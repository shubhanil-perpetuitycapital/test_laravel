<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', Rule::in(['recent', 'spend', 'orders', 'name'])],
        ]);

        $customers = Customer::query()
            ->withCount('orders')
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', '!=', OrderStatus::Cancelled->value)], 'total')
            ->withMax('orders as last_order_at', 'created_at')
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('mobile', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->when($filters['sort'] ?? 'recent', fn ($q, $sort) => match ($sort) {
                'spend' => $q->orderByDesc('total_spent'),
                'orders' => $q->orderByDesc('orders_count'),
                'name' => $q->orderBy('name'),
                default => $q->latest('id'),
            })
            ->paginate(config('store.admin_per_page'))
            ->withQueryString();

        return view('admin.customers.index', compact('customers', 'filters'));
    }

    public function show(Customer $customer): View
    {
        $orders = $customer->orders()->latest()->get();
        $enquiries = $customer->enquiries()->latest()->get();
        $totalSpent = $orders->where('status', '!=', OrderStatus::Cancelled)->sum('total');

        return view('admin.customers.show', compact('customer', 'orders', 'enquiries', 'totalSpent'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer->update($data);

        return back()->with('success', 'Customer details saved.');
    }
}
