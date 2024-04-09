@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('scripts.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Edit Script</h1>
@stop

@section('page-control-right')
    <form method="POST" action="{{ route('scripts.destroy', $Script->id) }}">
        <input name="_method" type="hidden" value="DELETE">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <button type="submit" class="button button--warning" onclick="return confirm('You are about to delete this script.');">Delete</button>
    </form>
@stop

@section('main')
    @include('components/page-control')

    @include('scripts/row', ['Script' => $Script])

    <form method="POST" class="form-narrow" action="{{ route('scripts.update', $Script->id) }}">
        <input name="_method" type="hidden" value="PUT">
        @include('scripts/form', ['Script' => $Script])
    </form>
@stop
