@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'help' => null, 'required' => false])
@php
    $id = 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $current = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<label class="form-label" for="{{ $id }}">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
<select id="{{ $id }}" name="{{ $name }}" {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }} @if ($required) required @endif>
    @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
    @foreach ($options as $optValue => $optLabel)
        <option value="{{ $optValue }}" @selected($current === (string) $optValue)>{{ $optLabel }}</option>
    @endforeach
</select>
@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
@if ($help)<div class="form-text">{{ $help }}</div>@endif
