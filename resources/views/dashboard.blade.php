@extends('layouts.app')

@section('title', 'Clerk Dashboard')
@section('body_class', 'page page--app')
@section('theme_color', '#faf7f2')

@php
    $displayName = trim((string) ($clerk['full_name'] ?? '')) ?: ($clerk['email'] ?? 'there');
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $scans = (int) ($stats['total'] ?? 0);
@endphp

@section('content')
<div class="app-shell">
    <header class="app-header">
        <div class="app-header__inner">
            @include('partials.brand', ['brandMeta' => 'Wedding Entrance'])

            <div class="app-header__actions">
                <span class="pill">
                    <span class="pill__dot" aria-hidden="true"></span>
                    On duty
                </span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="icon-btn" aria-label="Sign out" title="Sign out">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3" />
                            <path d="m16 17 5-5-5-5M21 12H9" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="app-main" id="main">
        <section class="invite">
            <div class="invite__frame">
                <p class="eyebrow">{{ $greeting }}</p>

                <h1 class="invite__title">
                    Ready at the door, <em>{{ $displayName }}</em>.
                </h1>

                <p class="invite__sub">
                    Point the camera at a guest's invitation. Every code is checked
                    against the list before entry.
                </p>

                <a href="{{ route('scan') }}" class="btn btn--primary btn--lg btn--block">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2" />
                        <path d="M4 12h16" />
                    </svg>
                    {{ $scans === 0 ? 'Start scanning' : 'Resume scanning' }}
                </a>

                <p class="invite__meta">
                    {{ $scans }} {{ \Illuminate\Support\Str::plural('scan', $scans) }} recorded so far
                </p>
            </div>
        </section>

        <section class="ledger" aria-label="Your scan statistics">
            <div class="ledger__item ledger__item--total">
                <span class="ledger__value">{{ $stats['total'] }}</span>
                <span class="ledger__label">Total</span>
            </div>
            <div class="ledger__item ledger__item--ok">
                <span class="ledger__value">{{ $stats['successful'] }}</span>
                <span class="ledger__label">Verified</span>
            </div>
            <div class="ledger__item ledger__item--no">
                <span class="ledger__value">{{ $stats['failed'] }}</span>
                <span class="ledger__label">Declined</span>
            </div>
        </section>

        <p class="note">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 11v5M12 8h.01" />
            </svg>
            <span>
                Each successful scan consumes one place on the invitation.
                <strong>The database is the final authority</strong> on every scan.
            </span>
        </p>
    </main>
</div>
@endsection
