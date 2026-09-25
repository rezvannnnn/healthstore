<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\MediaService;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ArticleController extends Controller
{
    public function __construct(protected MediaService $mediaService) {}

    public function index(Request $request): Response
    {
        $validatedFilters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(['all', 'published', 'draft'])],
        ]);

        $search = trim((string) ($validatedFilters['search'] ?? ''));
        $status = (string) ($validatedFilters['status'] ?? 'all');

        $query = Article::query()
            ->with('category:id,name')
            ->with('author:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status === 'published') {
            $query->where('is_active', true)->whereNotNull('published_at')->where('published_at', '<=', now());
        } elseif ($status === 'draft') {
            $query->where(function ($builder): void {
                $builder->where('is_active', false)->orWhereNull('published_at')->orWhere('published_at', '>', now());
            });
        }

        $paginator = $query->paginate(20)->withQueryString();
        $articles = [];

        foreach ($paginator->items() as $article) {
            /** @var Article $article */
            $articles[] = [
                'id' => $article->id,
                'title' => $article->title,
                'slug' => $article->slug,
                'category' => $article->category?->name,
                'author' => $article->author?->name,
                'is_active' => $article->is_active,
                'is_featured' => $article->is_featured,
                'published_at' => $article->published_at?->toISOString(),
                'created_at' => $article->created_at?->toISOString(),
            ];
        }

        return Inertia::render('Admin/Articles/Index', [
            'articles' => $articles,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Articles/Create', [
            'categories' => ArticleCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        if ($request->hasFile('featured_image_file')) {
            $data['featured_image'] = $this->mediaService->storeImage($request->file('featured_image_file'), 'articles');
        }
        unset($data['featured_image_file']);
        $data['slug'] = $this->makeSlug($data['slug'] ?? null, $data['title']);
        $data['author_id'] = $request->user()?->id;
        $data['published_at'] = $this->normalizePublishedAt($data['published_at'] ?? null, $data['is_active'] ?? false);

        Article::create($data);

        return to_route('admin.articles.index')->with('success', 'مقاله با موفقیت ایجاد شد.');
    }

    public function edit(Article $article): Response
    {
        return Inertia::render('Admin/Articles/Edit', [
            'article' => [
                'id' => $article->id,
                'category_id' => $article->category_id,
                'title' => $article->title,
                'slug' => $article->slug,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'featured_image' => $article->featured_image,
                'featured_image_url' => $this->mediaService->url($article->featured_image),
                'featured_image_alt' => $article->featured_image_alt,
                'seo_title' => $article->seo_title,
                'seo_description' => $article->seo_description,
                'canonical_url' => $article->canonical_url,
                'is_active' => $article->is_active,
                'is_featured' => $article->is_featured,
                'published_at' => $article->published_at,
            ],
            'categories' => ArticleCategory::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $oldImage = $article->featured_image;
        $data = $this->validatedData($request, $article);
        if ($request->hasFile('featured_image_file')) {
            $data['featured_image'] = $this->mediaService->storeImage($request->file('featured_image_file'), 'articles');
        }
        unset($data['featured_image_file']);
        $data['slug'] = $this->makeSlug($data['slug'] ?? null, $data['title']);
        $data['published_at'] = $this->normalizePublishedAt($data['published_at'] ?? null, $data['is_active'] ?? false);

        $article->update($data);

        if ($request->hasFile('featured_image_file') && $data['featured_image'] !== $oldImage) {
            $this->mediaService->deleteIfStored($oldImage);
        }

        return to_route('admin.articles.index')->with('success', 'مقاله با موفقیت ویرایش شد.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return to_route('admin.articles.index')->with('success', 'مقاله حذف شد.');
    }

    /** @return array<string, mixed> */
    protected function validatedData(Request $request, ?Article $article = null): array
    {
        $request->merge([
            'slug' => trim((string) $request->input('slug', '')) !== ''
                ? Str::slug((string) $request->input('slug'))
                : null,
        ]);

        return $request->validate([
            'category_id' => ['nullable', 'integer', Rule::exists('article_categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:2048'],
            'featured_image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'canonical_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    protected function makeSlug(?string $slug, string $title): string
    {
        return trim((string) $slug) !== '' ? Str::slug($slug) : Str::slug($title);
    }

    protected function normalizePublishedAt(?string $publishedAt, bool $isActive): ?string
    {
        if (! $isActive) {
            return $publishedAt;
        }

        return $publishedAt ?: now()->toDateTimeString();
    }
}
