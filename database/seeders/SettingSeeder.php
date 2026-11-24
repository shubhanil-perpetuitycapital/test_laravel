<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingService;
use App\Support\SettingsSchema;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /** Writes default settings for both admin screens; never overwrites existing values. */
    public function run(): void
    {
        foreach (SettingsSchema::allFields() as $key => $field) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                ['group' => $field['group'], 'type' => $field['type'], 'value' => $field['default'] ?? null]
            );
        }

        app(SettingService::class)->flush();
    }
}
