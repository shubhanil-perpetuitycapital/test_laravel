<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingService;
use App\Support\SettingsSchema;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Writes default company settings. Existing values are never
     * overwritten, so this is safe to re-run after the owner edits them.
     */
    public function run(): void
    {
        foreach (SettingsSchema::companyFields() as $key => $field) {
            Setting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'group' => $field['group'],
                    'type' => $field['type'],
                    'value' => $field['default'] ?? null,
                ]
            );
        }

        app(SettingService::class)->flush();
    }
}
