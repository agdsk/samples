<textarea autocomplete="off" rows="8" name="{{ $name }}" class="form-input {{ $errors->has($name) ? 'form-input--error' : '' }} {{ $classes }}" placeholder="{{ $placeholder }}">{{ old($name, $value) }}</textarea>

@include('components/field-error', ['errors' => $errors, 'field' => $name])
