@extends('layouts.app')

@section('title', 'Clerk Login')

@section('content')
<div class="center-layout">
    <div class="card">
        <header class="auth-header">
            <h1 class="auth-title">Clerk Login</h1>
            <p class="auth-subtitle">Sign in to verify wedding invitations.</p>
        </header>

        @if ($errors->has('email'))
            <div class="alert alert--error" role="alert">
                {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="field">
                <label for="email">Email address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    inputmode="email"
                    autocomplete="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                />
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                />
            </div>

            <button type="submit" class="btn btn--primary btn--block">Log in</button>
        </form>

        <p class="auth-footer">
            Need an account? <a href="{{ route('register') }}">Register</a>
        </p>
    </div>
</div>
@endsection