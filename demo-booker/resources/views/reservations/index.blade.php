@extends('layouts.standard', ['layout_class' => 'dark'])

@section('main')

    <div style="margin-top: 64px"></div>

    @forelse ($Locations as $Location)
        <a class="location-cardlet" href="{{ action('ReservationsController@location', $Location->id) }}">
            {{ $Location->branded_name }} &mdash; {{ $Location->fullCity }}
        </a>
    @empty
        <div class="blankstate">
            You are not assigned to any locations
        </div>
    @endforelse
@stop
