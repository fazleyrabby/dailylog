<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ theme: localStorage.getItem('theme') || 'dark' }"
      x-init="document.documentElement.classList.toggle('dark', theme === 'dark'); document.documentElement.setAttribute('data-theme', theme)"
      class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') | DailyLOG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-bg text-text-main font-sans antialiased flex items-center justify-center">
    <main class="w-full max-w-sm px-6 py-10">
        <div class="text-center mb-8">
            <div class="text-lg font-bold text-accent tracking-wider">Daily<span class="text-text-main">LOG</span></div>
            <div class="text-[10px] text-text-subtle mt-1 uppercase tracking-wider">Personal Life OS</div>
        </div>
        @yield('content')
    </main>
</body>
</html>
