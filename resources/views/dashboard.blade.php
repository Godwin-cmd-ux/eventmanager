@extends('layouts.app')

@section('title', 'Clerk Dashboard')
@section('body_class', 'page page--app')
@section('theme_color', '#eef1f8')

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
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3" />
                            <path d="m16 17 5-5-5-5M21 12H9" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="app-main" id="main">
        <section class="hero">
            <p class="hero__eyebrow">
                <span class="pill__dot" aria-hidden="true"></span>
                {{ $greeting }}
            </p>

            <h1 class="hero__title">
                Ready at the door,<br />
                <span>{{ $displayName }}</span>.
            </h1>

            <p class="hero__sub">
                Point the camera at a guest's invitation. Every code is checked
                against the list before entry.
            </p>

            <div class="hero__footer">
                <a href="{{ route('scan') }}" class="btn btn--light btn--lg">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2" />
                        <path d="M4 12h16" />
                    </svg>
                    {{ $scans === 0 ? 'Start scanning' : 'Resume scanning' }}
                </a>

                <span class="hero__meta">
                    {{ $scans }} {{ \Illuminate\Support\Str::plural('scan', $scans) }} recorded
                </span>
            </div>
        </section>

        <section class="stats-grid" aria-label="Your scan statistics">
            <div class="stat-card stat-card--neutral">
                <span class="stat-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8V6a2 2 0 0 1 2-2h2M16 4h2a2 2 0 0 1 2 2v2M20 16v2a2 2 0 0 1-2 2h-2M8 20H6a2 2 0 0 1-2-2v-2" /><path d="M4 12h16" /></svg>
                </span>
                <span class="stat-card__value">{{ $stats['total'] }}</span>
                <span class="stat-card__label">Total scans</span>
            </div>

            <div class="stat-card stat-card--success">
                <span class="stat-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5" /></svg>
                </span>
                <span class="stat-card__value">{{ $stats['successful'] }}</span>
                <span class="stat-card__label">Verified</span>
            </div>

            <div class="stat-card stat-card--danger">
                <span class="stat-card__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12" /></svg>
                </span>
                <span class="stat-card__value">{{ $stats['failed'] }}</span>
                <span class="stat-card__label">Declined</span>
            </div>
        </section>

        <p class="hint">
            Each successful scan consumes one place on the invitation.
            <strong>The database is the final authority</strong> on every scan.
        </p>
    </main>
</div>
@endsection
