@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ action('ReservationsController@location', [$Location->id, $date_prev]) }}"><</a>
@stop

@section('page-control-middle')
    <h1>{{ $Location->branded_name }}</h1>
    <h3>{{ $date_string }}</h3>
@stop

@section('page-control-right')
    <a class="button" href="{{ action('ReservationsController@location', [$Location->id, $date_next]) }}">></a>
@stop

@section('main')
    @include('components/page-control')

    {{-- Cancel Modal -------------------------------------------------------}}

    <div class="modal" id="reservation-cancellation-modal">
        <div class="modal__title">Are you sure you want to remove this person from their appointment?</div>

        <p>This user will be deleted from the system and their appointment will be forfeited. An email will be sent to notify them of the recent cancellation.</p>

        <div class="button-pair">
            <button class="button button--taller button--black modal-close-button">Cancel</button>

            <button class="button button--taller timeslot__reservation__cancel-commit-button"
                    data-url="{{ action('ReservationsController@cancelReservation') }}">Proceed
            </button>
        </div>
    </div>

    {{-- Script Modals --------------------------------------------------------}}

    @foreach ($Scripts as $Script)
        <div class="modal modal--wide" id="reservation-script-modal-{{ $Script->id }}">
            <div class="modal__button-set centered-contents">
                <button class="button button--taller modal-close-button">Close</button>
            </div>

            <div class="script-title">
                {{ $Script->name }}
            </div>

            <div class="script-body">
                {!! $Script->body !!}
            </div>
        </div>
    @endforeach

    <p class="subtle centered-contents">
        {{ $stats['total'] }} {{ str_plural('reservation', $stats['total']) }}

        @if ($stats['total'] > 0)
            &mdash;
        <img src="{{ asset('images/dot-green.png') }}"> {{ $stats['website'] }} website
        <img src="{{ asset('images/dot-blue.png') }}"> {{ $stats['walkup'] }} walkup
        @endif
    </p>

    @forelse ($schedule as $time)
        <div class="timeslot">
            <div class="timeslot__time">
                <div class="timeslot__time__dots">
                    @if (array_key_exists($time, $Reservations))
                        @foreach($Reservations[$time] as $Reservation)
                            @if ($Reservation->source == 'website')
                                <img src="{{ asset('images/dot-green.png') }}">
                            @elseif ($Reservation->source == 'walkup')
                                <img src="{{ asset('images/dot-blue.png') }}">
                            @else
                                <img src="{{ asset('images/dot-grey.png') }}">
                            @endif
                        @endforeach
                    @endif
                </div>

                <h3>{{ \App\Models\Location::toTime($time) }}</h3>
            </div>
            @if (array_key_exists($time, $Reservations))
                @foreach ($Reservations[$time] as $Reservation)
                    <div class="timeslot__reservation timeslot__reservation--{{ $Reservation->status_slug }}">
                        {{--<div class="timeslot__reservation__cancel-initiate-button" data-reservation="{{ $Reservation->id }}">--}}
                        {{--<img src="{{ asset('images/red-x.png') }}">--}}
                        {{--</div>--}}

                        <div class="timeslot__reservation__guest" style="margin-left: 20px;">
                            {{ $Reservation->safe_name }}
                        </div>

                        <div class="timeslot__reservation__buttons button-pair">
                            <button data-reservation="{{ $Reservation->id }}"
                                    data-url="{{ action('ReservationsController@checkinReservation') }}"
                                    class="button button--taller timeslot__reservation__checkin-commit-button ">Check In
                            </button>
                            <button data-reservation="{{ $Reservation->id }}"
                                    data-url="{{ action('ReservationsController@demoReservation')    }}"
                                    class="button button--taller timeslot__reservation__demo-commit-button    ">Give Demo
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
            <div class="timeslot__reservation">
                <div class="timeslot__reservation__add-initiate-button">
                    <img src="{{ asset('images/plus-blue.png') }}">
                </div>

                <div class="timeslot__reservation__guest">
                    <a href="{{ action('ReservationsController@createReservation', [$Location->id, 'date' => $date, 'time' => $time]) }}">Add a person</a>
                </div>

                <div class="timeslot__reservation__buttons button-pair">
                    <button class="button button--taller button--helpful timeslot__reservation__script-initiate-button">View Script</button>
                </div>
            </div>
        </div>
    @empty
        <div class="blankstate">
            This location is not open this day
        </div>
    @endforelse
@stop
