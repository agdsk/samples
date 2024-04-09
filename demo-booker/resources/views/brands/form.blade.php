<input type="hidden" name="_token" value="{{ csrf_token() }}">

<fieldset class="form-fieldset">
    <h3>Details</h3>

    @include('components/field-input', ['classes' => 'width-full', 'name' => 'name', 'value' => $Brand->name, 'placeholder' => 'Brand name'])
    @include('components/field-input', ['classes' => 'width-full', 'name' => 'slug', 'value' => $Brand->slug, 'placeholder' => 'Slug'])
</fieldset>

<fieldset class="form-fieldset">
    <h3>Event Options</h3>

    @include('components/field-input',    ['classes' => 'width-full', 'name' => 'img_logo_large_url', 'value' => $Brand->img_logo_large_url, 'placeholder' => 'Header Logo URL'])
    @include('components/field-input',    ['classes' => 'width-full', 'name' => 'img_bg_url', 'value' => $Brand->img_bg_url, 'placeholder' => 'Background Image URL'])
    @include('components/field-textarea', ['classes' => 'width-full', 'name' => 'long_text_description', 'value' => $Brand->long_text_description, 'placeholder' => 'Event Description'])
    @include('components/checkbox',       ['classes' => 'width-full', 'name' => 'show_map', 'value' => $Brand->show_map, 'label' => 'Show map on /events/' . $Brand->slug ])
</fieldset>


<fieldset class="centered-contents">
    <button class="button" type="submit">Save</button>
</fieldset>
