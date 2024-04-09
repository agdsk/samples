@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('promotions.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Create New Promotion</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ route('promotions.store') }}">
        @include('promotions/form', ['Promotion' => $Promotion])
    </form>
@stop
