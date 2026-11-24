<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryZoneRequest;
use App\Models\DeliveryZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryZoneController extends Controller
{
    public function index(): View
    {
        $zones = DeliveryZone::query()->withCount('pincodes')->orderBy('name')->get();

        return view('admin.delivery-zones.index', compact('zones'));
    }

    public function create(): View
    {
        return view('admin.delivery-zones.form', ['zone' => new DeliveryZone(['is_active' => true, 'lead_days' => 1, 'delivery_charge' => 60]), 'pincodeText' => '']);
    }

    public function store(DeliveryZoneRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $zone = DeliveryZone::query()->create($request->safe()->except('pincodes'));
            $this->syncPincodes($zone, $request->pincodes());
        });

        return redirect()->route('admin.delivery-zones.index')->with('success', 'Delivery area created.');
    }

    public function edit(DeliveryZone $zone): View
    {
        $pincodeText = $zone->pincodes->map(fn ($p) => trim($p->pincode.' '.$p->area_name))->implode("\n");

        return view('admin.delivery-zones.form', compact('zone', 'pincodeText'));
    }

    public function update(DeliveryZoneRequest $request, DeliveryZone $zone): RedirectResponse
    {
        DB::transaction(function () use ($request, $zone) {
            $zone->update($request->safe()->except('pincodes'));
            $this->syncPincodes($zone, $request->pincodes());
        });

        return redirect()->route('admin.delivery-zones.index')->with('success', 'Delivery area saved.');
    }

    public function destroy(DeliveryZone $zone): RedirectResponse
    {
        $zone->delete(); // pincodes cascade; past orders keep their charges

        return redirect()->route('admin.delivery-zones.index')->with('success', 'Delivery area "'.$zone->name.'" deleted.');
    }

    /** @param array<string, ?string> $pincodes */
    private function syncPincodes(DeliveryZone $zone, array $pincodes): void
    {
        $zone->pincodes()->whereNotIn('pincode', array_keys($pincodes))->delete();

        foreach ($pincodes as $pin => $area) {
            $zone->pincodes()->updateOrCreate(['pincode' => (string) $pin], ['area_name' => $area, 'is_active' => true]);
        }
    }
}
