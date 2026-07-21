@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
<form method="POST" action="{{ route('auth.attempt') }}" 
      x-data="{ token: '' }"
      class="w-full bg-surface border border-border rounded-sm p-8 space-y-5 login-container">
    @csrf

    <style>
        .login-container *, .login-container *::before, .login-container *::after {
            box-sizing: border-box !important;
        }
    </style>

    <h1 class="text-sm font-semibold text-text-main">Sign in</h1>

    <div>
        <label for="token" class="block text-xs font-medium text-text-muted mb-1.5">Access Token</label>
        <input id="token" name="token" type="password" x-model="token" required autofocus
               class="w-full h-10 px-3 bg-surface-2 border border-border rounded-sm text-xs text-text-main focus:outline-none focus:ring-1 focus:ring-accent">
        @error('token')
            <p class="mt-1 text-[11px] text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-3 pt-2">
        <button type="submit"
                class="w-full h-10 bg-accent hover:bg-accent-hover text-accent-fg text-xs font-semibold rounded-sm focus:outline-none focus:ring-2 focus:ring-accent cursor-pointer transition-colors duration-150">
            Sign in
        </button>
    </div>
</form>
@endsection
