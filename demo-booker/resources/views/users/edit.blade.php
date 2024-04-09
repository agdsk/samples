@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ route('users.index') }}"><</a>
@stop

@section('page-control-middle')
    <h1>Edit User</h1>
@stop

@section('page-control-right')
    @can('delete-user')
        <form method="POST" action="{{ route('users.destroy', $User->id) }}">
            <input name="_method" type="hidden" value="DELETE">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <button type="submit" class="button button--warning" onclick="return confirm('You are about to delete this user.');">Delete</button>
        </form>
    @endcan
@stop

@section('main')
    @include('components/page-control')

    @include('users/row', ['User' => $User])

    <form method="POST" class="form-narrow" action="{{ route('users.update', $User->id) }}">
        <input name="_method" type="hidden" value="PUT">
        @include('users/form', ['User' => $User])
    </form>
@stop
