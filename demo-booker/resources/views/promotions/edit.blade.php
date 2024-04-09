@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('promotions.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Edit Promotion</h1>
@stop

@section('page-control-right')
    <form method="POST" action="{{ route('promotions.destroy', $Promotion->id) }}">
        <input name="_method" type="hidden" value="DELETE">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <button type="submit" class="button button--warning" onclick="return confirm('You are about to delete this promotion.');">Delete</button>
    </form>
@stop

@section('main')
    @include('components/page-control')

    @include('promotions/row', ['Promotion' => $Promotion])

    <form method="POST" class="form-narrow" action="{{ route('promotions.update', $Promotion->id) }}">
        <input name="_method" type="hidden" value="PUT">
        @include('promotions/form', ['Promotion' => $Promotion])
    </form>
@stop
