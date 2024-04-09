@extends('layouts.standard')

@section('page-control-left')
    @if (Auth::user()->isAdmin())

        <select class="location-filter" name="location_region_filter">
            <option value="">Select Region</option>
            @foreach ($regions as $region)
                <option value="{{ $region->region }}" {{ $region_selected == $region->region ? 'selected' : '' }}>{{ (string) $region->region }}</option>
            @endforeach
        </select>

        <select class="location-filter" name="location_brand_filter">
            <option value="">Select Brand</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" {{ $brand_selected == $brand->id ? 'selected' : '' }}>{{ (string) $brand->name }}</option>
            @endforeach
        </select>
    @endif
@stop

@section('page-control-middle')
    <h1>Manage Locations</h1>
@stop

@section('page-control-right')
    @can('create-location')
        <a class="button" href="{{ route('locations.create') }}">New Location</a>
    @endcan
@stop

@section('main')
    @include('components/page-control')

    @forelse ($Locations as $Location)
        @include('locations/row', ['Location' => $Location])
    @empty
        <div class="blankstate">
            You are not assigned to any locations
        </div>
    @endforelse
@stop
