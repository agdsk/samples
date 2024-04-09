@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('locations.index') }}"><</a>

    &nbsp;&nbsp;

    <a href="{{ env('APP_CONSUMER_SITE') }}/demo/{{ $Location->id }}" class="cute-link" target="_blank">View Website</a>

    &nbsp;&nbsp;

    <a href="{{ action('ReservationsController@location', $Location->id) }}" class="cute-link">View Appointments</a>
@stop

@section('page-control-middle')
    <h1>Edit Location</h1>
@stop

@section('page-control-right')
    <a href="{{ route('locations.edit', $Location->id) }}" class="button">Edit</a>

    @can('delete-location')
        <form method="POST" action="{{ route('locations.destroy', $Location->id) }}" style="display: inline">
            <input name="_method" type="hidden" value="DELETE">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <button type="submit" class="button button--warning"
                    onclick="return confirm('You are about to delete this location.');">Delete
            </button>
        </form>
    @endcan
@stop

@section('main')
    @include('components/page-control')

    @include('locations/row', ['Location' => $Location])

    @if (count($InvalidReservations))
        <div class="card">
            <div class="card__title">{{ count($InvalidReservations) }} Invalid {{ str_plural('Reservation', count($InvalidReservations)) }}</div>

            <p class="subtle">These reservations exist for dates or times that this location is not scheduled to operate. You can keep these reservations anyway, or cancel them. Cancelled Reservations will receive an email notifying them and inviting them to schedule a new demo.</p>

            <ul>
                @foreach ($InvalidReservations as $Reservation)
                    <li>{{ $Reservation->first_name }} {{ $Reservation->last_name }} &mdash; {{ date('D', strtotime($Reservation->date)) }}, {{ $Reservation->date }} at {{ \App\Models\Location::toTime($Reservation->time) }} &mdash; {{ $Reservation->email }}</li>
                @endforeach
            </ul>

            <form method="POST" action="{{ action('LocationsController@repair', $Location->id) }}"
                  style="display: inline">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <button type="submit" class="button button--warning"
                        onclick="return confirm('You are about to cancel these reservations.');">Cancel Reservations
                </button>
            </form>
        </div>
    @endif

    <div style="float: right; padding : 8px;  width: 50%">
        <div class="card">
            <a class="cute-link"
               href="{{ action('LocationsOverridesController@create', $Location->id) }}">Create Override</a>

            <div class="card__title">Overrides</div>

            @forelse($Location->Overrides as $Override)
                <div>
                    <form method="POST"
                          action="{{ action('LocationsOverridesController@destroy', [$Location->id, $Override->id]) }}">
                        <input name="_method" type="hidden" value="DELETE">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="image" src="{{ asset('images/x-grey.png')  }}"
                               style="float: right; margin-top: 5px;">
                    </form>

                    <span class="pronounced">{{ date('D', strtotime($Override->date)) }} {{ \App\Models\Location::toDate($Override->date) }}, </span>
                    <span class="subtle">{{ \App\Models\Location::toTime($Override->start) }} to {{ \App\Models\Location::toTime($Override->end) }}, {{ $Override->stations }} stations</span>
                </div>
            @empty
                <div class="muted">No schedule overrides</div>
            @endforelse
        </div>

        <div class="card">
            <div class="card__title">Managers</div>

            @forelse ($Location->Users->whereLoose('role', 20) as $User)
                <div>
                    <a href="{{ action('UsersController@edit', $User->id) }}">{{ $User->first_name }} {{ $User->last_name }}</a>
                </div>
            @empty
                <div class="subtle">No Managers assigned</div>
            @endforelse

            <br>

            <div class="card__title">Ambassadors</div>

            @forelse ($Location->Users->whereLoose('role', 10) as $User)
                <div>
                    <a href="{{ action('UsersController@edit', $User->id) }}">{{ $User->first_name }} {{ $User->last_name }}</a>
                </div>
            @empty
                <div class="subtle">No Ambassadors assigned</div>
            @endforelse
        </div>
    </div>

    <div style="float: left; padding : 8px; width: 50%">
        <div class="location-card">
            <div class="location-card__logo">
                <img src="{{ env('APP_CONSUMER_SITE') }}/assets/logos/{{ $Location->Brand->slug }}.png">
            </div>

            <div class="location-card__title">
                {{ $Location->name }}
            </div>

            <div class="location-card__address">
                {{ $Location->address }}<br>
                {{ $Location->city }}, {{ $Location->region }} {{ $Location->country }}
            </div>
        </div>

        <div class="card">
            <div class="card__title">
                Geographic Data
            </div>

            <div class="subtle">Timezone: {{ $Location->timezone }}</div>
            <div class="subtle">Coordinates: {{ $Location->lat }}, {{ $Location->lng }}</div>
        </div>

        <div class="card">
            <div class="card__title">
                {{ $Location->name }}
            </div>

            <div style="float: left; padding : 8px; width: 50%">
                <div class="subtle">{!! $Location->status       > 0 ? '<img src="' . asset('images/dot-green.png') . '"> Active'       : '<img src="' . asset('images/dot-grey.png') . '"> Inactive'        !!}</div>
                <div class="subtle">{!! $Location->reservations > 0 ? '<img src="' . asset('images/dot-green.png') . '"> Reservations' : '<img src="' . asset('images/dot-grey.png') . '"> No Reservations' !!}</div>
                <div class="subtle">{!! $Location->visible      > 0 ? '<img src="' . asset('images/dot-green.png') . '"> Visible'      : '<img src="' . asset('images/dot-grey.png') . '"> Hidden'          !!}</div>
            </div>

            <div style="float: left; padding : 8px; width: 50%">
                <div class="subtle">Brand: {{ $Location->Brand->name }}</div>
                <div class="subtle">Type: {{ \App\Models\Location::$available_types[$Location->type] }}</div>
                <div class="subtle">{{ \App\Models\Location::$available_languages[$Location->language] }}</div>
                <div class="subtle">Products: {{ $Location->features_list }}</div>
            </div>

            <br style="clear: both">
        </div>
    </div>

@stop