@props(['action', 'confirm' => 'This cannot be undone.', 'label' => 'Delete', 'icon' => true, 'size' => 'sm'])
<form method="post" action="{{ $action }}" class="d-inline" data-confirm="{{ $confirm }}">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->class(['btn btn-outline-danger', 'btn-'.$size => $size]) }} @if ($icon && $label === '') aria-label="Delete" @endif>
        @if ($icon)<i class="bi bi-trash3" aria-hidden="true"></i>@endif
        @if ($label !== '')<span @class(['ms-1' => $icon])>{{ $label }}</span>@endif
    </button>
</form>
