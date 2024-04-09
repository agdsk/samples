@extends('layouts.standard')

@section('page-control-left')

@stop

@section('page-control-middle')
    <h1>Manage Brands</h1>
@stop

@section('page-control-right')
    <a class="button" href="{{ route('brands.create') }}">New Brand</a>
@stop

@section('main')
    @include('components/page-control')

    @foreach ($Brands as $Brand)
        @include('brands/row', ['Brand' => $Brand])
    @endforeach
@stop
