@extends('layouts.standard', ['layout_class' => 'dark'])

@section('main')
    <img class="centered-image" src="{{ asset('images/stadium-white.png') }}">

    <div class="behold">Acme Demo Portal</div>

    <form method="POST" class="form-narrow"  action="{{ action('ForgotPasswordController@passwordPost') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="token" value="{{ $token }}">

        <fieldset>
            <label>E-Mail Address</label>
            <input type="email" required name="email" value="{{ old('email') }}">
        </fieldset>

        <fieldset>
            <label>Password</label>
            <input type="password" required class="form-control" name="password">
        </fieldset>

        <fieldset>
            <label>Confirm Password</label>
            <input type="password" required class="form-control" name="password_confirmation">
        </fieldset>

        <button type="submit">Reset Password</button>
    </form>
@endsection
