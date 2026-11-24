<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Invoice {{ $order->order_number }} · {{ $company->name() }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,500&family=Figtree:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="{{ asset('assets/css/admin.css') }}" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="text-center my-3 no-print">
        <button type="button" class="btn btn-choc" onclick="window.print()">Print or save as PDF</button>
        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-light">Back to order</a>
    </div>

    <div class="invoice">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                @if ($company->logoUrl())
                    <img src="{{ $company->logoUrl() }}" alt="{{ $company->name() }}" style="max-height:56px">
                @else
                    <h1 class="mb-0">{{ $company->name() }}</h1>
                @endif
                <p class="small mt-2 mb-0">
                    <strong>{{ $company->legalName() }}</strong><br>
                    @foreach ($company->addressLines() as $line){{ $line }}<br>@endforeach
                    {{ $company->phone() }}@if ($company->email()) · {{ $company->email() }}@endif
                </p>
                @if ($company->gstin() || $company->fssai())
                    <p class="small mb-0">
                        @if ($company->gstin())GSTIN: {{ $company->gstin() }}<br>@endif
                        @if ($company->fssai())FSSAI Lic. No.: {{ $company->fssai() }}@endif
                    </p>
                @endif
            </div>
            <div class="text-md-end">
                <h2 class="h4 mb-1">Invoice</h2>
                <p class="small mb-0">Order <strong>{{ $order->order_number }}</strong><br>
                    Date: {{ $order->created_at->format('j M Y') }}<br>
                    Payment: {{ $order->payment_method_label }} ({{ $order->payment_status->label() }})</p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-sm-6">
                <h3 class="h6">Deliver to</h3>
                <p class="small mb-0">{{ $order->recipient_name }}<br>+91 {{ $order->recipient_mobile }}<br>{{ $order->full_address }}</p>
            </div>
            <div class="col-sm-6 text-sm-end">
                <h3 class="h6">Delivery</h3>
                <p class="small mb-0">{{ $order->delivery_method_label }}<br>
                    @if ($order->courier_name){{ $order->courier_name }} {{ $order->tracking_number }}<br>@endif
                    @if ($order->expected_delivery_date)Expected {{ $order->expected_delivery_date->format('j M Y') }}@endif</p>
            </div>
        </div>

        <table class="table table-sm">
            <thead><tr><th>#</th><th>Item</th><th class="text-end">Rate</th><th class="text-end">Qty</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->product_name }}@if ($item->weight) <span class="text-secondary">({{ $item->weight }})</span>@endif</td>
                        <td class="text-end">{{ money($item->unit_price, true) }}</td>
                        <td class="text-end">{{ $item->quantity }}</td>
                        <td class="text-end">{{ money($item->line_total, true) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end">{{ money($order->subtotal, true) }}</td></tr>
                @if ((float) $order->discount_amount > 0)
                    <tr><td colspan="4" class="text-end">Discount {{ $order->coupon_code }}</td><td class="text-end">−{{ money($order->discount_amount, true) }}</td></tr>
                @endif
                <tr><td colspan="4" class="text-end">Delivery</td><td class="text-end">{{ $order->delivery_charge_pending ? 'To be confirmed' : money($order->delivery_charge, true) }}</td></tr>
                <tr class="fw-bold"><td colspan="4" class="text-end">Total</td><td class="text-end">{{ money($order->total, true) }}</td></tr>
            </tfoot>
        </table>

        <p class="small text-secondary mt-4 mb-0">Thank you for choosing handmade. Questions about this order? WhatsApp us at {{ $company->phone() }} quoting {{ $order->order_number }}.</p>
    </div>
</body>
</html>
