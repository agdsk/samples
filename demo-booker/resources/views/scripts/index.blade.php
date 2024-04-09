@extends('layouts.standard')

@section('page-control-left')

@stop

@section('page-control-middle')
    <h1>Manage Scripts</h1>
@stop

@section('page-control-right')
    <a class="button" href="{{ route('scripts.create') }}">New Script</a>
@stop

@section('main')
    @include('components/page-control')

    @foreach ($Scripts as $Script)
        @include('scripts/row', ['Script' => $Script])
    @endforeach
@stop
