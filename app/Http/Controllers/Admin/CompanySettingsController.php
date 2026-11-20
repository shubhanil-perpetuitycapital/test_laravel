<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCompanySettingsRequest;
use App\Services\SettingService;
use App\Services\WhatsAppService;
use App\Support\SettingsSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CompanySettingsController extends Controller
{
    public function __construct(private SettingService $settings) {}

    public function edit(Request $request): View
    {
        $sections = SettingsSchema::company();
        $values = [];

        foreach (SettingsSchema::companyFields() as $key => $field) {
            $values[$key] = $this->settings->get($key);
        }

        $activeTab = $request->query('tab');
        if (! array_key_exists((string) $activeTab, $sections)) {
            $activeTab = array_key_first($sections);
        }

        return view('admin.company.edit', compact('sections', 'values', 'activeTab'));
    }

    public function update(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $fields = SettingsSchema::companyFields();
        $validated = $request->validated();
        $toSave = [];
        $groups = [];

        foreach ($fields as $key => $field) {
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

            if ($key === 'whatsapp_number') {
                $toSave[$key] = WhatsAppService::normalizeNumber((string) $validated[$key]);

                continue;
            }

            $toSave[$key] = isset($validated[$key]) ? trim((string) $validated[$key]) : null;
        }

        $this->settings->setMany($toSave, $groups);

        // Pages cache company data indirectly (navigation, sitemap).
        Cache::forget('nav_categories');

        return redirect()
            ->route('admin.company.edit', ['tab' => $request->input('active_tab')])
            ->with('success', 'Company details saved. The website now shows the updated information.');
    }
}
