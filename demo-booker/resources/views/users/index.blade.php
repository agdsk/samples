@extends('layouts.standard')

@section('page-control-left')

@stop

@section('page-control-middle')
    <h1>Manage Users</h1>
@stop

@section('page-control-right')
    <a class="button" href="{{ route('users.create') }}">New User</a>
@stop

@section('main')
    @include('components/page-control')

    @foreach ($Users as $User)
        @include('users/row', ['User' => $User])
    @endforeach
@stop
