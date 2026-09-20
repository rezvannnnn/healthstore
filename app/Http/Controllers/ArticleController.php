<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $validatedFilters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $search = trim((string) ($validatedFilters['search'] ?? ''));

        $query = Article::query()
            ->with('category:id,name,slug')
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate(12)->withQueryString();
        $articles = [];

        foreach ($paginator->items() as $article) {
            /** @var Article $article */
            $articles[] = [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'featured_image' => $article->featured_image,
                'featured_image_alt' => $article->featured_image_alt,
                'category' => $article->category?->only(['id', 'name', 'slug']),
                'published_at' => $article->published_at?->toISOString(),
            ];
        }

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
            'name' => 'مجله سلامت | مطالب آموزشی و کاربردی سلامت',
            'description' => 'مطالب آموزشی و کاربردی درباره سلامت و محصولات بهداشتی.',
            'url' => route('blog.index'),
        ];

        if ($structuredArticles !== []) {
            $structuredData['mainEntity'] = [
                '@type' => 'ItemList',
                'numberOfItems' => count($structuredArticles),
                'itemListElement' => $structuredArticles,
            ];
        }

        $categories = ArticleCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Blog/Index', [
            'seo' => [
                'title' => 'مجله سلامت | مطالب آموزشی و کاربردی سلامت',
                'description' => 'مطالب آموزشی و کاربردی درباره سلامت و محصولات بهداشتی.',
                'canonical' => url('/blog'),
            ],
            'articles' => $articles,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'structuredData' => $structuredData,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $article = Article::query()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->firstOrFail();

        $canonicalUrl = $article->canonical_url ?: url('/blog/'.$article->slug);
        $seoDescription = $article->seo_description ?: $article->excerpt;
        $featuredImage = $this->absoluteAssetUrl($article->featured_image);

        $relatedArticles = [];
        if ($article->category_id !== null) {
            $relatedArticles = Article::query()
                ->where('category_id', $article->category_id)
                ->whereKeyNot($article->id)
                ->where('is_active', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->orderByDesc('published_at')
                ->limit(4)
                ->get([
                    'id',
                    'title',
                    'slug',
                    'excerpt',
                    'featured_image',
                    'featured_image_alt',
                    'published_at',
                ])
                ->map(fn (Article $relatedArticle): array => [
                    'id' => $relatedArticle->id,
                    'title' => $relatedArticle->title,
                    'slug' => $relatedArticle->slug,
                    'excerpt' => $relatedArticle->excerpt,
                    'featured_image' => $relatedArticle->featured_image,
                    'featured_image_alt' => $relatedArticle->featured_image_alt,
                    'published_at' => $relatedArticle->published_at?->toISOString(),
                ])
                ->all();
        }

        return Inertia::render('Blog/Show', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'featured_image' => $featuredImage,
                'featured_image_alt' => $article->featured_image_alt,
                'seo_title' => $article->seo_title,
                'seo_description' => $seoDescription,
                'canonical_url' => $canonicalUrl,
                'category' => $article->category?->only(['id', 'name', 'slug']),
                'author' => $article->author?->only(['id', 'name']),
                'published_at' => $article->published_at?->toISOString(),
            ],
            'relatedArticles' => $relatedArticles,
        ]);
    }

    protected function absoluteAssetUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return url(ltrim($path, '/'));
    }
}
