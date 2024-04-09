@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('brands.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Create New Brand</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ route('brands.store') }}">
        @include('brands/form', ['Brand' => $Brand])
    </form>
@stop
