@extends('layouts.standard')

@section('page-control-left')

@stop

@section('page-control-middle')
    <h1>Manage Overrides</h1>
@stop

@section('page-control-right')
    <a class="button" href="{{ route('overrides.create') }}">New Override</a>
@stop

@section('main')
    @include('components/page-control')

    @foreach ($Overrides as $Override)
        @include('overrides/row', ['Override' => $Override])
    @endforeach
@stop
