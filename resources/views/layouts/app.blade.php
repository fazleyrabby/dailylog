<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" 
      x-data="{
          sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === 'true',
          mobileMenuOpen: false,
          isMobile: window.innerWidth < 768,
          theme: localStorage.getItem('theme') || 'dark',
          toasts: [],
          
          toggleSidebar() {
              this.sidebarCollapsed = !this.sidebarCollapsed;
              localStorage.setItem('sidebar-collapsed', this.sidebarCollapsed);
          },
          
          setTheme(val) {
              this.theme = val;
              localStorage.setItem('theme', val);
              if (val === 'dark') {
                  document.documentElement.classList.add('dark');
                  document.documentElement.setAttribute('data-theme', 'dark');
              } else {
                  document.documentElement.classList.remove('dark');
                  document.documentElement.removeAttribute('data-theme');
              }
          },

          initTheme() {
              this.setTheme(this.theme);
          },

          addToast(message, action = null) {
              const id = Date.now();
              this.toasts.push({ id, message, action });
              setTimeout(() => {
                  this.toasts = this.toasts.filter(t => t.id !== id);
              }, 8000);
          },

          dismissToast(id) {
              this.toasts = this.toasts.filter(t => t.id !== id);
          }
      }"
      x-init="
          initTheme();
          window.addEventListener('resize', () => { isMobile = window.innerWidth < 768; if (!isMobile) mobileMenuOpen = false; });
          // Global shortcut listener
          window.addEventListener('keydown', e => {
              if (e.key === '?' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA' && !document.activeElement.hasAttribute('contenteditable')) {
                  window.dispatchEvent(new CustomEvent('open-modal', { detail: { name: 'shortcuts' } }));
              }
              // Go shortcuts (g then key)
              if (e.key === 'g' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA' && !document.activeElement.hasAttribute('contenteditable')) {
                  const handler = (event) => {
                      if (event.key === 'd') window.location.href = '/dashboard';
                      else if (event.key === 't') window.location.href = '/tasks';
                      else if (event.key === 'n') window.location.href = '/notes';
                      else if (event.key === 'j') window.location.href = '/journal';
                      else if (event.key === 'b') window.location.href = '/bookmarks';
                      else if (event.key === 'l') window.location.href = '/learning';
                      else if (event.key === 'p') window.location.href = '/projects';
                      else if (event.key === 's') window.location.href = '/slipping';
                      else if (event.key === 'i') window.location.href = '/inbox';
                      window.removeEventListener('keydown', handler);
                  };
                  window.addEventListener('keydown', handler, { once: true });
                  // cleanup handler after 1s if no key is pressed
                  setTimeout(() => window.removeEventListener('keydown', handler), 1000);
              }
              // c shortcut to trigger capture / focus
              if (e.key === 'c' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA' && !document.activeElement.hasAttribute('contenteditable')) {
                  e.preventDefault();
                  // Dispatch open command palette prefilled or open default creation context
                  window.dispatchEvent(new CustomEvent('open-palette'));
              }
          });
          window.addEventListener('show-toast', e => {
              addToast(e.detail.message, e.detail.action);
          });
      "
      class="h-full select-none"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="DailyLOG - Personal Life OS monolith">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | DailyLOG</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-bg text-text-main font-sans antialiased overflow-hidden flex flex-col">

    <!-- Global App Wrapper -->
    <div class="flex h-full overflow-hidden relative">

        <!-- MOBILE BACKDROP -->
        <div
            x-show="mobileMenuOpen"
            x-transition.opacity
            @click="mobileMenuOpen = false"
            class="fixed inset-0 bg-black/50 z-30 md:hidden"
            style="display:none;"
        ></div>

        <!-- SIDEBAR -->
        <aside
            :class="[
                sidebarCollapsed ? 'md:w-16' : 'md:w-60',
                mobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
            ]"
            class="fixed md:relative inset-y-0 left-0 w-60 flex-shrink-0 bg-surface border-r border-border flex flex-col justify-between transition-all duration-200 z-40 transform"
        >
            <!-- Sidebar Header & Toggle -->
            <div>
                <div class="h-12 border-b border-border flex items-center justify-between px-3">
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-1.5" x-show="!sidebarCollapsed || isMobile">
                            <span class="w-3 h-3 rounded-full bg-[#FF5F56] border border-[#E0443E]/50"></span>
                            <span class="w-3 h-3 rounded-full bg-[#FFBD2E] border border-[#DEA123]/50"></span>
                            <span class="w-3 h-3 rounded-full bg-[#27C93F] border border-[#1AAB29]/50"></span>
                        </div>
                        <a href="/dashboard" class="flex items-center space-x-1.5 font-bold text-accent tracking-wider" x-show="!sidebarCollapsed || isMobile">
                            <span class="text-xs">Daily<span class="text-text-main">LOG</span></span>
                        </a>
                    </div>
                    <div class="mx-auto" x-show="sidebarCollapsed && !isMobile">
                        <span class="text-accent text-sm font-bold">D</span>
                    </div>
                    <button @click="toggleSidebar()" class="text-text-subtle hover:text-text-main cursor-pointer focus:outline-none" aria-label="Toggle Sidebar">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>

                <!-- Global Search Trigger Bar -->
                <div class="p-2 border-b border-border">
                    <button 
                        @click="$dispatch('open-palette')"
                        class="w-full h-8 px-2 bg-surface-2 hover:bg-surface border border-border rounded-sm flex items-center justify-between text-text-muted hover:text-text-main text-xs font-medium focus:outline-none focus:ring-1 focus:ring-accent cursor-pointer"
                    >
                        <span class="flex items-center space-x-2">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span x-show="!sidebarCollapsed || isMobile">Search (⌘K)</span>
                        </span>
                        <kbd x-show="!sidebarCollapsed || isMobile" class="text-[9px] font-mono border border-border bg-surface px-1 py-0.2 rounded-xs">⌘K</kbd>
                    </button>
                </div>

                <!-- Navigation Groups -->
                <nav class="p-2 space-y-1 overflow-y-auto max-h-[calc(100vh-180px)]">
                    <!-- Daily Drivers -->
                    <x-ui.sidebar-item href="/dashboard" :active="request()->is('dashboard', '/')">
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                        </x-slot>
                        Dashboard
                    </x-ui.sidebar-item>

                    <x-ui.sidebar-item href="/tasks" :active="request()->is('tasks*')" :badge="$sidebarCounts['tasks'] ?: null">
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </x-slot>
                        Tasks
                    </x-ui.sidebar-item>

                    <x-ui.sidebar-item href="/notes" :active="request()->is('notes*')">
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                        </x-slot>
                        Notes
                    </x-ui.sidebar-item>

                    <x-ui.sidebar-item href="/journal" :active="request()->is('journal*')">
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" /></svg>
                        </x-slot>
                        Journal
                    </x-ui.sidebar-item>

                    <div class="h-px bg-border my-2"></div>

                    <!-- Frequent -->
                    <x-ui.sidebar-item href="/bookmarks" :active="request()->is('bookmarks*')">
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                        </x-slot>
                        Bookmarks
                    </x-ui.sidebar-item>

                    <x-ui.sidebar-item href="/learning" :active="request()->is('learning*')" :badge="$sidebarCounts['learning'] ?: null">
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
                        </x-slot>
                        Learning
                    </x-ui.sidebar-item>

                    <x-ui.sidebar-item href="/projects" :active="request()->is('projects*')" :badge="$sidebarCounts['projects'] ?: null">
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6.878V6a2.25 2.25 0 012.25-2.25h7.5A2.25 2.25 0 0118 6v.878m-12 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128m-12 0A2.25 2.25 0 004.5 9v.878m13.5-3A2.25 2.25 0 0119.5 9v.878m-15 0a2.25 2.25 0 00-2.25 2.25v6.75A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V12.128a2.25 2.25 0 00-2.25-2.25m-15 0c.235-.083.487-.128.75-.128h10.5c.263 0 .515.045.75.128" /></svg>
                        </x-slot>
                        Projects
                    </x-ui.sidebar-item>

                    <div class="h-px bg-border my-2"></div>

                    <!-- Collapsible Library Group -->
                    <div x-data="{ open: localStorage.getItem('library-expanded') !== 'false' }">
                        <button 
                            @click="open = !open; localStorage.setItem('library-expanded', open)" 
                            class="w-full flex items-center justify-between px-3 py-1.5 text-xxs uppercase tracking-wider text-text-subtle hover:text-text-main font-semibold cursor-pointer focus:outline-none"
                        >
                            <span x-show="!sidebarCollapsed || isMobile" class="text-xs">Reference Library</span>
                            <span x-show="sidebarCollapsed && !isMobile">LIB</span>
                            <svg :class="open ? 'rotate-90' : ''" class="h-3 w-3 transition-transform text-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        
                        <div x-show="open" class="mt-1 space-y-1">
                            <x-ui.sidebar-item href="/quotes" :active="request()->is('quotes*')">
                                <x-slot name="icon">❝</x-slot>
                                Quotes
                            </x-ui.sidebar-item>
                            
                            <x-ui.sidebar-item href="/resources" :active="request()->is('resources*')">
                                <x-slot name="icon">◫</x-slot>
                                Resources
                            </x-ui.sidebar-item>
                        </div>
                    </div>

                    <div class="h-px bg-border my-2"></div>

                    <!-- System Modules -->
                    <x-ui.sidebar-item href="/slipping" :active="request()->is('slipping*')" :badge="$sidebarCounts['slipping'] ?: null">
                        <x-slot name="icon">
                            <svg class="h-4 w-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </x-slot>
                        Slipping
                    </x-ui.sidebar-item>

                    <x-ui.sidebar-item href="/inbox" :active="request()->is('inbox*')">
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.008 1.24l.885 1.77a2.25 2.25 0 002.007 1.24h1.98a2.25 2.25 0 002.007-1.24l.885-1.77a2.25 2.25 0 012.007-1.24h3.86m-18 0h18" /></svg>
                        </x-slot>
                        Inbox
                    </x-ui.sidebar-item>
                </nav>
            </div>

            <!-- Sidebar Footer / User Widget -->
            <div class="p-2 border-t border-border bg-surface-2/30">
                <div x-data="{ dropdownOpen: false }" class="relative">
                    <button 
                        @click="dropdownOpen = !dropdownOpen" 
                        class="w-full flex items-center space-x-2.5 p-1.5 hover:bg-surface border border-transparent hover:border-border rounded-sm text-left focus:outline-none focus:ring-1 focus:ring-accent cursor-pointer"
                    >
                        <div class="h-6 w-6 rounded-full bg-accent text-white flex items-center justify-center font-bold text-xs select-none">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()?->name ?? '?', 0, 1)) }}
                        </div>
                        <div class="truncate text-xs flex-grow" x-show="!sidebarCollapsed || isMobile">
                            <div class="font-semibold text-text-main">{{ auth()->user()?->name }}</div>
                            <div class="text-[10px] text-text-subtle">{{ auth()->user()?->email }}</div>
                        </div>
                        <svg x-show="!sidebarCollapsed || isMobile" class="h-3 w-3 text-text-subtle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- User Dropdown Menu -->
                    <div 
                        x-show="dropdownOpen" 
                        @click.away="dropdownOpen = false"
                        class="absolute bottom-10 left-0 z-30 w-52 bg-surface border border-border rounded-sm shadow-xl py-1 text-xs text-text-main"
                        style="display: none;"
                    >
                        <a href="{{ route('settings.profile') }}" class="block px-4 py-2 hover:bg-surface-2">⚙ Settings & Preferences</a>
                        <button @click="setTheme(theme === 'dark' ? 'light' : 'dark')" class="w-full text-left block px-4 py-2 hover:bg-surface-2">
                            🌓 Switch Theme (<span x-text="theme"></span>)
                        </button>
                        <div class="h-px bg-border my-1"></div>
                        <a href="{{ route('settings.profile') }}" class="block px-4 py-2 text-text-muted hover:bg-surface-2">📥 Export JSON / Markdown</a>
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left block px-4 py-2 text-text-muted hover:bg-surface-2">⎋ Log out</button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>

        <!-- MAIN WINDOW -->
        <main class="flex-grow flex flex-col h-full bg-bg relative min-w-0 w-full">

            <!-- TOP NAVIGATION HEADER -->
            <header class="h-12 border-b border-border bg-surface flex items-center justify-between px-3 md:px-6 z-10 gap-2">
                <div class="flex items-center space-x-2 md:space-x-4 min-w-0">
                    <button @click="mobileMenuOpen = true" class="md:hidden p-1 text-text-subtle hover:text-text-main focus:outline-none flex-shrink-0" aria-label="Open menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                    <span class="text-[10px] font-bold text-text-muted font-mono tracking-widest uppercase flex items-center space-x-1.5 truncate">
                        <span class="text-accent/80">~</span>
                        <span class="text-text-subtle font-normal">/</span>
                        <span class="text-text-main truncate">@yield('header_breadcrumbs', 'DAILYLOG // WORKSPACE')</span>
                    </span>
                </div>
                
                <div class="flex items-center space-x-3">
                    <!-- Theme Toggle -->
                    <button 
                        @click="setTheme(theme === 'dark' ? 'light' : 'dark')"
                        class="text-text-subtle hover:text-text-main p-1 rounded-sm focus:outline-none focus:ring-1 focus:ring-accent cursor-pointer"
                        aria-label="Toggle Theme"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>
                    
                    <!-- Quick Cheatsheet link -->
                    <button 
                        @click="$dispatch('open-modal', { name: 'shortcuts' })"
                        class="text-text-subtle hover:text-text-main p-1 rounded-sm focus:outline-none focus:ring-1 focus:ring-accent cursor-pointer"
                        aria-label="Show Shortcuts"
                    >
                        <span class="text-xs font-mono border border-border px-1.5 py-0.2 bg-surface-2 rounded-xs">? Help</span>
                    </button>
                </div>
            </header>
            
            <!-- PAGE CONTENT CONTAINER -->
            <div class="flex-grow overflow-y-auto p-3 md:p-6 relative">
                @yield('content')
            </div>
            
        </main>
        
    </div>

    <!-- COMMAND PALETTE OVERLAY -->
    <x-ui.command-palette />

    <!-- TOAST NOTIFICATIONS (Optimistic UI alerts) -->
    <div 
        class="fixed bottom-4 left-4 z-50 flex flex-col space-y-2 max-w-sm pointer-events-none"
        aria-live="assertive"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div 
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-98"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-98"
                class="pointer-events-auto bg-surface border border-border text-text-main text-xs px-4 py-3 rounded-sm shadow-lg flex items-center justify-between space-x-3 w-80"
            >
                <div class="flex items-center space-x-2">
                    <span class="text-success font-bold font-mono">⚡</span>
                    <span x-text="toast.message"></span>
                </div>
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <template x-if="toast.action">
                        <button 
                            @click="dismissToast(toast.id); addToast('Action reverted successfully.')" 
                            class="text-accent hover:text-accent-hover font-semibold cursor-pointer focus:outline-none"
                            x-text="toast.action"
                        ></button>
                    </template>
                    <button 
                        @click="dismissToast(toast.id)" 
                        class="text-text-subtle hover:text-text-main focus:outline-none"
                    >
                        &times;
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- SHORTCUT CHEATSHEET MODAL -->
    <x-ui.modal name="shortcuts">
        <x-slot name="title">Keyboard Shortcuts Map</x-slot>
        
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1.5 mb-2">Global Actions</h4>
                <div class="grid grid-cols-2 gap-y-1.5 text-xs">
                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">Cmd+K</kbd> <span>or</span> <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">Ctrl+K</kbd></div>
                    <div class="text-text-muted">Global command palette & search</div>
                    
                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">c</kbd></div>
                    <div class="text-text-muted">Smart new item capture palette</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">/</kbd></div>
                    <div class="text-text-muted">Focus page filter / search input</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">?</kbd></div>
                    <div class="text-text-muted">Open this cheatsheet</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">Esc</kbd></div>
                    <div class="text-text-muted">Close modals, dropdowns, and search focus</div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1.5 mb-2">Navigation Chords (Press g then...)</h4>
                <div class="grid grid-cols-2 gap-y-1.5 text-xs">
                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">g</kbd> then <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">d</kbd></div>
                    <div class="text-text-muted">Go to Dashboard</div>
                    
                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">g</kbd> then <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">t</kbd></div>
                    <div class="text-text-muted">Go to Tasks</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">g</kbd> then <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">n</kbd></div>
                    <div class="text-text-muted">Go to Notes</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">g</kbd> then <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">j</kbd></div>
                    <div class="text-text-muted">Go to Journal</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">g</kbd> then <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">b</kbd></div>
                    <div class="text-text-muted">Go to Bookmarks</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">g</kbd> then <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">l</kbd></div>
                    <div class="text-text-muted">Go to Learning</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">g</kbd> then <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">p</kbd></div>
                    <div class="text-text-muted">Go to Projects</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">g</kbd> then <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">s</kbd></div>
                    <div class="text-text-muted">Go to Slipping Items</div>
                </div>
            </div>

            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1.5 mb-2">List Context nav (Tasks / Notes rows)</h4>
                <div class="grid grid-cols-2 gap-y-1.5 text-xs">
                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">j</kbd> / <kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">k</kbd></div>
                    <div class="text-text-muted">Move list selection down / up</div>
                    
                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">Enter</kbd></div>
                    <div class="text-text-muted">Open selected list entry</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">x</kbd></div>
                    <div class="text-text-muted">Toggle check / complete list item</div>

                    <div class="flex items-center space-x-1"><kbd class="bg-surface-2 px-1 border border-border rounded font-mono font-semibold">e</kbd></div>
                    <div class="text-text-muted">Inline edit title</div>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <x-ui.button variant="secondary" @click="$dispatch('close-modal', { name: 'shortcuts' })">Close Cheatsheet</x-ui.button>
        </x-slot>
    </x-ui.modal>

</body>
</html>
