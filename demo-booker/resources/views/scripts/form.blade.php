<input type="hidden" name="_token" value="{{ csrf_token() }}">

<fieldset class="form-fieldset">
    <h3>Details</h3>

    @include('components/field-input', ['classes' => 'width-full','name' => 'name', 'value' => $Script->name, 'placeholder' => 'Script name'])

    <textarea name="body" class="wysiwyg">{{ $Script->body }}</textarea>
</fieldset>

<fieldset class="centered-contents">
    <button class="button" type="submit">Save</button>
</fieldset>