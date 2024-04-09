<input type="text" autocomplete="off" name="{{ $name }}" class="form-input {{ $errors->has($name) ? 'form-input--error' : '' }} {{ $classes }}" value="{{ old($name, $value) }}" placeholder="{{ $placeholder }}">

@include('components/field-error', ['errors' => $errors, 'field' => $name])