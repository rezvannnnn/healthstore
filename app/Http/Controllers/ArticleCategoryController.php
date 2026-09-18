<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use Inertia\Inertia;
use Inertia\Response;

class ArticleCategoryController extends Controller
{
    public function index(): Response
    {
        $paginator = ArticleCategory::query()
            ->withCount([
                'articles as published_articles_count' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now()),
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(24);

        $categories = collect($paginator->items())
            ->map(fn (ArticleCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'articles_count' => (int) $category->getAttribute('published_articles_count'),
            ])
            ->values()
            ->all();

        $structuredCategories = collect($categories)
            ->map(fn (array $category, int $index) => [
                '@type' => 'ListItem',
                'position' => ($paginator->currentPage() - 1) * $paginator->perPage() + $index + 1,
                'url' => route('blog.categories.show', $category['slug']),
                'name' => $category['name'],
            ])
            ->all();

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'دسته‌بندی‌های مجله | مجله سلامت',
            'description' => 'مشاهده دسته‌بندی‌های فعال مجله سلامت و مطالب آموزشی هر دسته.',
            'url' => route('blog.categories.index'),
        ];

        if ($structuredCategories !== []) {
            $structuredData['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($structuredCategories),
                'itemListElement' => $structuredCategories,
            ];
        }

        return Inertia::render('Blog/Categories', [
            'seo' => [
                'title' => 'دسته‌بندی‌های مجله | مجله سلامت',
                'description' => 'مشاهده دسته‌بندی‌های فعال مجله سلامت و مطالب آموزشی هر دسته.',
                'canonical' => route('blog.categories.index'),
            ],
            'categories' => $categories,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'structuredData' => $structuredData,
        ]);
    }

    public function show(ArticleCategory $articleCategory): Response
    {
        abort_unless($articleCategory->is_active, 404);

        $paginator = Article::query()
            ->with('category:id,name,slug')
            ->where('category_id', $articleCategory->id)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(12);

        $articles = collect($paginator->items())
            ->map(fn (Article $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'featured_image' => $article->featured_image,
                'featured_image_alt' => $article->featured_image_alt,
                'published_at' => $article->published_at?->toISOString(),
            ])
            ->values()
            ->all();

        $categoryUrl = route('blog.categories.show', $articleCategory);
        $description = $articleCategory->description
            ?: 'مطالب آموزشی دسته '.$articleCategory->name.' در مجله سلامت.';

        $structuredArticles = collect($articles)
            ->map(fn (array $article, int $index) => [
                '@type' => 'ListItem',
                'position' => ($paginator->currentPage() - 1) * $paginator->perPage() + $index + 1,
                'url' => route('blog.show', $article['slug']),
                'name' => $article['title'],
            ])
            ->all();

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $articleCategory->name,
            'description' => $description,
            'url' => $categoryUrl,
        ];

        if ($structuredArticles !== []) {
            $structuredData['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($structuredArticles),
                'itemListElement' => $structuredArticles,
            ];
        }

        return Inertia::render('Blog/Category', [
            'seo' => [
                'title' => $articleCategory->name.' | مجله سلامت',
                'description' => $description,
                'canonical' => $categoryUrl,
            ],
            'category' => [
                'id' => $articleCategory->id,
                'name' => $articleCategory->name,
                'slug' => $articleCategory->slug,
                'description' => $articleCategory->description,
            ],
            'articles' => $articles,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'structuredData' => $structuredData,
        ]);
    }
}
