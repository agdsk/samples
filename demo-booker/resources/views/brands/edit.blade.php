@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('brands.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Edit Brand</h1>
@stop

@section('page-control-right')
    <form method="POST" action="{{ route('brands.destroy', $Brand->id) }}">
        <input name="_method" type="hidden" value="DELETE">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <button type="submit" class="button button--warning" onclick="return confirm('You are about to delete this brand.');">Delete</button>
    </form>
@stop

@section('main')
    @include('components/page-control')

    @include('brands/row', ['Brand' => $Brand])

    <form method="POST" class="form-narrow" action="{{ route('brands.update', $Brand->id) }}">
        <input name="_method" type="hidden" value="PUT">
        @include('brands/form', ['Brand' => $Brand])
    </form>
@stop
