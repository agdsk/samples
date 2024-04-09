@extends('layouts.wide')

@section('page-control-left')
    <a class="button" href="{{ route('locations.show', $Location->id) }}"><</a>
@stop

@section('page-control-middle')
    <h1>Edit Location</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" action="{{ route('locations.update', $Location->id) }}">
        <input name="_method" type="hidden" value="PUT">
        @include('locations/form', ['Location' => $Location])
    </form>
@stop
