@props(['name'])
@error($name)
    <div class="invalid-feedback d-block" id="{{ str_replace(['[', ']', '.'], '_', $name) }}_error">{{ $message }}</div>
@enderror
