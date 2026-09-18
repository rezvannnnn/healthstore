<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            [
                'loc' => route('home'),
            ],
            [
                'loc' => route('products.index'),
            ],
            [
                'loc' => route('categories.index'),
            ],
            [
                'loc' => route('blog.index'),
            ],
            [
                'loc' => route('brands.index'),
            ],
            [
                'loc' => route('blog.categories.index'),
            ],
        ];

        foreach (Category::query()->where('is_active', true)->get(['slug', 'updated_at']) as $category) {
            $urls[] = [
                'loc' => route('categories.show', $category->slug),
                'lastmod' => $category->updated_at?->toAtomString(),
            ];
        }

        foreach (Brand::query()->where('is_active', true)->get(['slug', 'updated_at']) as $brand) {
            $urls[] = [
                'loc' => route('brands.show', $brand->slug),
                'lastmod' => $brand->updated_at?->toAtomString(),
            ];
        }

        foreach (Product::query()->where('is_active', true)->get(['slug', 'updated_at']) as $product) {
            $urls[] = [
                'loc' => route('products.show', $product),
                'lastmod' => $product->updated_at?->toAtomString(),
            ];
        }

        foreach (ArticleCategory::query()->where('is_active', true)->get(['slug', 'updated_at']) as $category) {
            $urls[] = [
                'loc' => route('blog.categories.show', $category->slug),
                'lastmod' => $category->updated_at?->toAtomString(),
            ];
        }

        foreach (Article::query()
            ->where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get(['slug', 'updated_at']) as $article) {
            $urls[] = [
                'loc' => route('blog.show', $article->slug),
                'lastmod' => $article->updated_at?->toAtomString(),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($urls as $url) {
            $xml .= '<url><loc>'.e($url['loc']).'</loc>';

            if (! empty($url['lastmod'])) {
                $xml .= '<lastmod>'.e($url['lastmod']).'</lastmod>';
            }

            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
