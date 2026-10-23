@extends('layouts.app')

@section('title', 'Create your clerk account · Vows')
@section('body_class', 'page page--auth')
@section('theme_color', '#17402f')

@section('content')
<div class="auth">
    <aside class="auth__aside">
        @include('partials.brand', ['brandMeta' => 'Wedding Entrance'])

        <div class="auth__pitch">
            <p class="auth__title">Join the team <em>at the door</em>.</p>
            <p class="auth__lede">
                Create your account and start scanning right away. Scans take
                seconds, and every guest is checked against the list.
            </p>

            <ul class="auth__features">
                <li class="auth__feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M19 8v6M22 11h-6" /></svg>
                    <div>
                        <strong>Instant access</strong>
                        <span>Sign up and start scanning at the door immediately.</span>
                    </div>
                </li>
                <li class="auth__feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 7v5c0 4.4 3.4 8.3 8 9 4.6-.7 8-4.6 8-9V7l-8-4Z" /><path d="m9 12 2 2 4-4" /></svg>
                    <div>
                        <strong>Your scans, tracked</strong>
                        <span>See successful and failed checks on your dashboard.</span>
                    </div>
                </li>
                <li class="auth__feature">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 8v4l3 2" /><circle cx="12" cy="12" r="9" /></svg>
                    <div>
                        <strong>Ready in a minute</strong>
                        <span>No emails to confirm and no approval to wait for.</span>
                    </div>
                </li>
            </ul>
        </div>

        <p class="auth__footnote">Clerk accounts are active the moment you sign up.</p>
    </aside>

    <main class="auth__panel" id="main">
        <div class="auth__panel-inner">
            <div class="auth__mobile-brand">
                @include('partials.brand', ['brandMeta' => 'Wedding Entrance'])
            </div>

            <header class="auth-header">
                <p class="eyebrow">Get started</p>
                <h1 class="auth-title">Clerk Registration</h1>
                <p class="auth-subtitle">Create an account and start scanning immediately.</p>
            </header>

            @if ($errors->any() && ! $errors->has('full_name') && ! $errors->has('email') && ! $errors->has('password'))
                <div class="alert alert--error" role="alert">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" /></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="field @error('full_name') field--invalid @enderror">
                    <label for="full_name">Full name</label>
                    <div class="field__control">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.6" /><path d="M4.5 20c.8-3.6 3.9-5.6 7.5-5.6s6.7 2 7.5 5.6" /></svg>
                        <input
                            id="full_name"
                            type="text"
                            name="full_name"
                            autocomplete="name"
                            placeholder="Ada Lovelace"
                            value="{{ old('full_name') }}"
                            required
                            autofocus
                        />
                    </div>
                    @error('full_name')
                        <p class="field-error">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" /></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

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
                        />
                    </div>
                    @error('email')
                        <p class="field-error">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9" /><path d="M12 8v4M12 16h.01" /></svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="field-row">
                    <div class="field @error('password') field--invalid @enderror">
                        <label for="password">Password</label>
                        <div class="field__control">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="3" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="8+ characters"
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

                    <div class="field">
                        <label for="password_confirmation">Confirm password</label>
                        <div class="field__control">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="3" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /><path d="m10 15 1.5 1.5L15 13" /></svg>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                placeholder="Repeat password"
                                required
                            />
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn--primary btn--lg btn--block">
                    Create account
                </button>
            </form>

            <p class="auth-trust">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 4 7v5c0 4.4 3.4 8.3 8 9 4.6-.7 8-4.6 8-9V7l-8-4Z" /><path d="m9 12 2 2 4-4" /></svg>
                <span>Your account is ready immediately after registration.</span>
            </p>

            <p class="auth-footer">
                Already registered?
                <a href="{{ route('login') }}">Sign in</a>
            </p>
        </div>
    </main>
</div>
@endsection
