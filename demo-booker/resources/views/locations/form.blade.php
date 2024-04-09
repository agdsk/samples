<script id="schedules-data" type="application/json">
    <?= json_encode(array_values(old('schedules', $schedules_array)), JSON_PRETTY_PRINT); ?>
</script>

<input type="hidden" name="_token" value="{{ csrf_token() }}">

<div style="float: left; padding: 32px; width: 50%">
    @can('administer-location')
        <fieldset class="form-fieldset">
            <h3>Options</h3>
            @include('components/checkbox', ['classes' => 'width-full', 'name' => 'status',   'value' => $Location->status,  'label' => 'Location is active'])
            @include('components/checkbox', ['classes' => 'width-full', 'name' => 'reservations', 'value' => $Location->visible, 'label' => 'Allows reservations'])
            @include('components/checkbox', ['classes' => 'width-full', 'name' => 'visible',      'value' => $Location->visible, 'label' => 'Visible in search results'])
        </fieldset>

        <fieldset class="form-fieldset">
            <h3>Brand</h3>

            <select name="brand_id" class="width-full">
                @foreach ($Brands as $Brand)
                    <option {{ $Location->brand_id == $Brand->id ? 'selected' : '' }} value="{{ $Brand->id }}">{{ $Brand->name }}</option>
                @endforeach
            </select>

            @include('components/field-error', ['errors' => $errors, 'field' => 'brand_id'])
        </fieldset>

        <fieldset class="form-fieldset">
            <h3>Type</h3>

            <select name="type" class="width-full">
                @foreach (\App\Models\Location::$available_types as $k => $v)
                    <option {{ $Location->type == $k ? 'selected' : '' }} value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>

            @include('components/field-error', ['errors' => $errors, 'field' => 'type'])
        </fieldset>
    @endcan

    <fieldset class="form-fieldset">
        <h3>Language</h3>

        <select name="language" class="width-full">
            @foreach (\App\Models\Location::$available_languages as $k => $v)
                <option {{ $Location->language == $k ? 'selected' : '' }} value="{{ $k }}">{{ $v }}</option>
            @endforeach
        </select>

        @include('components/field-error', ['errors' => $errors, 'field' => 'language'])
    </fieldset>

    <fieldset class="form-fieldset">
        <h3>Address</h3>
        @include('components/field-input', ['classes' => 'width-full', 'name' => 'name',       'value' => $Location->name,       'placeholder' => 'Location Name'])
        @include('components/field-input', ['classes' => 'width-full', 'name' => 'vendor_id',  'value' => $Location->vendor_id,  'placeholder' => 'Vendor ID (Store number, etc)'])
        @include('components/field-input', ['classes' => 'width-full', 'name' => 'address',    'value' => $Location->address,    'placeholder' => 'Address'])
        @include('components/field-input', ['classes' => 'width-full', 'name' => 'address2',   'value' => $Location->address2,   'placeholder' => 'Building, Suite, Booth, etc.'])
        @include('components/field-input', ['classes' => 'width-full', 'name' => 'city',       'value' => $Location->city,       'placeholder' => 'City'])
        @include('components/field-input', ['classes' => 'width-full', 'name' => 'region',     'value' => $Location->region,     'placeholder' => 'Region'])
        @include('components/field-input', ['classes' => 'width-full', 'name' => 'country',    'value' => $Location->country,    'placeholder' => 'Country'])
        @include('components/field-input', ['classes' => 'width-full', 'name' => 'postalCode', 'value' => $Location->postalCode, 'placeholder' => 'Postal Code'])
    </fieldset>

    <fieldset class="form-fieldset">
        <h3>Products</h3>

        @include('components/checkblock', ['name' => 'feature_gearvr', 'text' => 'Gear VR', 'type' => 'checkbox', 'value' => 1, 'active' => $Location->feature_gearvr])
        @include('components/checkblock', ['name' => 'feature_rift',   'text' => 'Rift',    'type' => 'checkbox', 'value' => 1, 'active' => $Location->feature_rift])
        @include('components/checkblock', ['name' => 'feature_touch',  'text' => 'Touch',   'type' => 'checkbox', 'value' => 1, 'active' => $Location->feature_touch])
    </fieldset>
