<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Slug
{
    /**
     * A URL slug unique within the model's table (soft-deleted rows included,
     * so a restored record never collides).
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function unique(string $modelClass, string $source, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $i = 2;

        $query = fn (string $candidate) => $modelClass::query()
            ->when(method_exists($modelClass, 'bootSoftDeletes'), fn ($q) => $q->withTrashed())
            ->where($column, $candidate)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        while ($query($slug)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
