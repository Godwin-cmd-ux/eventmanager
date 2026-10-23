<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="theme-color" content="#1e3a8a" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title', 'Wedding Guest Verification')</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>