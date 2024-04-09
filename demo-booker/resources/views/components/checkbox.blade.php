<label style="display:block" class="form-input {{ $errors->has($name) ? 'form-input--error' : '' }} {{ $classes }}">
	<input type="hidden" name="{{ $name }}" value="0">
	<input type="checkbox" {{ $value == 1 ? 'checked' : '' }} autocomplete="off" name="{{ $name }}" value="1">&nbsp;
	{{$label}}
</label>

@include('components/field-error', ['errors' => $errors, 'field' => $name])
