<input type="hidden" name="_token" value="{{ csrf_token() }}">

<fieldset class="form-fieldset">
    <fieldset class="form-fieldset">
        <h3>What day would you like to make an override for?</h3>

        @include('components/field-date', ['classes' => 'width-full', 'name' => 'date', 'value' => $Override->date])
    </fieldset>

    <fieldset class="form-fieldset">
        <h3>During what hours will demos be available?</h3>

        <table class="table-narrow">
            <tr>
                <td>
                    @include('components/time-picker', ['name' => 'start', 'value' => $Override->start])
                </td>
                <td>
                    @include('components/time-picker', ['name' => 'end', 'value' => $Override->end])
                </td>
                <td class="table-narrow__label-cell"></td>
            </tr>

            @if ($errors->has('start'))
                <tr>
                    <td colspan="3">
                        @include('components/field-error', ['errors' => $errors, 'field' => 'start'])
                    </td>
                </tr>
            @endif

            @if ($errors->has('end'))
                <tr>
                    <td colspan="3">
                        @include('components/field-error', ['errors' => $errors, 'field' => 'end'])
                    </td>
                </tr>
            @endif

        </table>
    </fieldset>

    <fieldset class="form-fieldset">
        <h3>How many demo stations are at this location?</h3>

        @include('components/field-crementor', ['classes' => '', 'name' => 'stations', 'value' => $Override->stations])
    </fieldset>
</fieldset>


<fieldset class="centered-contents">
    <button class="button" type="submit">Save</button>
</fieldset>
