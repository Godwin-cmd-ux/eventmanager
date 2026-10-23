<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="theme-color" content="@yield('theme_color', '#eef1f8')" />
    <meta name="color-scheme" content="@yield('color_scheme', 'light')" />
    <meta name="description" content="Wedding guest verification for entrance clerks." />
    <title>@yield('title', 'Wedding Guest Verification')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="@yield('body_class', 'page page--app')">
    <a class="skip-link" href="#main">Skip to content</a>

    @yield('content')

    @stack('scripts')

    @php
        $toasts = [];

        $flashed = session('toast');
        if (is_array($flashed) && ($flashed['title'] ?? $flashed['message'] ?? null)) {
            $toasts[] = $flashed;
        } elseif (is_string($flashed) && $flashed !== '') {
            $toasts[] = ['tone' => 'success', 'message' => $flashed];
        }

        if ($errors->any()) {
            $toasts[] = [
                'tone' => 'error',
                'title' => 'Check your details',
                'message' => $errors->first(),
            ];
        }
    @endphp

    @if ($toasts !== [])
        <script type="application/json" id="app-flash">@json(['toasts' => $toasts], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
    @endif
</body>
</html>
