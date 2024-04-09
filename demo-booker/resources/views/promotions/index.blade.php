@extends('layouts.standard')

@section('page-control-left')

@stop

@section('page-control-middle')
    <h1>Manage Promotions</h1>
@stop

@section('page-control-right')
    <a class="button" href="{{ route('promotions.create') }}">Create Promo Code</a>
@stop

@section('main')
    @include('components/page-control')

    @foreach ($Promotions as $Promotion)
        @include('promotions/row', ['Promotion' => $Promotion])
    @endforeach
@stop
