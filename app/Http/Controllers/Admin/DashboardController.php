<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\SettingService;
use App\Support\SettingsSchema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SettingService $settings): View
    {
        $stats = [
            'orders_total' => Order::query()->count(),
            'orders_today' => Order::query()->whereDate('created_at', today())->count(),
            'orders_pending' => Order::query()->where('status', OrderStatus::Pending)->count(),
            'products' => Product::query()->count(),
            'products_out_of_stock' => Product::query()->where('is_active', true)->where('stock_quantity', 0)->count(),
            'categories' => Category::query()->count(),
            'customers' => Customer::query()->count(),
        ];

        // Company-setup checklist: which essentials are still on placeholder values.
        $defaults = SettingsSchema::defaults();
        $checklist = [
            ['label' => 'Brand name', 'done' => $settings->get('company_name') !== $defaults['company_name']],
            ['label' => 'Logo', 'done' => filled($settings->all()['logo'] ?? null)],
            ['label' => 'WhatsApp number', 'done' => $settings->get('whatsapp_number') !== $defaults['whatsapp_number']],
            ['label' => 'Business address', 'done' => $settings->get('address_line1') !== $defaults['address_line1']],
            ['label' => 'FSSAI number', 'done' => filled($settings->get('fssai_number'))],
            ['label' => 'Instagram link', 'done' => filled($settings->get('instagram_url'))],
        ];

        return view('admin.dashboard', compact('stats', 'checklist'));
    }
}