</div>

<div style="float: right; padding: 32px; width: 50%">
    <fieldset class="form-fieldset">
        <h3>Managers</h3>

        <table class="table-narrow magic-manager-select-container">
            @foreach ($Location->Users->whereLoose('role', 20) as $User)
                <tr>
                    <td class="table-narrow__label-cell">Manager</td>
                    <td>
                        <select name="users[]" class="magic-manager-select width-medium">
                            <option value="{{ $User->id }}"
                                    selected="selected">{{ $User->first_name }} {{ $User->last_name }}</option>
                        </select>
                    </td>
                    <td>
                        @include('components/x')
                    </td>
                </tr>
            @endforeach

            @for ($i = 1; $i <= 2; $i++)
                <tr>
                    <td class="table-narrow__label-cell">Manager</td>
                    <td>
                        <select name="users[]" class="magic-manager-select width-medium">
                            <option value=""></option>
                        </select>
                    </td>
                    <td>
                        @include('components/x')
                    </td>
                </tr>
            @endfor
        </table>
    </fieldset>

    <fieldset class="form-fieldset">
        <h3>Ambassadors</h3>

        <table class="table-narrow magic-ambassador-select-container">
            @foreach ($Location->Users->whereLoose('role', 10) as $User)
                <tr>
                    <td class="table-narrow__label-cell">Ambassador</td>
                    <td>
                        <select name="users[]" class="magic-ambassador-select width-medium">
                            <option value="{{ $User->id }}"
                                    selected="selected">{{ $User->first_name }} {{ $User->last_name }}</option>
                        </select>
                    </td>
                    <td>
                        @include('components/x')
                    </td>
                </tr>
            @endforeach

            @for ($i = 1; $i <= 4; $i++)
                <tr>
                    <td class="table-narrow__label-cell">Ambassador</td>
                    <td>
                        <select name="users[]" class="magic-ambassador-select width-medium">
                            <option value=""></option>
                        </select>
                    </td>
                    <td>
                        @include('components/x')
                    </td>
                </tr>
            @endfor
        </table>
    </fieldset>
</div>

<div id="react-scheduler"></div>

<fieldset class="form-fieldset centered-contents" style="clear: both">
    <button class="button" type="submit">Save</button>
</fieldset>

<script type="text/javascript">
        <?php

        $manager_list_options = [];
        $manager_list_data = [];

        foreach ($Users as $User) {
            if ($User->role != 20) {
                continue;
            }

            $manager_list_options[] = [
                'id' => $User->id,
                'text' => $User->first_name . ' ' . $User->last_name . ' (' . $User->email . ')'
            ];

            $manager_list_data[$User->id] = [
                'id' => $User->id,
                'first_name' => $User->first_name,
                'last_name' => $User->last_name,
                'email' => $User->email,
            ];
        }

        ?>

    var manager_list_options = '<?= str_replace("'", "`", json_encode($manager_list_options)); ?>';
    var manager_list_data = '<?= str_replace("'", "`", json_encode($manager_list_data)); ?>';
</script>

<script type="text/javascript">
        <?php

        $ambassador_list_options = [];
        $ambassador_list_data = [];

        foreach ($Users as $User) {
            if ($User->role != 10) {
                continue;
            }

            $ambassador_list_options[] = [
                'id' => $User->id,
                'text' => $User->first_name . ' ' . $User->last_name . ' (' . $User->email . ')'
            ];

            $ambassador_list_data[$User->id] = [
                'id' => $User->id,
                'first_name' => $User->first_name,
                'last_name' => $User->last_name,
                'email' => $User->email,
            ];
        }

        ?>

    var ambassador_list_options = '<?= str_replace("'", "`", json_encode($ambassador_list_options)); ?>';
    var ambassador_list_data = '<?= str_replace("'", "`", json_encode($ambassador_list_data)); ?>';
</script>
