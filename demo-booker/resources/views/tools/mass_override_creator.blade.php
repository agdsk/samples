@extends('layouts.standard')

@section('page-control-middle')
    <h1>Mass Override Creator</h1>
@stop

@section('main')
    @include('components/page-control')

    <form method="POST" class="form-narrow" action="{{ action('ToolsController@massOverrideCreate') }}">
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <fieldset class="form-fieldset">
            <fieldset class="form-fieldset">
                <h3>What brand?</h3>

                <select name="brand_id" class="width-full">
                    @foreach ($Brands as $Brand)
                        <option value="{{ $Brand->id }}">{{ $Brand->name }}</option>
                    @endforeach
                </select>
            </fieldset>

            <fieldset class="form-fieldset">
                <h3>What day would you like to make an override for?</h3>

                @include('components/field-date', ['classes' => 'width-full', 'name' => 'date', 'value' => null])
            </fieldset>

            <fieldset class="form-fieldset">
                <h3>During what hours will demos be available?</h3>

                <table class="table-narrow">
                    <tr>
                        <td>
                            @include('components/time-picker', ['name' => 'start', 'value' => null])
                        </td>
                        <td>
                            @include('components/time-picker', ['name' => 'end', 'value' => null])
                        </td>
                        <td class="table-narrow__label-cell"></td>
                    </tr>

                    @if ($errors->has('start'))
                        <tr>
                            <td colspan="3">
                                @include('components/field-error', ['errors' => $errors, 'field' => 'start'])
                            </td>
                        </tr>
                    @endif

                    @if ($errors->has('end'))
                        <tr>
                            <td colspan="3">
                                @include('components/field-error', ['errors' => $errors, 'field' => 'end'])
                            </td>
                        </tr>
                    @endif

                </table>
            </fieldset>

            <fieldset class="form-fieldset">
                <h3>How many demo stations are at this location?</h3>

                @include('components/field-crementor', ['classes' => '', 'name' => 'stations', 'value' => 0])
            </fieldset>
        </fieldset>


        <fieldset class="centered-contents">
            <button class="button" type="submit">Save</button>
        </fieldset>
    </form>
@stop