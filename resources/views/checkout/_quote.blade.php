@if ($quote->ok)
    <p class="delivery-box__line"><i class="bi bi-check-circle-fill text-success" aria-hidden="true"></i>
        <strong>{{ $quote->method === 'local' ? 'We deliver here' : 'Courier delivery' }}@if ($quote->area) · {{ $quote->area }}@endif</strong>
    </p>
    <p class="delivery-box__line">
        @if ($quote->method === 'local')
            Expected delivery: <strong>{{ $quote->estimate }}</strong>
        @else
            {{ $quote->estimate }}
        @endif
    </p>
    <p class="delivery-box__line">Delivery charge: <strong>{{ $quote->chargeLabel() }}</strong></p>
    @if ($quote->note)
        <p class="delivery-box__note">{{ $quote->note }}</p>
    @endif
@else
    <p class="mb-0 text-danger"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $quote->error }}</p>
@endif
