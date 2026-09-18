<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use Inertia\Inertia;
use Inertia\Response;

class ArticleCategoryController extends Controller
{
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
