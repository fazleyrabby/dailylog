@extends('layouts.app')

@section('title', 'Settings')
@section('header_breadcrumbs', 'DAILYLOG // SETTINGS')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <x-ui.section-header title="System Settings" />

    <!-- Settings Forms Grid -->
    <div class="space-y-6">
        
        <!-- DATABASE BACKUP -->
        <x-ui.card title="Database Backup">
            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <span class="font-bold text-text-main block">Back up to Supabase</span>
                        <span class="text-text-muted mt-0.5">Mirror the live database to the off-site Supabase copy. Runs automatically every week; you can also trigger it manually.</span>
                    </div>
                    <form method="POST" action="{{ route('settings.backup.supabase') }}" x-data="{ busy: false }" @submit="busy = true" class="flex-shrink-0">
                        @csrf
                        <x-ui.button type="submit" variant="secondary" size="sm" ::disabled="busy">
                            <span x-show="!busy">Back up now</span>
                            <span x-show="busy" style="display:none;">Backing up…</span>
                        </x-ui.button>
                    </form>
                </div>
                <div class="text-text-subtle">
                    Last backup:
                    <span class="font-mono text-text-muted">{{ $lastBackupAt ? \Illuminate\Support\Carbon::parse($lastBackupAt)->diffForHumans() : 'never' }}</span>
                </div>
                @if(session('status'))
                    <div class="text-success font-medium">{{ session('status') }}</div>
                @endif
                @if(session('error'))
                    <div class="text-danger font-medium break-words">{{ session('error') }}</div>
                @endif
            </div>
        </x-ui.card>

        <!-- IP WHITELIST (PRODUCTION) -->
        <x-ui.card title="IP Whitelist (Production)">
            <div
                x-data="{
                    envIps: @js($envIps),
                    userIps: @js($userIps),
                    newIp: '',
                    currentIp: @js($currentIp),
                    isEnabled: @js($isWhitelistEnabled),
                    error: '',

                    addIp(ip) {
                        ip = ip.trim();
                        if (!ip) return;
                        if (!this.isValidIpOrCidr(ip)) {
                            this.error = 'Invalid IP address or CIDR range';
                            return;
                        }
                        if (this.userIps.includes(ip) || this.envIps.includes(ip)) {
                            this.error = 'IP already whitelisted';
                            return;
                        }
                        this.userIps.push(ip);
                        this.newIp = '';
                        this.error = '';
                        this.save();
                    },

                    removeIp(ip) {
                        this.userIps = this.userIps.filter(i => i !== ip);
                        this.save();
                    },

                    addCurrentIp() {
                        this.addIp(this.currentIp);
                    },

                    isValidIpOrCidr(value) {
                        const ipv4 = /^(\d{1,3}\.){3}\d{1,3}$/;
                        const ipv6 = /^([0-9a-fA-F]{0,4}:){2,7}[0-9a-fA-F]{0,4}$/;
                        const cidr = /^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/;
                        return ipv4.test(value) || ipv6.test(value) || cidr.test(value);
                    },

                    async save() {
                        const form = new FormData();
                        this.userIps.forEach(ip => form.append('ips[]', ip));
                        if (this.userIps.length === 0) form.append('ips[]', '');

                        try {
                            const response = await fetch('{{ route('settings.ip-whitelist.update') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json',
                                },
                                body: form,
                            });

                            if (response.ok) {
                                window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'IP whitelist updated' } }));
                            } else {
                                const data = await response.json();
                                this.error = data.message || 'Failed to update whitelist';
                            }
                        } catch (e) {
                            this.error = 'Network error. Please try again.';
                        }
                    },
                }"
                class="space-y-4 text-xs"
            >
                <!-- Status indicator -->
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-text-main block">Access Restriction</span>
                        <span class="text-text-muted mt-0.5">Only whitelisted IPs can access the application in production.</span>
                    </div>
                    <span
                        :class="isEnabled ? 'bg-success/10 border-success/30 text-success' : 'bg-surface-2 border-border text-text-subtle'"
                        class="px-3 py-1 border text-xxs font-mono rounded select-none"
                        x-text="isEnabled ? 'ACTIVE' : 'INACTIVE'"
                    ></span>
                </div>

                <div class="h-px bg-border"></div>

                <!-- Current IP display -->
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <span class="font-bold text-text-main block">Your Current IP</span>
                        <span class="font-mono text-text-muted mt-0.5" x-text="currentIp"></span>
                    </div>
                    <template x-if="!userIps.includes(currentIp) && !envIps.includes(currentIp)">
                        <button
                            @click="addCurrentIp()"
                            class="px-3 py-1.5 bg-accent/10 hover:bg-accent/20 border border-accent/30 text-accent text-xxs font-mono rounded cursor-pointer select-none transition-colors"
                        >
                            + ADD MY IP
                        </button>
                    </template>
                    <template x-if="userIps.includes(currentIp) || envIps.includes(currentIp)">
                        <span class="px-3 py-1.5 bg-success/10 border border-success/30 text-success text-xxs font-mono rounded select-none">
                            ✓ WHITELISTED
                        </span>
                    </template>
                </div>

                <div class="h-px bg-border"></div>

                <!-- Whitelisted IPs list -->
                <div>
                    <span class="font-bold text-text-main block mb-2">Whitelisted IPs</span>

                    <!-- Env-locked IPs -->
                    <template x-for="ip in envIps" :key="'env-' + ip">
                        <div class="flex items-center justify-between py-1.5 px-2 bg-surface-2/50 rounded-sm mb-1">
                            <div class="flex items-center space-x-2">
                                <span class="text-text-subtle" title="Locked via environment variable">🔒</span>
                                <span class="font-mono text-text-main" x-text="ip"></span>
                            </div>
                            <span class="text-[10px] text-text-subtle font-mono uppercase">ENV</span>
                        </div>
                    </template>

                    <!-- User-managed IPs -->
                    <template x-for="ip in userIps" :key="'user-' + ip">
                        <div class="flex items-center justify-between py-1.5 px-2 hover:bg-surface-2/30 rounded-sm mb-1 group">
                            <div class="flex items-center space-x-2">
                                <span class="text-success/60">●</span>
                                <span class="font-mono text-text-main" x-text="ip"></span>
                            </div>
                            <button
                                @click="removeIp(ip)"
                                class="text-text-subtle hover:text-danger text-xs opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer focus:outline-none"
                                title="Remove IP"
                            >
                                ✕
                            </button>
                        </div>
                    </template>

                    <!-- Empty state -->
                    <template x-if="envIps.length === 0 && userIps.length === 0">
                        <div class="text-text-subtle text-center py-3 bg-surface-2/30 rounded-sm">
                            No IPs whitelisted yet. All traffic is allowed when the list is empty.
                        </div>
                    </template>
                </div>

                <div class="h-px bg-border"></div>

                <!-- Add IP form -->
                <div class="flex items-start gap-2">
                    <div class="flex-grow">
                        <input
                            type="text"
                            x-model="newIp"
                            @keydown.enter.prevent="addIp(newIp)"
                            placeholder="Enter IP or CIDR (e.g. 192.168.1.0/24)"
                            class="ui-input w-full h-8 px-2 bg-surface border border-border rounded-sm text-sm focus:ring-1 focus:outline-none placeholder-text-subtle focus:border-accent focus:ring-accent"
                        />
                        <template x-if="error">
                            <span class="text-[11px] text-danger mt-1 block" x-text="error"></span>
                        </template>
                    </div>
                    <button
                        @click="addIp(newIp)"
                        class="px-3 h-8 bg-surface-2 hover:bg-surface border border-border text-text-muted hover:text-text-main text-xxs font-mono rounded-sm cursor-pointer select-none transition-colors flex-shrink-0"
                    >
                        ADD
                    </button>
                </div>

                <!-- Flash success -->
                @if(session('ip_status'))
                    <div class="text-success font-medium">{{ session('ip_status') }}</div>
                @endif
                @if($errors->any())
                    <div class="text-danger font-medium">{{ $errors->first() }}</div>
                @endif
            </div>
        </x-ui.card>

        <!-- APPEARANCE & THEME -->
        <x-ui.card title="Appearance & Layout Mode">
            <div class="space-y-4 text-xs">
                <!-- Theme Select -->
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-text-main block">UI Color Theme</span>
                        <span class="text-text-muted mt-0.5">Choose a workspace skin. Each theme has its own design language.</span>
                    </div>
                    <div class="flex flex-wrap justify-end gap-1 max-w-[60%]">
                        <template x-for="t in $store.themes.list" :key="t.id">
                            <button
                                @click="setTheme(t.id)"
                                :class="theme === t.id ? 'bg-accent/15 border-accent text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                                class="px-3 py-1 border text-xxs font-mono rounded cursor-pointer select-none"
                                x-text="t.name"
                            ></button>
                        </template>
                    </div>
                </div>

                <div class="h-px bg-border my-2"></div>

                <!-- Density Toggle -->
                <div x-data="{ density: localStorage.getItem('density') || 'comfortable' }" class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-text-main block">List Row Density</span>
                        <span class="text-text-muted mt-0.5">Compact aligns sizes to 32px (h-8); Comfortable aligns to 40px (h-10).</span>
                    </div>
                    <div class="flex space-x-1">
                        <button 
                            @click="density = 'compact'; localStorage.setItem('density', 'compact'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Row density set to Compact' } }))"
                            :class="density === 'compact' ? 'bg-accent/15 border-accent text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                            class="px-3 py-1 border text-xxs font-mono rounded cursor-pointer select-none"
                        >
                            COMPACT
                        </button>
                        <button 
                            @click="density = 'comfortable'; localStorage.setItem('density', 'comfortable'); window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Row density set to Comfortable' } }))"
                            :class="density === 'comfortable' ? 'bg-accent/15 border-accent text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                            class="px-3 py-1 border text-xxs font-mono rounded cursor-pointer select-none"
                        >
                            COMFORTABLE
                        </button>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <!-- SLIPPING ENGINE RULES -->
        <x-ui.card title="Slipping Engine Thresholds">
            <div class="space-y-4 text-xs">
                <p class="text-text-muted leading-relaxed">
                    Set the number of inactive days that will flag each content category as "slipping" inside the dashboard snapshot.
                </p>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <label class="font-bold text-text-main block mb-1">Learning Paths</label>
                        <x-ui.input type="number" value="30" class="font-mono text-center" />
                    </div>
                    <div>
                        <label class="font-bold text-text-main block mb-1">Projects</label>
                        <x-ui.input type="number" value="21" class="font-mono text-center" />
                    </div>
                    <div>
                        <label class="font-bold text-text-main block mb-1">Bookmarks</label>
                        <x-ui.input type="number" value="30" class="font-mono text-center" />
                    </div>
                    <div>
                        <label class="font-bold text-text-main block mb-1">Undated Tasks</label>
                        <x-ui.input type="number" value="30" class="font-mono text-center" />
                    </div>
                </div>
                
                <div class="flex justify-end pt-2">
                    <x-ui.button variant="primary" @click="window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Slipping rules updated' } }))">
                        Update Thresholds
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <!-- DATA MANAGEMENT & EXPORT -->
        <x-ui.card title="Data Portability & Export">
            <div class="space-y-4 text-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-text-main block">Export Personal Life OS Archive</span>
                        <span class="text-text-muted mt-0.5">Download a compressed ZIP archive containing all Markdown notes and a SQL file.</span>
                    </div>
                    <x-ui.button variant="secondary" @click="window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Generating export archive... Download started.' } }))">
                        Export ZIP
                    </x-ui.button>
                </div>
                
                <div class="h-px bg-border my-2"></div>
                
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-text-main block">Backup SQLite Database</span>
                        <span class="text-text-muted mt-0.5">Download a snapshot of the database file directly for emergency recovery.</span>
                    </div>
                    <x-ui.button variant="secondary" @click="window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: 'Database backup downloaded successfully.' } }))">
                        Download SQLite
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>

        <!-- PLUGGABLE AI CONFIG -->
        <x-ui.card title="AI Engine (Optional)">
            <div class="space-y-4 text-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-text-main block">Enable Local AI processing (Ollama)</span>
                        <span class="text-text-muted mt-0.5">Processes Whisper audio transcriptions and capture grammar parsing locally.</span>
                    </div>
                    <div x-data="{ enabled: false }" class="relative">
                        <button 
                            @click="enabled = !enabled; window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: enabled ? 'Ollama AI local pipeline enabled' : 'Ollama pipeline disabled' } }))"
                            :class="enabled ? 'bg-success/10 border-success/30 text-success' : 'bg-surface-2 border-border text-text-subtle'"
                            class="px-3 py-1 border text-xxs font-mono rounded cursor-pointer select-none"
                            x-text="enabled ? 'ENABLED' : 'DISABLED'"
                        ></button>
                    </div>
                </div>
            </div>
        </x-ui.card>

    </div>
</div>
@endsection
