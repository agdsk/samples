@extends('layouts.standard')

@section('main')
    <div class="script-title">
        {{ $Script->name }}
    </div>

    <div class="script-body">
        {!! $Script->body !!}
    </div>
@stop
