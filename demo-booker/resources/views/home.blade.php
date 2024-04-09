@extends('layouts.standard', ['layout_class' => 'dark'])

@section('main')
    @if(Auth::user()->isManager())
        <a href="{{ route('locations.index')  }}" class="home-button">
            <img src="{{ asset('images/location.png') }}">
            Manage Locations
        </a>
    @endif

    @if(Auth::user()->isManager())
        <a href="{{ route('users.index')  }}" class="home-button">
            <img src="{{ asset('images/avatar.png') }}">
            Manage Users
        </a>
    @endif

    @if (Auth::user()->isAdmin())
        <a href="{{ route('promotions.index') }}" class="home-button">
            <img src="{{ asset('images/star.png') }}">
            Promo Codes
        </a>
    @endif

    @if (false && Auth::user()->isAdmin())
        <a href="{{ '' }}" class="home-button blur">
            <img src="{{ asset('images/connecting.png') }}">
            Featured Events
        </a>
    @endif

    @if (Auth::user()->role == 10)
        <a href="{{ action(('ReservationsController@index')) }}" class="home-button">
            <img src="{{ asset('images/calendar.png') }}">
            Appointments
        </a>
    @endif

    @if (Auth::user()->isAdmin())
        <a href="{{ action('ScriptsController@index') }}" class="home-button">
            <img src="{{ asset('images/news.png') }}">
            Manage Scripts
        </a>
    @endif
@stop
