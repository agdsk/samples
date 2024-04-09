@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('locations.show', $Location->id) }}"><</a>
@stop

@section('page-control-middle')
    <h1>Create Override</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ action('LocationsOverridesController@create', $Location->id) }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="location_id" value="{{ $Location->id }}">

        @if (isset($Reservations))
            <fieldset class="form-fieldset">
                <input type="hidden" name="date" value="{{ old('date') }}">
                <input type="hidden" name="start" value="{{ old('start') }}">
                <input type="hidden" name="end" value="{{ old('end') }}">
                <input type="hidden" name="stations" value="{{ old('stations') }}">

                <h2>Your Override Has Not Been Saved</h2>

                <p>The following reservations exist for the impacted time slots</p>

                <table>
                    @foreach ($Reservations as $Reservation)
                        <tr>
                            <td class="subtle" style="padding-right: 16px;">
                                {{ \App\Models\Location::toTime($Reservation->time) }}
                            </td>
                            <td>
                                {{ $Reservation->first_name }} {{ $Reservation->last_name }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </fieldset>

            <h3>You can choose to keep these reservations or cancel them</h3>

            <p class="subtle">Keeping the reservations will allow these users to attend their reservation as scheduled.</p>

            <p class="subtle">Canceling the reservations will remove these users from their scheduled timeslot and notify them via email about their cancellation.</p>

            <h3>What would you like to do with the reservations impacted?</h3>

            <fieldset class="form-fieldset">

                <select name="behavior">
                    <option>Choose...</option>
                    <option value="keep">Keep the reservations</option>
                    <option value="cancel">Cancel the reservations</option>
                </select>

            </fieldset>

            <button class="button" type="submit">Save</button>

        @else
            <fieldset class="form-fieldset">
                <h3>What day would you like to make an override for?</h3>

                @include('components/field-date', ['classes' => 'width-full', 'name' => 'date', 'value' => date('Y-m-d')])
            </fieldset>

            <fieldset class="form-fieldset">
                <h3>During what hours will demos be available?</h3>

                <table class="table-narrow">
                    <tr>
                        <td>
                            @include('components/time-picker', ['name' => 'start', 'value' => null])
                        </td>
                        <td>
                            @include('components/time-picker', ['name' => 'end', 'value' => null])
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

                @include('components/field-crementor', ['classes' => '', 'name' => 'stations', 'value' => 0])
            </fieldset>

            <button class="button" type="submit">Save</button>
        @endif
    </form>
@stop
