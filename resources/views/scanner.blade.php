@extends('layouts.app')

@section('title', 'Scan Invitation')
@section('body_class', 'page page--scan')
@section('theme_color', '#080b0a')
@section('color_scheme', 'dark')

@section('content')
<div class="app-shell">
    <header class="app-header">
        <div class="app-header__inner">
            @include('partials.brand', ['brandMeta' => 'Live scanning'])

            <a href="{{ route('dashboard') }}" class="btn btn--ghost">Stop</a>
        </div>
    </header>

    <main class="app-main app-main--scan" id="main">
        <h1 class="sr-only">Scan invitation</h1>

        <div class="scan-stage">
            <div id="qr-reader" class="qr-reader"></div>
            <span class="scan-stage__frame" aria-hidden="true"></span>
            <span class="scan-stage__line" aria-hidden="true"></span>
        </div>

        <p id="scan-status" class="scan-status scan-status--info" role="status">
            Requesting camera access…
        </p>

        <div id="scan-error" class="card" hidden>
            <div class="alert alert--error" role="alert"></div>
            <div class="btn-row">
                <button type="button" class="btn btn--primary" onclick="window.location.reload()">
                    Try again
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn--secondary">
                    Back to dashboard
                </a>
            </div>
        </div>

        <div class="scan-footer">
            <a href="{{ route('dashboard') }}" class="btn btn--ghost btn--block">
                Back to dashboard
            </a>
        </div>
    </main>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/scanner.js'])
@endpush
