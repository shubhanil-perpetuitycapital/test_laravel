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
    <label class="form-label" for="{{ $id }}">{{ $field['label'] }}</label>
    <div class="image-field" data-image-field>
        <div class="image-field__preview {{ $name === 'logo_light' ? 'is-dark' : '' }}">
            <img src="{{ $value ? media_url($value) : '' }}" alt="" data-image-preview @if (! $value) hidden @endif>
            <span class="image-field__empty" data-image-empty @if ($value) hidden @endif><i class="bi bi-image" aria-hidden="true"></i> No image</span>
        </div>
        <input type="file" id="{{ $id }}" name="{{ $name }}" accept="image/png,image/jpeg,image/webp"
               @class(['form-control form-control-sm', 'is-invalid' => $errors->has($name)]) data-image-input
               @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        @if ($value)
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" value="1" id="{{ $id }}_remove" name="remove_{{ $name }}">
                <label class="form-check-label small" for="{{ $id }}_remove">Remove current image</label>
            </div>
        @endif
    </div>
@elseif ($type === 'textarea')
    <label class="form-label" for="{{ $id }}">{{ $field['label'] }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="3" data-preview-source="{{ $name }}"
              @class(['form-control', 'is-invalid' => $errors->has($name)])
              placeholder="{{ $field['placeholder'] ?? '' }}" @if ($required) required @endif
              @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>{{ $current }}</textarea>
@else
    <label class="form-label" for="{{ $id }}">{{ $field['label'] }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    <input type="{{ $type === 'number' ? 'number' : $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $current }}"
           data-preview-source="{{ $name }}"
           @class(['form-control', 'is-invalid' => $errors->has($name)])
           placeholder="{{ $field['placeholder'] ?? '' }}" @if ($required) required @endif
           @if ($type === 'tel') inputmode="tel" autocomplete="tel" @endif
           @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
@endif

@error($name)
    <div class="invalid-feedback d-block" id="{{ $id }}_error">{{ $message }}</div>
@enderror
@if (! empty($field['help']))
    <div class="form-text" id="{{ $id }}_help">{{ $field['help'] }}</div>
@endif
