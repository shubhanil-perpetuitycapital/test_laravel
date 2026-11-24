@props(['name', 'label', 'value' => null, 'help' => null, 'required' => false, 'rows' => 3])
@php $id = 'f_'.str_replace(['[', ']', '.'], '_', $name); @endphp
<label class="form-label" for="{{ $id }}">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
<textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
          {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}
          @if ($required) required @endif
          @if ($help) aria-describedby="{{ $id }}_help" @endif>{{ old($name, $value) }}</textarea>
@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
@if ($help)<div class="form-text" id="{{ $id }}_help">{{ $help }}</div>@endif
