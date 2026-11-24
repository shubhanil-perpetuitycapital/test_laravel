@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false, 'prefix' => null])
@php
    $id = 'f_'.str_replace(['[', ']', '.'], '_', $name);
    $describedBy = trim(($help ? $id.'_help ' : '').($errors->has($name) ? $id.'_error' : ''));
@endphp
<label class="form-label" for="{{ $id }}">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
@if ($prefix)<div class="input-group has-validation"><span class="input-group-text">{{ $prefix }}</span>@endif
<input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ old($name, $value) }}"
       {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
       @if ($required) required @endif
       @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
@error($name)<div class="invalid-feedback" id="{{ $id }}_error">{{ $message }}</div>@enderror
@if ($prefix)</div>@endif
@if ($help)<div class="form-text" id="{{ $id }}_help">{{ $help }}</div>@endif
