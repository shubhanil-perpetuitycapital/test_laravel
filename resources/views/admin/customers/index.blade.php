@extends('admin.layouts.app')

@section('title', 'Customers')

@section('content')
    <form method="get" class="filter-bar">
        <div>
            <label class="form-label" for="cq">Search</label>
            <input id="cq" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, mobile or email">
        </div>
        <div>
            <label class="form-label" for="cs">Sort by</label>
            <select id="cs" name="sort" class="form-select" data-auto-submit>
                @foreach (['recent' => 'Newest first', 'spend' => 'Highest spend', 'orders' => 'Most orders', 'name' => 'Name'] as $v => $l)
                    <option value="{{ $v }}" @selected(($filters['sort'] ?? 'recent') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div><button class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="table-card">
        @if ($customers->isEmpty())
            <x-admin.empty icon="bi-people" title="No customers yet">Customers are added automatically when they order or send an enquiry.</x-admin.empty>
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th>Name</th><th>Mobile</th><th class="hide-sm">Email</th><th>Orders</th><th>Total spent</th><th>Last order</th></tr></thead>
                    <tbody>
                        @foreach ($customers as $c)
                            <tr>
                                <td data-label="Name"><a class="row-link" href="{{ route('admin.customers.show', $c) }}">{{ $c->name }}</a></td>
                                <td data-label="Mobile">{{ $c->mobile }}</td>
                                <td data-label="Email" class="hide-sm">{{ $c->email ?: '—' }}</td>
                                <td data-label="Orders">{{ $c->orders_count }}</td>
                                <td data-label="Total spent">{{ money($c->total_spent ?? 0) }}</td>
                                <td data-label="Last order">{{ $c->last_order_at ? \Illuminate\Support\Carbon::parse($c->last_order_at)->format('j M Y') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($customers->hasPages())<div class="table-card__foot">{{ $customers->links() }}</div>@endif
        @endif
    </div>
@endsection
