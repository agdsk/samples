<input type="date" name="{{ $name }}" class="form-input {{ $errors->has($name) ? 'form-input--error' : '' }} {{ $classes }}" value="{{ old($name) ? old($name) : $value }}">

@include('components/field-error', ['errors' => $errors, 'field' => $name])