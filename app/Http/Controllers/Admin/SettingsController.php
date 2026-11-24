<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\SettingService;
use App\Services\WhatsAppService;
use App\Support\SettingsSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Company setup and Store settings screens, both generated from
 * App\Support\SettingsSchema.
 */
class SettingsController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function edit(Request $request, string $screen): View
    {
        $sections = SettingsSchema::sections($screen);
        $values = [];
        foreach (SettingsSchema::fields($screen) as $key => $field) {
            $values[$key] = $field['type'] === 'image' ? $this->settings->raw($key) : $this->settings->get($key);
        }

        $activeTab = $request->query('tab');
        if (! array_key_exists((string) $activeTab, $sections)) {
            $activeTab = array_key_first($sections);
        }

        return view('admin.settings.edit', [
            'screen' => $screen,
            'screenTitle' => SettingsSchema::SCREENS[$screen]['title'],
            'sections' => $sections,
            'values' => $values,
            'activeTab' => $activeTab,
        ]);
    }

    public function update(UpdateSettingsRequest $request, string $screen): RedirectResponse
    {
        $validated = $request->validated();
        $toSave = [];
        $groups = [];

        foreach (SettingsSchema::fields($screen) as $key => $field) {
            $groups[$key] = $field['group'];

            if ($field['type'] === 'image') {
                if ($request->hasFile($key)) {
                    $toSave[$key] = $this->settings->storeImage($key, $request->file($key));
                } elseif ($request->boolean('remove_'.$key)) {
                    $this->settings->deleteImage($key);
                    $toSave[$key] = null;
                }

                continue;
            }

            $value = isset($validated[$key]) ? trim((string) $validated[$key]) : null;

            if ($key === 'whatsapp_number' && $value) {
                $value = WhatsAppService::normalizeNumber($value);
            }

            $toSave[$key] = $value === '' ? null : $value;
        }

        $this->settings->setMany($toSave, $groups);
        Cache::forget('nav_categories');
        Cache::forget('sitemap_xml');

        return redirect()
            ->route('admin.settings.edit', ['screen' => $screen, 'tab' => $request->input('active_tab')])
            ->with('success', SettingsSchema::SCREENS[$screen]['title'].' saved. The website now uses the new details.');
    }
}
