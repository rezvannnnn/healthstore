<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreObserver
{
    private function prefix(Model $model): ?string
    {
        return match ($model->getTable()) {
            'products' => 'products', 'brands' => 'brands', 'categories' => 'categories', 'articles' => 'blog', 'article_categories' => 'blog/category', default => null
        };
    }

    public function saving(Model $model): void
    {
        $prefix = $this->prefix($model);
        if ($prefix && $model->exists && $model->isDirty('slug')) {
            $old = url('/'.$prefix.'/'.$model->getRawOriginal('slug'));
            if (array_key_exists('canonical_url', $model->getAttributes()) && (! $model->getAttribute('canonical_url') || $model->getAttribute('canonical_url') === $old)) {
                $model->setAttribute('canonical_url', url('/'.$prefix.'/'.$model->getAttribute('slug')));
            }
        }
    }

    public function saved(Model $model): void
    {
        $prefix = $this->prefix($model);
        if ($prefix) {
            Cache::forever('sitemap:version', Str::uuid()->toString());
            DB::table('slug_redirects')->where('old_path', '/'.$prefix.'/'.$model->getAttribute('slug'))->delete();
            if ($model->wasChanged('slug') && $model->getRawOriginal('slug')) {
                $old = '/'.$prefix.'/'.$model->getRawOriginal('slug');
                $new = '/'.$prefix.'/'.$model->getAttribute('slug');
                DB::table('slug_redirects')->where('new_path', $old)->update(['new_path' => $new, 'updated_at' => now()]);
                DB::table('slug_redirects')->updateOrInsert(['old_path' => $old], ['new_path' => $new, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
        if (auth()->id()) {
            $fields = array_values(array_diff(array_keys($model->getChanges()), ['updated_at', 'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_last_counter']));
            if ($fields !== []) {
                DB::table('audit_logs')->insert(['actor_id' => auth()->id(), 'subject_type' => $model->getTable(), 'subject_id' => $model->getKey(), 'event' => $model->wasRecentlyCreated ? 'created' : 'updated', 'changed_fields' => json_encode($fields, JSON_THROW_ON_ERROR), 'created_at' => now()]);
            }
        }
    }

    public function deleted(Model $model): void
    {
        Cache::forever('sitemap:version', Str::uuid()->toString());
        if (auth()->id()) {
            DB::table('audit_logs')->insert(['actor_id' => auth()->id(), 'subject_type' => $model->getTable(), 'subject_id' => $model->getKey(), 'event' => 'deleted', 'changed_fields' => '[]', 'created_at' => now()]);
        }
    }
}
