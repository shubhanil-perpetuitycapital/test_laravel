@extends('admin.layouts.app')

@section('title', 'Custom enquiries')

@section('content')
    <form method="get" class="filter-bar">
        <div>
            <label class="form-label" for="eq">Search</label>
            <input id="eq" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Name, mobile, reference or occasion">
        </div>
        <div>
            <label class="form-label" for="es">Status</label>
            <select id="es" name="status" class="form-select" data-auto-submit>
                <option value="">All</option>
                @foreach (\App\Models\CustomOrderEnquiry::STATUSES as $v => $l)
                    <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div><button class="btn btn-outline-secondary w-100">Filter</button></div>
    </form>

    <div class="table-card">
        @if ($enquiries->isEmpty())
            <x-admin.empty icon="bi-stars" title="No enquiries yet">Requests from the Custom orders page on your website appear here.</x-admin.empty>
        @else
            <div class="table-responsive">
                <table class="table table-stack">
                    <thead><tr><th>Reference</th><th>Customer</th><th>Occasion</th><th>Quantity</th><th>Needed by</th><th>Quote</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($enquiries as $e)
                            <tr>
                                <td data-label="Reference"><a class="row-link" href="{{ route('admin.enquiries.show', $e) }}">{{ $e->enquiry_number }}</a><span class="cell-sub">{{ $e->created_at->format('j M, g:i a') }}</span></td>
                                <td data-label="Customer">{{ $e->name }}<span class="cell-sub">{{ $e->mobile }}</span></td>
                                <td data-label="Occasion">{{ $e->occasion ?: '—' }}<span class="cell-sub">{{ $e->chocolate_type }}</span></td>
                                <td data-label="Quantity">{{ $e->quantity }}</td>
                                <td data-label="Needed by">
                                    @if ($e->required_date)
                                        <span @class(['text-danger fw-semibold' => $e->required_date->isBefore(now()->addDays(5)) && ! in_array($e->status, ['converted', 'closed'])])>{{ $e->required_date->format('j M') }}</span>
                                    @else — @endif
                                </td>
                                <td data-label="Quote">{{ $e->quoted_amount ? money($e->quoted_amount) : '—' }}</td>
                                <td data-label="Status"><span class="badge {{ $e->status_badge }}">{{ $e->status_label }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($enquiries->hasPages())<div class="table-card__foot">{{ $enquiries->links() }}</div>@endif
        @endif
    </div>
@endsection
