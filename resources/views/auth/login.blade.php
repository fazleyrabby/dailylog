@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
<form method="POST" action="{{ route('auth.attempt') }}" class="bg-surface border border-border rounded-sm p-6 space-y-4">
    @csrf

    <h1 class="text-sm font-semibold text-text-main">Sign in</h1>

    <div>
        <label for="email" class="block text-xs font-medium text-text-muted mb-1">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
               class="w-full h-9 px-2 bg-surface-2 border border-border rounded-sm text-xs text-text-main focus:outline-none focus:ring-1 focus:ring-accent">
        @error('email')
            <p class="mt-1 text-[11px] text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-xs font-medium text-text-muted mb-1">Password</label>
        <input id="password" name="password" type="password" required
               class="w-full h-9 px-2 bg-surface-2 border border-border rounded-sm text-xs text-text-main focus:outline-none focus:ring-1 focus:ring-accent">
        @error('password')
            <p class="mt-1 text-[11px] text-danger">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex items-center space-x-2 text-xs text-text-muted">
        <input type="checkbox" name="remember" value="1" class="accent-accent">
        <span>Remember me</span>
    </label>

    <button type="submit"
            class="w-full h-9 bg-accent hover:bg-accent-hover text-accent-fg text-xs font-semibold rounded-sm focus:outline-none focus:ring-2 focus:ring-accent cursor-pointer">
        Sign in
    </button>
</form>
@endsection
