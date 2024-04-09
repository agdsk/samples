@extends('layouts.standard')

@section('page-control-middle')
    <h1>Invalid Reservations</h1>
@stop

@section('main')
    @include('components/page-control')

    <div class="card">
        <div class="card__title">{{ count($InvalidReservations) }} Invalid {{ str_plural('Reservation', count($InvalidReservations)) }}</div>

        @if (count($InvalidReservations))
            <p class="subtle">These reservations exist for dates or times that their location is not scheduled to operate. You can keep these reservations anyway, or cancel them. Cancelled Reservations will receive an email notifying them and inviting them to schedule a new demo.</p>

            <ul>
                @foreach ($InvalidReservations as $Reservation)
                    <li>{{ $Reservation->first_name }} {{ $Reservation->last_name }} &mdash; {{ date('D', strtotime($Reservation->date)) }}, {{ $Reservation->date }} at {{ \App\Models\Location::toTime($Reservation->time) }} &mdash; {{ $Reservation->email }} &mdash;
                        <a href="{{ route('locations.show', $Reservation->Location->id) }}">{{ $Reservation->Location->name }}</a>
                    </li>
                @endforeach
            </ul>

            <form method="POST" action="" style="display: inline">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <button type="submit" class="button button--warning"
                        onclick="return confirm('You are about to cancel these reservations.');">Cancel Reservations
                </button>
            </form>
        @else
            <p class="subtle">There are no invalid reservations at this time</p>
    </div>
    @endif
@stop