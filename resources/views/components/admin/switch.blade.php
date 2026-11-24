@props(['name', 'label', 'checked' => false, 'help' => null])
@php $id = 'f_'.str_replace(['[', ']', '.'], '_', $name); @endphp
<div class="form-check form-switch admin-switch">
    <input type="hidden" name="{{ $name }}" value="0">
    <input class="form-check-input" type="checkbox" role="switch" id="{{ $id }}" name="{{ $name }}" value="1"
           @checked(filter_var(old($name, $checked), FILTER_VALIDATE_BOOLEAN))>
    <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
