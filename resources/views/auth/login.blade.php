@extends('layouts.app')

@section('title', 'Sign in · Vows')
@section('body_class', 'page page--auth')
@section('theme_color', '#17402f')

@section('content')
<div class="auth">
    <aside class="auth__aside">
        @include('partials.brand', ['brandMeta' => 'Wedding Entrance'])

        <div class="auth__pitch">
            <p class="auth__title">Every guest, <em>verified</em> in a single scan.</p>
            <p class="auth__lede">
                A calm, reliable door. Scan the invitation, confirm the name, and let
                the evening begin.
            </p>

            <ul class="auth__features">
                <li class="auth__feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                    <div>
                        <strong>Instant verification</strong>
                        <span>Results in under a second, even on a busy queue.</span>
                    </div>
                </li>
                <li class="auth__feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7V5a1 1 0 0 1 1-1h2M17 4h2a1 1 0 0 1 1 1v2M20 17v2a1 1 0 0 1-1 1h-2M7 20H5a1 1 0 0 1-1-1v-2" /><path d="M12 8v8M8 12h8" /></svg>
                    <div>
                        <strong>Built for the door</strong>
                        <span>Large targets, high contrast, comfortable one-handed.</span>
                    </div>
                </li>
                <li class="auth__feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 7v5c0 4.4 3.4 8.3 8 9 4.6-.7 8-4.6 8-9V7l-8-4Z" /><path d="m9 12 2 2 4-4" /></svg>
                    <div>
                        <strong>Auditable by design</strong>
                        <span>Every scan is recorded against its invitation.</span>
                    </div>
                </li>
            </ul>
        </div>

        <p class="auth__footnote">Clerk accounts are active immediately after sign-up.</p>
    </aside>

    <main class="auth__panel" id="main">
        <div class="auth__panel-inner">
            <div class="auth__mobile-brand">
                @include('partials.brand', ['brandMeta' => 'Wedding Entrance'])
            </div>

            <header class="auth-header">
                <p class="eyebrow">Clerk access</p>
                <h1 class="auth-title">Clerk Login</h1>
                <p class="auth-subtitle">Welcome back. Sign in to start verifying invitations.</p>
            </header>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="field @error('email') field--invalid @enderror">
                    <label for="email">Email address</label>
                    <div class="field__control">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3" /><path d="m4 8 7.1 4.7a2 2 0 0 0 2.2 0L20 8" /></svg>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            inputmode="email"
                            autocomplete="email"
                            placeholder="you@example.com"
                            value="{{ old('email') }}"
                            required
                            autofocus
                        />
                    </div>
                    @error('email')
                        <p class="field-error">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" /></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="field @error('password') field--invalid @enderror">
                    <label for="password">Password</label>
                    <div class="field__control">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="3" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            required
                        />
                    </div>
                    @error('password')
                        <p class="field-error">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" /></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <button type="submit" class="btn btn--primary btn--lg btn--block">
                    Sign in
                </button>
            </form>

            <p class="auth-trust">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 7v5c0 4.4 3.4 8.3 8 9 4.6-.7 8-4.6 8-9V7l-8-4Z" /><path d="m9 12 2 2 4-4" /></svg>
                <span>Scanning is limited to registered clerks with an active account.</span>
            </p>

            <p class="auth-footer">
                Need scanning access?
                <a href="{{ route('register') }}">Create an account</a>
            </p>
        </div>
    </main>
</div>
@endsection
