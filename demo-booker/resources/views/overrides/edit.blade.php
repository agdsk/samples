@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('overrides.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Edit Override</h1>
@stop

@section('page-control-right')
    <form method="POST" action="{{ route('overrides.destroy', $Override->id) }}">
        <input name="_method" type="hidden" value="DELETE">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <button type="submit" class="button button--warning" onclick="return confirm('You are about to delete this override.');">Delete</button>
    </form>
@stop

@section('main')
    @include('components/page-control')

    @include('overrides/row', ['Override' => $Override])

    <form method="POST" class="form-narrow" action="{{ route('overrides.update', $Override->id) }}">
        <input name="_method" type="hidden" value="PUT">
        @include('overrides/form', ['Override' => $Override])
    </form>
@stop
