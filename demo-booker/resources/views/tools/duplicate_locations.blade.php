@extends('layouts.standard')

@section('page-control-middle')
    <h1>Duplicate Locations</h1>
@stop

@section('main')
    @include('components/page-control')

    @forelse ($Locations as $Location)
        @include('locations/row', ['Location' => $Location])
    @empty
        <div class="blankstate">
            There are no duplicate locations
        </div>
    @endforelse
@stop