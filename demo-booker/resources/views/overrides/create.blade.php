@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('overrides.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Create New Override</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ route('overrides.store') }}">
        @include('overrides/form', ['Override' => $Override])
    </form>
@stop
