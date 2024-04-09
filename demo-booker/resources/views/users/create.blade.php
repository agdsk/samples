@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('users.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Create New User</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ route('users.store') }}">
        @include('users/form', ['User' => $User])
    </form>
@stop
