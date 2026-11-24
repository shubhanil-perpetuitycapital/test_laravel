@props(['icon' => 'bi-inbox', 'title', 'action' => null, 'actionLabel' => null])
<div class="admin-empty">
    <i class="bi {{ $icon }}" aria-hidden="true"></i>
    <p class="admin-empty__title">{{ $title }}</p>
    @if ($slot->isNotEmpty())<p class="text-secondary mb-3">{{ $slot }}</p>@endif
    @if ($action)<a href="{{ $action }}" class="btn btn-choc">{{ $actionLabel }}</a>@endif
</div>
