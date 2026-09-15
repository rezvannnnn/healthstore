<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

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

        return Inertia::render('Blog/Show', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'featured_image' => $article->featured_image,
                'featured_image_alt' => $article->featured_image_alt,
                'seo_title' => $article->seo_title,
                'seo_description' => $seoDescription,
                'canonical_url' => $canonicalUrl,
                'category' => $article->category?->only(['id', 'name', 'slug']),
                'author' => $article->author?->only(['id', 'name']),
                'published_at' => $article->published_at?->toISOString(),
            ],
        ]);
    }
}
