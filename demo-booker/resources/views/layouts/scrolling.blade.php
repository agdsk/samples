@include('layouts/partials/header')

@if (session('message'))
    <div class="flash-message">
        {{ session('message') }}
    </div>
@endif

@yield('main')

@include('layouts/partials/footer')