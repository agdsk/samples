@extends('layouts.scrolling')

@section('page-control-middle')
    <h1>Statistics</h1>
@stop

@section('main')
    @include('components/page-control')

    <table class="statistics-table">
        <tr>
            <th nowrap class="id">ID</th>
            <th nowrap>Brand</th>
            <th nowrap>Store #</th>
            <th nowrap>Name</th>
            <th nowrap>City</th>
            <th nowrap>Region</th>
            <th nowrap>Country</th>
            @foreach ($dates as $date)
                <th nowrap colspan="3">{{ date('n/j', strtotime($date)) }}</th>
            @endforeach
        </tr>
        @foreach ($Locations as $Location)
            <tr>
                <td>{{ $Location->id }}</td>
                <td nowrap class="statistics-table--left-text">{{ $Location->Brand->name }}</td>
                <td nowrap class="statistics-table--left-text">{{ $Location->vendor_id }}</td>
                <td nowrap class="statistics-table--left-text">{{ $Location->name }}</td>
                <td nowrap class="statistics-table--left-text">{{ $Location->city }}</td>
                <td nowrap class="statistics-table--left-text">{{ $Location->region }}</td>
                <td nowrap class="statistics-table--left-text">{{ $Location->country }}</td>

                @foreach ($dates as $date)
                    <td>{{ $statistics[$Location->id][$date]['website'] }}</td>
                    <td>{{ $statistics[$Location->id][$date]['walkup'] }}</td>
                    <td>{{ $statistics[$Location->id][$date]['total'] }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>
@stop
