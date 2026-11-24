<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\SettingsSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Reads and writes admin-configured settings.
 *
 * All values load with one query and are cached (file cache on shared
 * hosting); the cache is cleared whenever settings are saved.
 */
class SettingService
{
    private const CACHE_KEY = 'app_settings_v1';

    /** @var array<string, mixed>|null in-request memo */
    private ?array $values = null;

    public function __construct(private ImageService $images) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
            return $values[$key];
        }

        return $default ?? (SettingsSchema::defaults()[$key] ?? null);
    }

    /** Raw stored value without falling back to defaults. */
    public function raw(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    public function bool(string $key): bool
    {
        $value = $this->all()[$key] ?? (SettingsSchema::defaults()[$key] ?? '0');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function float(string $key): ?float
    {
        $value = $this->get($key);

        return is_numeric($value) ? (float) $value : null;
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
            $this->values = []; // database not configured yet (fresh install)
        }

        return $this->values;
    }

    /**
     * @param  array<string, mixed>  $values  key => value
     * @param  array<string, string>  $groups  key => group name
     */
    public function setMany(array $values, array $groups = []): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => $groups[$key] ?? 'general', 'value' => is_bool($value) ? ($value ? '1' : '0') : $value]
            );
        }

        $this->flush();
    }

    /** Store an uploaded image, deleting the file it replaces. */
    public function storeImage(string $key, UploadedFile $file): string
    {
        $this->deleteImage($key);

        // Favicons and QR codes must stay pixel-exact; everything else is resized.
        $maxWidth = in_array($key, ['favicon', 'upi_qr'], true) ? null : 1600;

        return $this->images->store($file, 'branding', $maxWidth);
    }

    public function deleteImage(string $key): void
    {
        $this->images->delete(Setting::query()->where('key', $key)->value('value'));
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }
}
