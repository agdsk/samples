<input type="hidden" name="_token" value="{{ csrf_token() }}">

@can('change-user-role')
    <fieldset class="form-fieldset">
        <h3>Role</h3>

        <select name="role" class="width-full">
            @foreach (\App\Models\User::$roles as $key => $value)
                <option value="{{ $key }}" {{ $User->role == $key ? 'selected' : '' }}>{{ $value }}</option>
            @endforeach
        </select>
    </fieldset>
@endcan

<fieldset class="form-fieldset">
    <h3>Status</h3>

    <select name="status" class="width-full">
        @foreach (\App\Models\User::$available_statuses as $key => $value)
            <option {{ $User->status == $key ? 'selected' : '' }} value="{{ $key }}">{{ $value }}</option>
        @endforeach
    </select>
</fieldset>

<fieldset class="form-fieldset">
    <h3>Details</h3>

    @include('components/field-input', ['classes' => 'width-full', 'name' => 'first_name', 'value' => $User->first_name, 'placeholder' => 'First Name'])
    @include('components/field-input', ['classes' => 'width-full', 'name' => 'last_name',  'value' => $User->last_name,  'placeholder' => 'Last Name'])
    @include('components/field-input', ['classes' => 'width-full', 'name' => 'email',      'value' => $User->email,      'placeholder' => 'Email'])
</fieldset>

<fieldset class="form-fieldset">
    <h3>Locations assigned to this user</h3>

    <table class="table-narrow magic-ambassador-select-container">
        @foreach ($User->Locations as $Location)
            <tr>
                <td class="table-narrow__label-cell">Location</td>
                <td>
                    <select name="locations[]" class="magic-location-select width-medium">
                        <option value="{{ $Location->id }}" selected="selected">{{ $Location->name }}</option>
                    </select>
                </td>
                <td>
                    @include('components/x')
                </td>
            </tr>
        @endforeach

        @for ($i = 1; $i <= 3; $i++)
            <tr>
                <td class="table-narrow__label-cell">Location</td>
                <td>
                    <select name="locations[]" class="magic-location-select width-medium">
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

<fieldset class="centered-contents">
    <button class="button" type="submit">Save</button>
</fieldset>

<script type="text/javascript">
        <?php

        $location_list_options = [];
        $location_list_data = [];

        foreach ($Locations as $Location) {
            $location_list_options[] = [
                'id' => $Location->id,
                'text' => $Location->branded_name,
            ];

            $location_list_data[$Location->id] = [
                'id' => $Location->id,
                'name' => $Location->branded_name,
                'vendor_id' => $Location->vendor_id,
                'city' => $Location->city,
                'region' => $Location->region,
            ];
        }

        ?>

    var location_list_options = '<?= str_replace("'", "`", json_encode($location_list_options)); ?>';
    var location_list_data = '<?= str_replace("'", "`", json_encode($location_list_data)); ?>';
</script>