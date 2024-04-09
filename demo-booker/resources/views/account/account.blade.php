@extends('layouts.standard')

@section('page-control-left')
    <a class="button" href="{{ url('/') }}"><</a>
@stop

@section('page-control-middle')
    <h1>My Account</h1>
@stop

@section('page-control-right')

@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ action('AccountController@passwordPost') }}">
        <fieldset class="form-fieldset">
            <h3>Change your password</h3>

            @include('components/field-password', ['classes' => 'width-full', 'name' => 'password', 'placeholder' => 'Your new password'])
            @include('components/field-password', ['classes' => 'width-full', 'name' => 'password_confirmation', 'placeholder' => 'Your new password again'])
        </fieldset>

        <fieldset class="centered-contents">
            <button class="button" type="submit">Save</button>
        </fieldset>
    </form>
@stop
