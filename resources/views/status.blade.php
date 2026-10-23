@extends('layouts.app')

@section('title', $title)
@section('body_class', 'page page--app')
@section('theme_color', '#faf7f2')

@php
    $isPending = $state === 'pending';
    $tone = $isPending ? 'warning' : 'error';
@endphp

@section('content')
<div class="center-layout">
    <div class="status-shell">
        @include('partials.brand', ['brandMeta' => 'Wedding Entrance'])

        <div class="card notice notice--{{ $tone }}" id="main">
            <div class="notice__badge" aria-hidden="true">
                @if ($isPending)
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01" /><path d="M10.3 4.3 2.6 17.8A2 2 0 0 0 4.3 20.8h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z" /></svg>
                @endif
            </div>

            <h1 class="notice__title">{{ $title }}</h1>
            <p class="notice__message">{{ $message }}</p>

            @if ($isPending)
                <ol class="timeline" aria-label="Approval progress">
                    <li class="timeline__item timeline__item--done">
                        <span class="timeline__dot" aria-hidden="true">✓</span>
                        <span>Request received</span>
                    </li>
                    <li class="timeline__item timeline__item--current">
                        <span class="timeline__dot" aria-hidden="true">•</span>
                        <span>Awaiting organizer approval</span>
                    </li>
                </ol>
            @endif

            <a href="{{ route('clerk.status') }}" class="btn btn--primary btn--block">
                {{ $isPending ? 'Check again' : 'Retry' }}
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn--secondary btn--block">
                    Sign out
                </button>
            </form>
        </div>

        <p class="status-footnote">
            @if ($isPending)
                Clerk access is approved by the event organizer.
            @else
                Contact the event organizer if this is unexpected.
            @endif
        </p>
    </div>
</div>
@endsection
