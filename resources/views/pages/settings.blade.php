@extends('layouts.app')

@section('title', 'Settings')
@section('header_breadcrumbs', 'DAILYLOG // SETTINGS')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <x-ui.section-header title="System Settings" />

    <!-- Settings Forms Grid -->
    <div class="space-y-6">
        
        <!-- APPEARANCE & THEME -->
        <x-ui.card title="Appearance & Layout Mode">
            <div class="space-y-4 text-xs">
                <!-- Theme Select -->
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-text-main block">UI Color Theme</span>
                        <span class="text-text-muted mt-0.5">Toggle between dark and light workspace skins.</span>
                    </div>
                    <div class="flex space-x-1">
                        <button 
                            @click="setTheme('dark')" 
                            :class="theme === 'dark' ? 'bg-accent/15 border-accent text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                            class="px-3 py-1 border text-xxs font-mono rounded cursor-pointer select-none"
                        >
                            DARK
                        </button>
                        <button 
                            @click="setTheme('light')" 
                            :class="theme === 'light' ? 'bg-accent/15 border-accent text-accent font-semibold' : 'bg-surface border-border text-text-muted hover:text-text-main'"
                            class="px-3 py-1 border text-xxs font-mono rounded cursor-pointer select-none"
                        >
                            LIGHT
                        </button>
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
