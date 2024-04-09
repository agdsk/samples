@extends('layouts.standard', ['layout_class' => 'dark'])

@section('main')
    <img class="centered-image" src="{{ asset('images/stadium-white.png') }}">

    <div class="behold">Acme Demo Portal</div>

    <form method="POST" class="form-narrow"  action="{{ action('ForgotPasswordController@forgotPost') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        @if (session('message'))
            <p class="muted">{{ session('message') }}</p>
        @endif

        @include('components/field-input', ['classes' => 'width-full form-input--dark', 'name' => 'email', 'value' => '', 'placeholder' => 'Email'])

        <fieldset class="form-fieldset centered-contents">
            <button class="button" type="submit">Send Password Reset Link</button>
        </fieldset>
    </form>
@endsection
