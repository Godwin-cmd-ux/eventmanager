@extends('layouts.app')

@section('title', $title)

@section('content')
<div class="center-layout">
    <div class="card notice notice--{{ $state === 'pending' ? 'warning' : 'error' }}">
        <div class="notice__badge notice__badge--{{ $state === 'pending' ? 'warning' : 'error' }}" aria-hidden="true">
            {{ $state === 'pending' ? '✓' : '!' }}
        </div>
        <h1 class="notice__title">{{ $title }}</h1>
        <p class="notice__message">{{ $message }}</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn--secondary btn--block">Sign out</button>
        </form>
    </div>
</div>
@endsection