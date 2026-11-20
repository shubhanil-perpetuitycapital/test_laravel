<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\SettingsSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reads and writes admin-configured settings.
 *
 * All values are loaded with one query and cached (file cache on shared
 * hosting); the cache is cleared whenever settings are saved.
 */
class SettingService
{
    private const CACHE_KEY = 'app_settings_v1';

    /** @var array<string, mixed>|null in-request memo */
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
            return $values[$key];
        }

        return $default ?? (SettingsSchema::defaults()[$key] ?? null);
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key) ?? '0', FILTER_VALIDATE_BOOLEAN);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            $this->values = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            // Database not configured yet (fresh install) — fall back to defaults.
            $this->values = [];
        }

        return $this->values;
    }

    /**
     * Persist many values at once.
     *
     * @param  array<string, mixed>  $values  key => value
     * @param  array<string, string>  $groups  key => group name
     */
    public function setMany(array $values, array $groups = []): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'group' => $groups[$key] ?? 'general',
                    'value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                ]
            );
        }

        $this->flush();
    }

    /**
     * Store an uploaded image on the public uploads disk, deleting the
     * file it replaces. Returns the relative path saved in settings.
     */
    public function storeImage(string $key, UploadedFile $file, string $folder = 'branding'): string
    {
        $this->deleteImage($key);

        $name = Str::slug($key).'-'.Str::random(8).'.'.$file->extension();

        return $file->storeAs($folder, $name, 'uploads');
    }

    public function deleteImage(string $key): void
    {
        $current = Setting::query()->where('key', $key)->value('value');

        if ($current && ! str_starts_with($current, 'images/')) {
            Storage::disk('uploads')->delete($current);
        }
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }
}
