@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('scripts.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Create New Script</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ route('scripts.store') }}">
        @include('scripts/form', ['Script' => $Script])
    </form>
@stop
