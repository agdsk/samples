<input type="hidden" name="_token" value="{{ csrf_token() }}">

<fieldset class="form-fieldset">
    <h3>Promo code name / ID (this is what a user will type in)</h3>

    @include('components/field-input', ['classes' => 'width-full', 'name' => 'code', 'value' => $Promotion->code, 'placeholder' => 'Promo code'])
</fieldset>

<select name="location_id" class="magic-location-select width-full">
    @if ($Promotion->Location)
        <option value="{{ $Promotion->Location->id }}" selected="selected">{{ $Promotion->Location->name }}</option>
    @endif
</select>

<fieldset class="form-fieldset">
    <h3>For which days can this code be used?</h3>

    @include('components/field-date', ['classes' => 'width-full', 'name' => 'start', 'value' => $Promotion->start])
    @include('components/field-date', ['classes' => 'width-full', 'name' => 'end',   'value' => $Promotion->end])
</fieldset>

<fieldset class="form-fieldset">
    <h3>How many demos per time slot will this code unlock?</h3>

    @include('components/field-crementor', ['classes' => '', 'name' => 'size', 'value' => $Promotion->size])
</fieldset>

<fieldset class="form-fieldset">
    <h3>How many times can this code be redeemed?</h3>

    @include('components/field-crementor', ['classes' => '', 'name' => 'limit', 'value' => $Promotion->limit])
</fieldset>

<fieldset class="centered-contents">
    <button class="button" type="submit">Save</button>
</fieldset>

<script type="text/javascript">
    <?php

    $location_list_options = [];
    $location_list_data    = [];

    foreach ($Locations as $Location) {
        $location_list_options[] = [
            'id'         => $Location->id,
            'text'       => $Location->branded_name,
        ];

        $location_list_data[$Location->id] = [
            'id'        => $Location->id,
            'name'      => $Location->branded_name,
            'vendor_id' => $Location->vendor_id,
            'city'      => $Location->city,
            'region'    => $Location->region,
        ];
    }

    ?>

    var location_list_options = '<?= str_replace("'", "`", json_encode($location_list_options)); ?>';
    var location_list_data    = '<?= str_replace("'", "`", json_encode($location_list_data)); ?>';
</script>