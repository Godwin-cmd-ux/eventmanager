@extends('layouts.app')

@section('title', 'Clerk Registration')

@section('content')
<div class="center-layout">
    @if (session('status') === 'confirm_email')
        <div class="card notice notice--warning">
            <div class="notice__badge notice__badge--warning" aria-hidden="true">&#10003;</div>
            <h1 class="notice__title">Confirm your email</h1>
            <p class="notice__message">
                We sent a confirmation link to your email. Confirm it, then wait for
                organizer approval before you can scan.
            </p>
            <a href="{{ route('login') }}" class="btn btn--primary btn--block">Go to login</a>
        </div>
    @else
        <div class="card">
            <header class="auth-header">
                <h1 class="auth-title">Clerk Registration</h1>
                <p class="auth-subtitle">Create an account to request scanning access.</p>
            </header>

            @if ($errors->any())
                <div class="alert alert--error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input
                        id="full_name"
                        type="text"
                        name="full_name"
                        autocomplete="name"
                        value="{{ old('full_name') }}"
                        required
                        autofocus
                    />
                    @error('full_name')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

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
                    />
                    @error('email')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        required
                    />
                    @error('password')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    />
                </div>

                <button type="submit" class="btn btn--primary btn--block">Register</button>
            </form>

            <p class="auth-footer">
                Already registered? <a href="{{ route('login') }}">Log in</a>
            </p>
        </div>
    @endif
</div>
@endsection
