@extends('layouts.app')

@section('title', 'Journal')
@section('header_breadcrumbs', 'DAILYLOG // JOURNAL')

@section('content')
<div 
    x-data="{
        selectedDate: '2026-06-08',
        editing: false,
        entries: {
            '2026-06-08': {
                date: 'June 8, 2026',
                learned: '- Redis Streams consumer group acknowledge logic (XACK).\n- Tailwind v4 theme extension syntax via app.css.',
                worked: '- Initialized Laravel 12 application skeleton.\n- Configured Vite build plugins and Bunny fonts loaders.',
                wins: '- Solved the non-empty directory composer project initialization issue using temp subdirectories.\n- Clean border-based UI system styling matches requirements.',
                ideas: '- Build a local SQLite database backup synchronizer that outputs compressed JSON archives to S3 buckets.'
            },
            '2026-06-07': {
                date: 'June 7, 2026',
                learned: '- Understood Laravel 12 default Vite config and asset output mechanisms.',
                worked: '- Drafted Information Architecture specs and UX strategy maps.',
                wins: '- Cleared out the backlogs list representing outstanding architecture items.',
                ideas: '- Introduce natural-language time parse capabilities for task due dates like `due:friday`.'
            }
        },
        
        saveEntry() {
            window.dispatchEvent(new CustomEvent('show-toast', { 
                detail: { message: 'Journal reflection saved', action: 'Dismiss' }
            }));
            this.editing = false;
        }
    }"
    class="h-[calc(100vh-100px)] flex overflow-hidden border border-border rounded-sm bg-surface"
>
    <!-- LEFT SIDEBAR: Calendar & History -->
    <div class="w-1/3 border-r border-border flex flex-col bg-surface-2/10">
        <!-- Calendar Grid Header -->
        <div class="p-3 border-b border-border bg-surface">
            <h3 class="text-xs font-semibold text-text-main uppercase tracking-wider mb-2">Calendar Navigation</h3>
            
            <!-- Simple Mock 31-day grid representing current month -->
            <div class="grid grid-cols-7 gap-1 text-center font-mono text-[10px]">
                <span class="text-text-subtle">M</span><span class="text-text-subtle">T</span><span class="text-text-subtle">W</span><span class="text-text-subtle">T</span><span class="text-text-subtle">F</span><span class="text-text-subtle">S</span><span class="text-text-subtle">S</span>
                
                <template x-for="day in Array.from({length: 8}, (_, i) => i + 1)">
                    <button 
                        @click="selectedDate = '2026-06-0' + day; editing = false;"
                        :class="selectedDate === '2026-06-0' + day ? 'bg-accent text-white font-bold' : 'bg-surface hover:bg-surface-2 text-text-main border border-border'"
                        class="h-6 w-full rounded-sm flex items-center justify-center cursor-pointer focus:outline-none"
                        x-text="day"
                    ></button>
                </template>
                <template x-for="day in Array.from({length: 22}, (_, i) => i + 9)">
                    <span class="h-6 w-full flex items-center justify-center text-text-subtle select-none" x-text="day"></span>
                </template>
            </div>
        </div>

        <!-- History Entries list -->
        <div class="flex-grow overflow-y-auto divide-y divide-border">
            <div class="p-3 bg-surface-2/30 text-[10px] font-semibold text-text-subtle uppercase tracking-wider border-b border-border/40">
                Reflection Logs
            </div>
            
            <div 
                @click="selectedDate = '2026-06-08'; editing = false;"
                :class="selectedDate === '2026-06-08' ? 'bg-accent-subtle-bg/30' : 'hover:bg-surface-2/30'"
                class="p-3 cursor-pointer transition-colors"
            >
                <div class="font-semibold text-xs text-text-main">June 8, 2026</div>
                <p class="text-[10px] text-text-muted mt-1 truncate">Worked on Laravel 12 application skeleton and Vite configuration.</p>
            </div>

            <div 
                @click="selectedDate = '2026-06-07'; editing = false;"
                :class="selectedDate === '2026-06-07' ? 'bg-accent-subtle-bg/30' : 'hover:bg-surface-2/30'"
                class="p-3 cursor-pointer transition-colors"
            >
                <div class="font-semibold text-xs text-text-main">June 7, 2026</div>
                <p class="text-[10px] text-text-muted mt-1 truncate">Drafted Information Architecture specs and UX strategy maps.</p>
            </div>
        </div>
    </div>

    <!-- RIGHT SECTION: Journal Content Editor -->
    <div class="w-2/3 flex flex-col h-full bg-surface">
        
        <!-- Controls Header -->
        <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-semibold text-text-main" x-text="entries[selectedDate] ? entries[selectedDate].date : 'Journal Entry Details'"></span>
            </div>
            <div class="flex items-center space-x-2">
                <button 
                    @click="editing = !editing"
                    class="h-7 px-2.5 bg-surface border border-border hover:bg-surface-2 text-xxs font-medium rounded-sm flex items-center space-x-1 cursor-pointer select-none text-text-main"
                >
                    <span x-text="editing ? '👁 Read Mode' : '✎ Edit Reflection'"></span>
                </button>
                <template x-if="editing">
                    <x-ui.button variant="primary" size="sm" @click="saveEntry()" class="font-semibold cursor-pointer">
                        Save Entry
                    </x-ui.button>
                </template>
            </div>
        </div>

        <!-- Editor canvas -->
        <div class="flex-grow p-6 overflow-y-auto max-w-2xl mx-auto w-full leading-relaxed select-text">
            <!-- READ MODE -->
            <div x-show="!editing" class="space-y-6">
                
                <template x-if="entries[selectedDate]">
                    <div class="space-y-5">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1 mb-2">What I Learned Today</h3>
                            <div class="text-xs text-text-main font-mono bg-surface-2 p-3 border border-border rounded-sm whitespace-pre-line select-text" x-text="entries[selectedDate].learned"></div>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1 mb-2">What I Worked On Today</h3>
                            <div class="text-xs text-text-main font-mono bg-surface-2 p-3 border border-border rounded-sm whitespace-pre-line select-text" x-text="entries[selectedDate].worked"></div>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1 mb-2">Wins & Milestones</h3>
                            <div class="text-xs text-text-main font-mono bg-surface-2 p-3 border border-border rounded-sm whitespace-pre-line select-text" x-text="entries[selectedDate].wins"></div>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1 mb-2">Ideas Captured</h3>
                            <div class="text-xs text-text-main font-mono bg-surface-2 p-3 border border-border rounded-sm whitespace-pre-line select-text" x-text="entries[selectedDate].ideas"></div>
                        </div>
                    </div>
                </template>
                
                <template x-if="!entries[selectedDate]">
                    <x-ui.empty-state 
                        title="Blank Reflection Log"
                        description="No journal reflection entry was captured for this day."
                        actionLabel="capture today's log"
                        @click="editing = true"
                    />
                </template>
            </div>

            <!-- EDIT MODE -->
            <div x-show="editing" class="space-y-4">
                <template x-if="entries[selectedDate]">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">What I Learned Today</label>
                            <x-ui.textarea x-model="entries[selectedDate].learned" rows="3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">What I Worked On Today</label>
                            <x-ui.textarea x-model="entries[selectedDate].worked" rows="3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Wins & Milestones</label>
                            <x-ui.textarea x-model="entries[selectedDate].wins" rows="3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Ideas Captured</label>
                            <x-ui.textarea x-model="entries[selectedDate].ideas" rows="2"></x-ui.textarea>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
