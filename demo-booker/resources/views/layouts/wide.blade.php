@include('layouts/partials/header')

<div class="trunk trunk--wide">
    @if (session('message'))
        <div class="flash-message">
            {{ session('message') }}
        </div>
    @endif

    @yield('main')
</div>

@include('layouts/partials/footer')