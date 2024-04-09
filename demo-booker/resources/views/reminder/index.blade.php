@extends('layouts.standard')

@section('page-control-middle')
    <h1>Send Reminder</h1>
@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ action('ReminderController@create') }}">
        <p class="subtle">You are signing up to be put on a mailing list to be reminded to make a reservation on live.acme.com.</p>

        <fieldset class="form-fieldset">
            @include('components/field-input', ['classes' => 'width-full','name' => 'email',      'value' => '', 'placeholder' => 'Email address'])
            @include('components/field-input', ['classes' => 'width-full','name' => 'first_name', 'value' => '', 'placeholder' => 'First Name'])
            @include('components/field-input', ['classes' => 'width-full','name' => 'last_name',  'value' => '', 'placeholder' => 'Last Name'])
        </fieldset>

        <fieldset class="centered-contents">
            <button class="button" type="submit">Send</button>
        </fieldset>
    </form>
@stop
