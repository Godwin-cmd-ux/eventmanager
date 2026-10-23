@extends('layouts.app')

@section('title', 'Clerk Dashboard')

@section('content')
<div class="app-shell">
    <header class="app-header">
        <div>
            <p class="app-header__eyebrow">Wedding Entrance</p>
            <h1 class="app-header__title">Clerk Dashboard</h1>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn--ghost">Log out</button>
        </form>
    </header>

    <main class="app-main">
        <p class="greeting">
            Signed in as <strong>{{ $clerk['full_name'] ?: $clerk['email'] }}</strong>
        </p>

        <section class="stats-grid" aria-label="Your scan statistics">
            <div class="stat-card stat-card--neutral">
                <span class="stat-card__value">{{ $stats['total'] }}</span>
                <span class="stat-card__label">Total Scans</span>
            </div>
            <div class="stat-card stat-card--success">
                <span class="stat-card__value">{{ $stats['successful'] }}</span>
                <span class="stat-card__label">Successful</span>
            </div>
            <div class="stat-card stat-card--danger">
                <span class="stat-card__value">{{ $stats['failed'] }}</span>
                <span class="stat-card__label">Failed</span>
            </div>
        </section>

        <a href="{{ route('scan') }}" class="btn btn--primary btn--block btn--xl">
            {{ $stats['total'] === 0 ? 'Start Scan' : 'Continuous Scan' }}
        </a>

        <p class="hint">
            Each successful scan consumes one place on the invitation. The database
            is the final authority on every scan.
        </p>
    </main>
</div>
@endsection
