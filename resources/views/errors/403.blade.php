@extends('layouts.guest')
@section('title', 'Access Denied')

@section('content')
<div class="w-full bg-surface border border-border rounded-sm p-8 space-y-5 text-center">
    <div class="flex items-center justify-center mb-2">
        <div class="h-12 w-12 rounded-full bg-danger/10 border border-danger/20 flex items-center justify-center">
            <svg class="h-6 w-6 text-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
        </div>
    </div>

    <h1 class="text-sm font-semibold text-text-main">Access Denied</h1>
    <p class="text-xs text-text-muted leading-relaxed">
        Your IP address is not whitelisted for this application.
    </p>

    <div class="bg-surface-2 border border-border rounded-sm px-4 py-3">
        <span class="text-[10px] text-text-subtle uppercase tracking-wider block mb-1">Your IP Address</span>
        <span class="text-xs font-mono text-text-main font-semibold">{{ request()->headers->get('Cf-Connecting-Ip') ?: request()->ip() }}</span>
    </div>

    <p class="text-[10px] text-text-subtle leading-relaxed">
        If you believe this is an error, add your IP to the <code class="font-mono text-text-muted">IP_WHITELIST</code> environment variable or contact the system administrator.
    </p>
</div>
@endsection
