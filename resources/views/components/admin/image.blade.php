@props(['name', 'label', 'current' => null, 'help' => null, 'removable' => true, 'dark' => false, 'required' => false])
@php $id = 'f_'.str_replace(['[', ']', '.'], '_', $name); @endphp
<label class="form-label" for="{{ $id }}">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
<div class="image-field" data-image-field>
    <div @class(['image-field__preview', 'is-dark' => $dark])>
        <img src="{{ $current ?: '' }}" alt="" data-image-preview @if (! $current) hidden @endif>
        <span class="image-field__empty" data-image-empty @if ($current) hidden @endif><i class="bi bi-image" aria-hidden="true"></i> No image</span>
    </div>
    <input type="file" id="{{ $id }}" name="{{ $name }}" accept="image/png,image/jpeg,image/webp"
           @class(['form-control form-control-sm', 'is-invalid' => $errors->has($name)]) data-image-input>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($current && $removable)
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="{{ $id }}_remove" name="remove_{{ $name }}">
            <label class="form-check-label small" for="{{ $id }}_remove">Remove current image</label>
        </div>
    @endif
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
</div>
