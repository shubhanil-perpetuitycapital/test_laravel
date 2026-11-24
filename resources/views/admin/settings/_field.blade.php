@php
    $id = 'f_'.$name;
    $type = $field['type'];
    $required = in_array('required', $field['rules'] ?? [], true);
    $current = old($name, $value);
    $describedBy = trim((! empty($field['help']) ? $id.'_help ' : '').($errors->has($name) ? $id.'_error' : ''));
@endphp

@if ($type === 'boolean')
    <div class="form-check form-switch admin-switch">
        <input type="hidden" name="{{ $name }}" value="0">
        <input class="form-check-input" type="checkbox" role="switch" id="{{ $id }}" name="{{ $name }}" value="1"
               @checked(filter_var($current, FILTER_VALIDATE_BOOLEAN)) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        <label class="form-check-label" for="{{ $id }}">{{ $field['label'] }}</label>
    </div>
@elseif ($type === 'image')
    <x-admin.image :name="$name" :label="$field['label']" :current="$value ? media_url($value) : null" :dark="$name === 'logo_light'" />
@elseif ($type === 'textarea')
    <label class="form-label" for="{{ $id }}">{{ $field['label'] }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ str_starts_with($name, 'whatsapp_') && str_ends_with($name, '_template') ? 6 : 3 }}" data-preview-source="{{ $name }}"
              @class(['form-control', 'is-invalid' => $errors->has($name)])
              placeholder="{{ $field['placeholder'] ?? '' }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $current }}</textarea>
@else
    <label class="form-label" for="{{ $id }}">{{ $field['label'] }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    <input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $current }}" data-preview-source="{{ $name }}"
           @class(['form-control', 'is-invalid' => $errors->has($name)])
           placeholder="{{ $field['placeholder'] ?? '' }}"
           @if ($type === 'number') step="any" min="0" @endif
           @if ($type === 'tel') inputmode="tel" @endif
           @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
@endif

@if ($type !== 'image')
    @error($name)
        <div class="invalid-feedback d-block" id="{{ $id }}_error">{{ $message }}</div>
    @enderror
@endif
@if (! empty($field['help']))
    <div class="form-text" id="{{ $id }}_help">{{ $field['help'] }}</div>
@endif
