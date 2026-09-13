<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        $brands = Brand::query()
            ->withCount('products')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Brands/Index', [
            'brands' => $brands,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Brands/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->makeSlug($data['slug'] ?? null, $data['name']);

        Brand::create($data);

        return to_route('admin.brands.index')->with('success', 'برند با موفقیت ایجاد شد.');
    }

    public function edit(Brand $brand): Response
    {
        return Inertia::render('Admin/Brands/Edit', [
            'brand' => $brand->only([
                'id', 'name', 'slug', 'description', 'logo', 'is_active',
            ]),
        ]);
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validatedData($request, $brand);
        $data['slug'] = $this->makeSlug($data['slug'] ?? null, $data['name']);

        $brand->update($data);

        return to_route('admin.brands.index')->with('success', 'برند با موفقیت ویرایش شد.');
    }

    protected function validatedData(Request $request, ?Brand $brand = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['boolean'],
        ]);
    }

    protected function makeSlug(?string $slug, string $name): string
    {
        return trim((string) $slug) !== '' ? trim((string) $slug) : Str::slug($name);
    }
}
