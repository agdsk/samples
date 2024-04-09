@extends('layouts.wide')

@section('page-control-left')
    <a class="button" href="{{ route('locations.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Create New Location</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" action="{{ route('locations.store') }}">
        @include('locations/form', ['Location' => $Location])
    </form>
@stop
