@extends('layouts.standard', ['layout_class' => 'dark'])

@section('main')
    <img class="centered-image" src="{{ asset('images/stadium-white.png') }}">

    <div class="behold">Acme Demo Portal</div>

    <form method="POST" class="form-narrow" action="{{ action('AuthController@postLogin') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        @include('components/field-input', ['classes' => 'width-full form-input--dark', 'name' => 'email', 'value' => '', 'placeholder' => 'Email'])

        <input class="form-input width-full form-input--dark " type="password" placeholder="Password" name="password" required />

        <fieldset class="form-fieldset centered-contents">
            <a href="{{ action('ForgotPasswordController@forgot') }}">Forgot password?</a>

            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

            <button class="button" type="submit">Log In</button>
        </fieldset>
    </form>
@endsection
