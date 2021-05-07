<!doctype html>
<html class="no-js" lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __(config('app.name', 'Volga Acco')) }}@yield('title')</title>
    <!-- Scripts -->
    <script src="{{ mix('js/app.js') }}" defer></script>
    @yield('scripts')
    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Raleway:300,400,600" rel="stylesheet" type="text/css">
    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
</head>
<body>
@guest
    <div class="auth">
        @yield('content')
    </div>
@else
    <div class="main-wrapper">
        <div class="app" id="app">
            @include('app._common.header.header')
            @include('app._common.sidebar.sidebar')
            <div class="mobile-menu-handle"></div>
            <article class="content">
                @yield('content')
            </article>
            @include('app._common.footer.footer')
            @include('app._common.modals.modals')
        </div>
    </div>
@endguest
</body>
</html>
