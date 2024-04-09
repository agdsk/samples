<input type="password" name="{{ $name }}" class="form-input {{ $errors->has($name) ? 'form-input--error' : '' }} {{ $classes }}" placeholder="{{ $placeholder }}">

@include('components/field-error', ['errors' => $errors, 'field' => $name])