<img src="{{ asset('images/minus.png') }}" class="field-decrementer">
<input name="{{ $name }}" class="form-input width-short centered {{ $classes }}" type="number" min="0" value="{{ old($name) ? old($name) : $value }}">
<img src="{{ asset('images/plus.png') }}" class="field-incrementer">

@include('components/field-error', ['errors' => $errors, 'field' => $name])