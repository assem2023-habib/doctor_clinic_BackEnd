<?php

namespace App\Domains\Shared\Traits;

use Illuminate\Support\Facades\Cache;

trait ClearsCache
{
    protected static function bootClearsCache(): void
    {
        static::saved(static function ($model) {
            if (method_exists($model, 'cacheVersionsToIncrement')) {
                foreach ($model->cacheVersionsToIncrement() as $key) {
                    Cache::increment($key);
                }
            }
        });

        static::deleted(static function ($model) {
            if (method_exists($model, 'cacheVersionsToIncrement')) {
                foreach ($model->cacheVersionsToIncrement() as $key) {
                    Cache::increment($key);
                }
            }
        });
    }
}
