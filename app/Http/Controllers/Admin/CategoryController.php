    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\MediaService;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function index(): Response
    {
        $categories = Category::query()
            ->with('parent:id,name')
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Create', [
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        if ($request->hasFile('image_file')) {
            $data['image'] = $this->mediaService->storeImage($request->file('image_file'), 'categories');
        }
        unset($data['image_file']);
        $data['slug'] = $this->makeSlug($data['slug'] ?? null, $data['name']);

        Category::create($data);

        return to_route('admin.categories.index')->with('success', 'دسته‌بندی با موفقیت ایجاد شد.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Admin/Categories/Edit', [
            'category' => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'image' => $category->image,
                'image_url' => $this->mediaService->url($category->image),
                'is_active' => $category->is_active,
                'sort_order' => $category->sort_order,
            ],
            'parents' => $this->parentOptions($category),
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $oldImage = $category->image;
        $data = $this->validatedData($request, $category);
        if ($request->hasFile('image_file')) {
            $data['image'] = $this->mediaService->storeImage($request->file('image_file'), 'categories');
        }
        unset($data['image_file']);
        $data['slug'] = $this->makeSlug($data['slug'] ?? null, $data['name']);

        $category->update($data);

        if ($request->hasFile('image_file') && $data['image'] !== $oldImage) {
            $this->mediaService->deleteIfStored($oldImage);
        }

        return to_route('admin.categories.index')->with('success', 'دسته‌بندی با موفقیت ویرایش شد.');
    }

    protected function parentOptions(?Category $category = null): Collection
    {
        $query = Category::query()->where('is_active', true)->orderBy('name');

        if ($category) {
            $query->whereNotIn('id', $this->excludedParentIds($category));
        }

        return $query->get(['id', 'name']);
    }

    protected function validatedData(Request $request, ?Category $category = null): array
    {
        $parentRules = [
            'nullable',
            'integer',
            Rule::exists('categories', 'id')->where('is_active', true),
        ];

        if ($category) {
            $parentRules[] = Rule::notIn($this->excludedParentIds($category));
        }

        return $request->validate([
            'parent_id' => $parentRules,
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'string', 'max:2048'],
            'image_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /**
     * @return list<int>
     */
    protected function excludedParentIds(Category $category): array
    {
        $excluded = [$category->id];
        $pending = [$category->id];

        while (true) {
            $childIds = array_map(
                'intval',
                Category::query()
                    ->whereIn('parent_id', $pending)
                    ->whereNotIn('id', $excluded)
                    ->pluck('id')
                    ->all(),
            );

            if (count($childIds) === 0) {
                break;
            }

            foreach ($childIds as $childId) {
                $excluded[] = $childId;
            }

            $pending = $childIds;
        }

        return $excluded;
    }

    protected function makeSlug(?string $slug, string $name): string
    {
        return trim((string) $slug) !== '' ? trim((string) $slug) : Str::slug($name);
    }
}
