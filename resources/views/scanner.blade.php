@extends('layouts.app')

@section('title', 'Scan Invitation')

@section('content')
<div class="app-shell app-shell--scan">
    <header class="app-header">
        <div>
            <p class="app-header__eyebrow">Scanning as {{ $clerk['full_name'] }}</p>
            <h1 class="app-header__title">Scan Invitation</h1>
        </div>
        <a href="{{ route('dashboard') }}" class="btn btn--ghost">Stop</a>
    </header>

    <main class="app-main app-main--scan">
        <div id="qr-reader" class="qr-reader"></div>

        <p id="scan-status" class="scan-status scan-status--info">
            Requesting camera access...
        </p>

        <div id="scan-error" class="card" hidden>
            <div class="alert alert--error" role="alert"></div>
            <div class="btn-row">
                <button type="button" class="btn btn--primary" onclick="window.location.reload()">
                    Try Again
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn--secondary">
                    Back to Dashboard
                </a>
            </div>
        </div>

        <div class="scan-footer">
            <a href="{{ route('dashboard') }}" class="btn btn--secondary btn--block">
                Back to Dashboard
            </a>
        </div>
    </main>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/scanner.js'])
@endpush