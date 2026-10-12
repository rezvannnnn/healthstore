<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const SIZE = 1000;

    private function query(string $type): Builder
    {
        $class = match ($type) {
            'products' => Product::class, 'brands' => Brand::class, 'categories' => Category::class, 'articles' => Article::class, 'article-categories' => ArticleCategory::class, default => abort(404)
        };
        $query = $class::query()->where('is_active', true);
        if ($type === 'articles') {
            $query->whereNotNull('published_at')->where('published_at', '<=', now());
        }

        return $query;
    }

    private function staticUrls(): array
    {
        $urls = array_map(fn ($route) => ['loc' => route($route)], ['home', 'products.index', 'categories.index', 'blog.index', 'brands.index', 'blog.categories.index']);
        foreach (['contact', 'shipping', 'returns', 'privacy'] as $section) {
            $urls[] = ['loc' => route('information', $section)];
        }

        return $urls;
    }

    private function rows(string $type, int $page): array
    {
        $route = match ($type) {
            'products' => 'products.show', 'brands' => 'brands.show', 'categories' => 'categories.show', 'articles' => 'blog.show', 'article-categories' => 'blog.categories.show', default => abort(404)
        };

        return $this->query($type)->orderBy('id')->offset(($page - 1) * self::SIZE)->limit(self::SIZE)->get(['slug', 'updated_at'])
            ->map(fn ($model) => ['loc' => route($route, $model->getAttribute('slug')), 'lastmod' => $model->getAttribute('updated_at')?->toAtomString()])->all();
    }

    private function xml(array $rows, string $root = 'urlset'): string
    {
        $item = $root === 'sitemapindex' ? 'sitemap' : 'url';
        $xml = '<?xml version="1.0" encoding="UTF-8"?><'.$root.' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($rows as $row) {
            $xml .= '<'.$item.'><loc>'.e($row['loc']).'</loc>';
            if (! empty($row['lastmod'])) {
                $xml .= '<lastmod>'.e($row['lastmod']).'</lastmod>';
            } $xml .= '</'.$item.'>';
        }

        return $xml.'</'.$root.'>';
    }

    public function __invoke(): Response
    {
        $key = 'sitemap:root:'.sha1(url('/')).':'.Cache::get('sitemap:version', '1');
        $xml = Cache::remember($key, 300, function () {
            $counts = [];
            foreach (['products', 'brands', 'categories', 'articles', 'article-categories'] as $type) {
                $counts[$type] = $this->query($type)->count();
            }
            if (array_sum($counts) + count($this->staticUrls()) <= self::SIZE) {
                $urls = $this->staticUrls();
                foreach (array_keys($counts) as $type) {
                    $urls = array_merge($urls, $this->rows($type, 1));
                }

                return $this->xml($urls);
            }
            $urls = [['loc' => route('sitemap.part', ['type' => 'static', 'page' => 1])]];
            foreach ($counts as $type => $count) {
                for ($page = 1; $page <= ceil($count / self::SIZE); $page++) {
                    $urls[] = ['loc' => route('sitemap.part', compact('type', 'page'))];
                }
            }

            return $this->xml($urls, 'sitemapindex');
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function part(string $type, int $page): Response
    {
        abort_unless($page >= 1 && ($type !== 'static' || $page === 1), 404);
        $key = 'sitemap:'.sha1(url('/')).':'.Cache::get('sitemap:version', '1').':'.$type.':'.$page;
        $xml = Cache::remember($key, 300, function () use ($type, $page) {
            $rows = $type === 'static' ? $this->staticUrls() : $this->rows($type, $page);
            abort_if($rows === [], 404);

            return $this->xml($rows);
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
