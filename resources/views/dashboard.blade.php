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

    <main class="app-main app-main--dashboard" id="main">
        <div class="dashboard-grid">
            <section class="invite" aria-labelledby="invite-title">
                <div class="invite__frame">
                    <span class="invite__seal" aria-hidden="true">V</span>

                    <p class="eyebrow">{{ $greeting }}</p>

                    <h1 class="invite__title" id="invite-title">
                        Ready at the door, <em>{{ $displayName }}</em>.
                    </h1>

                    <p class="invite__sub">
                        Point the camera at a guest's invitation. Every code is checked
                        against the list before entry.
                    </p>

                    <a href="{{ route('scan') }}" class="btn btn--primary btn--lg">
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

            <aside class="rail">
                <section class="panel" aria-labelledby="ledger-title">
                    <div class="panel__head">
                        <h2 class="panel__title" id="ledger-title">Door ledger</h2>
                        <span class="panel__hint">All checks</span>
                    </div>

                    <div class="ledger" aria-label="Your scan statistics">
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
                    </div>
                </section>

                <section class="panel" aria-labelledby="steps-title">
                    <div class="panel__head">
                        <h2 class="panel__title" id="steps-title">At the door</h2>
                    </div>

                    <ol class="steps">
                        <li class="steps__item">
                            <span class="steps__num" aria-hidden="true">01</span>
                            <span class="steps__copy">
                                <strong>Scan the invitation</strong>
                                <span>Frame the guest's QR code and hold steady.</span>
                            </span>
                        </li>
                        <li class="steps__item">
                            <span class="steps__num" aria-hidden="true">02</span>
                            <span class="steps__copy">
                                <strong>Confirm the name</strong>
                                <span>Check the guest details against the party at the door.</span>
                            </span>
                        </li>
                        <li class="steps__item">
                            <span class="steps__num" aria-hidden="true">03</span>
                            <span class="steps__copy">
                                <strong>Admit or decline</strong>
                                <span>The result is recorded the moment you scan.</span>
                            </span>
                        </li>
                    </ol>
                </section>
            </aside>

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
        </div>

        <footer class="app-footer">
            <span class="app-footer__mark">Vows · Wedding entrance</span>
            <span>Organizer-approved clerk</span>
        </footer>
    </main>
</div>
@endsection
