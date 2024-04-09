<!doctype html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="//cdn.jsdelivr.net/webshim/1.12.4/extras/modernizr-custom.js"></script>
    <script src="//cdn.jsdelivr.net/webshim/1.12.4/polyfiller.js"></script>

    <script src="{{ asset('/js/vendor.js') }}"></script>
    <script src="{{ asset('/js/app.js') }}"></script>

    <script>
        webshims.setOptions('waitReady', false);
        webshims.setOptions('forms-ext', {types: 'date'});
        webshims.polyfill('forms forms-ext');
    </script>

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <link xmlns="http://www.w3.org/1999/xhtml" rel="icon" type="image/x-icon" href="/images/favicon.ico" />

    <link href="{{ asset('/css/vendor.css') }}" rel="stylesheet" />
    <link href="{{ asset('/css/app.css') }}" rel="stylesheet" />
</head>

<body class="{{ isset($layout_class) ? 'layout-' . $layout_class : ''}}">

@include('components/header')