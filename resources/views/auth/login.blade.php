@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
<form method="POST" action="{{ route('auth.attempt') }}" 
      x-data="{ email: '{{ old('email', '') }}', password: '' }"
      class="w-full bg-surface border border-border rounded-sm p-8 space-y-5 login-container">
    @csrf

    <style>
        .login-container *, .login-container *::before, .login-container *::after {
            box-sizing: border-box !important;
        }
    </style>

    <h1 class="text-sm font-semibold text-text-main">Sign in</h1>

    <div>
        <label for="email" class="block text-xs font-medium text-text-muted mb-1.5">Email</label>
        <input id="email" name="email" type="email" x-model="email" required autofocus
               class="w-full h-10 px-3 bg-surface-2 border border-border rounded-sm text-xs text-text-main focus:outline-none focus:ring-1 focus:ring-accent">
        @error('email')
            <p class="mt-1 text-[11px] text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-xs font-medium text-text-muted mb-1.5">Password</label>
        <input id="password" name="password" type="password" x-model="password" required
               class="w-full h-10 px-3 bg-surface-2 border border-border rounded-sm text-xs text-text-main focus:outline-none focus:ring-1 focus:ring-accent">
        @error('password')
            <p class="mt-1 text-[11px] text-danger">{{ $message }}</p>
        @enderror
    </div>

    <label class="flex items-center space-x-2 text-xs text-text-muted cursor-pointer select-none">
        <input type="checkbox" name="remember" value="1" class="accent-accent">
        <span>Remember me</span>
    </label>

    <div class="space-y-3 pt-2">
        <button type="submit"
                class="w-full h-10 bg-accent hover:bg-accent-hover text-accent-fg text-xs font-semibold rounded-sm focus:outline-none focus:ring-2 focus:ring-accent cursor-pointer transition-colors duration-150">
            Sign in
        </button>

        <div class="relative flex py-1 items-center">
            <div class="flex-grow border-t border-border"></div>
            <span class="flex-shrink mx-3 text-[10px] text-text-subtle uppercase tracking-wider">Or</span>
            <div class="flex-grow border-t border-border"></div>
        </div>

        <button type="button"
                @click="email = 'fazley111@gmail.com'; password = '01821013136rabby'; $nextTick(() => $el.form.submit())"
                class="w-full h-10 bg-surface-2 hover:bg-surface border border-border text-text-muted hover:text-text-main text-xs font-semibold rounded-sm focus:outline-none focus:ring-1 focus:ring-accent cursor-pointer transition-colors duration-150">
            Demo Login
        </button>
    </div>
</form>
@endsection
