@extends('layouts.app')

@section('title', 'Journal')
@section('header_breadcrumbs', 'DAILYLOG // JOURNAL')

@section('content')
<div 
    x-data="journalComponent({{ json_encode($journalEntries) }})"
    class="h-[calc(100vh-100px)] flex overflow-hidden border border-border rounded-sm bg-surface"
>
    <!-- LEFT SIDEBAR: Calendar & History -->
    <div class="w-1/3 border-r border-border flex flex-col bg-surface-2/10">
        <!-- Calendar Grid Header -->
        <div class="p-3 border-b border-border bg-surface">
            <h3 class="text-xs font-semibold text-text-main uppercase tracking-wider mb-2">Calendar Navigation (June 2026)</h3>
            
            <!-- Simple 30-day grid representing current month -->
            <div class="grid grid-cols-7 gap-1 text-center font-mono text-[10px]">
                <span class="text-text-subtle">M</span><span class="text-text-subtle">T</span><span class="text-text-subtle">W</span><span class="text-text-subtle">T</span><span class="text-text-subtle">F</span><span class="text-text-subtle">S</span><span class="text-text-subtle">S</span>
                
                <template x-for="day in daysInMonth" :key="day">
                    <button 
                        @click="selectDate(day)"
                        :class="getDateClass(day)"
                        class="h-6 w-full rounded-sm flex items-center justify-center cursor-pointer focus:outline-none transition-colors relative"
                    >
                        <span x-text="day"></span>
                        <span x-show="hasEntry(day)" class="absolute bottom-0.5 w-1 h-1 rounded-full bg-accent"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- History Entries list -->
        <div class="flex-grow overflow-y-auto divide-y divide-border">
            <div class="p-3 bg-surface-2/30 text-[10px] font-semibold text-text-subtle uppercase tracking-wider border-b border-border/40">
                Reflection Logs
            </div>
            
            <template x-for="entry in sortedEntries" :key="entry.id">
                <div 
                    @click="selectedDate = entry.occurred_on; editing = false;"
                    :class="selectedDate === entry.occurred_on ? 'bg-accent-subtle-bg/30' : 'hover:bg-surface-2/30'"
                    class="p-3 cursor-pointer transition-colors"
                >
                    <div class="font-semibold text-xs text-text-main" x-text="entry.date"></div>
                    <p class="text-[10px] text-text-muted mt-1 truncate" x-text="entry.worked || 'No reflection details captured yet.'"></p>
                </div>
            </template>
        </div>
    </div>

    <!-- RIGHT SECTION: Journal Content Editor -->
    <div class="w-2/3 flex flex-col h-full bg-surface">
        
        <!-- Controls Header -->
        <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="text-xs font-semibold text-text-main" x-text="getFormattedDateHeader()"></span>
            </div>
            <div class="flex items-center space-x-2">
                <template x-if="activeEntry">
                    <button 
                        @click="editing = !editing"
                        class="h-7 px-2.5 bg-surface border border-border hover:bg-surface-2 text-xxs font-medium rounded-sm flex items-center space-x-1 cursor-pointer select-none text-text-main"
                    >
                        <span x-text="editing ? '👁 Read Mode' : '✎ Edit Reflection'"></span>
                    </button>
                </template>
                <template x-if="editing && activeEntry">
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
                
                <template x-if="activeEntry">
                    <div class="space-y-5">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1 mb-2">What I Learned Today</h3>
                            <div class="text-xs text-text-main font-mono bg-surface-2 p-3 border border-border rounded-sm whitespace-pre-line select-text min-h-[3.5rem]" x-text="activeEntry.learned || 'Nothing captured.'"></div>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1 mb-2">What I Worked On Today</h3>
                            <div class="text-xs text-text-main font-mono bg-surface-2 p-3 border border-border rounded-sm whitespace-pre-line select-text min-h-[3.5rem]" x-text="activeEntry.worked || 'Nothing captured.'"></div>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1 mb-2">Wins & Milestones</h3>
                            <div class="text-xs text-text-main font-mono bg-surface-2 p-3 border border-border rounded-sm whitespace-pre-line select-text min-h-[3.5rem]" x-text="activeEntry.wins || 'Nothing captured.'"></div>
                        </div>

                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-text-subtle border-b border-border pb-1 mb-2">Ideas Captured</h3>
                            <div class="text-xs text-text-main font-mono bg-surface-2 p-3 border border-border rounded-sm whitespace-pre-line select-text min-h-[3.5rem]" x-text="activeEntry.ideas || 'Nothing captured.'"></div>
                        </div>
                    </div>
                </template>
                
                <template x-if="!activeEntry">
                    <x-ui.empty-state 
                        title="Blank Reflection Log"
                        description="No journal reflection entry was captured for this day."
                        actionLabel="capture today's log"
                        @click="createEntryForSelectedDate()"
                    />
                </template>
            </div>

            <!-- EDIT MODE -->
            <div x-show="editing" class="space-y-4">
                <template x-if="activeEntry">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">What I Learned Today</label>
                            <x-ui.textarea x-model="activeEntry.learned" rows="3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">What I Worked On Today</label>
                            <x-ui.textarea x-model="activeEntry.worked" rows="3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Wins & Milestones</label>
                            <x-ui.textarea x-model="activeEntry.wins" rows="3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-xxs font-bold uppercase tracking-wider text-text-subtle block mb-1">Ideas Captured</label>
                            <x-ui.textarea x-model="activeEntry.ideas" rows="2"></x-ui.textarea>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
window.journalComponent = function(initialEntries) {
    return {
        selectedDate: '2026-06-09',
        editing: false,
        entries: initialEntries,
        daysInMonth: Array.from({length: 30}, (_, i) => i + 1),

        get sortedEntries() {
            return Object.values(this.entries).sort((a, b) => b.occurred_on.localeCompare(a.occurred_on));
        },

        get activeEntry() {
            return this.entries[this.selectedDate] || null;
        },

        hasEntry(day) {
            let dateStr = '2026-06-' + String(day).padStart(2, '0');
            return !!this.entries[dateStr];
        },

        selectDate(day) {
            this.selectedDate = '2026-06-' + String(day).padStart(2, '0');
            this.editing = false;
        },

        getDateClass(day) {
            let dateStr = '2026-06-' + String(day).padStart(2, '0');
            if (this.selectedDate === dateStr) {
                return 'bg-accent text-white font-bold';
            }
            if (this.entries[dateStr]) {
                return 'bg-accent/10 hover:bg-accent/15 text-accent border border-accent/20';
            }
            return 'bg-surface hover:bg-surface-2 text-text-main border border-border';
        },

        getFormattedDateHeader() {
            if (this.activeEntry) {
                return this.activeEntry.date;
            }
            let parts = this.selectedDate.split('-');
            if (parts.length === 3) {
                let date = new Date(parts[0], parts[1] - 1, parts[2]);
                return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
            }
            return 'Journal Entry Details';
        },

        saveEntry() {
            if (!this.activeEntry) return;

            fetch(`/journal/${this.activeEntry.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    learned: this.activeEntry.learned,
                    worked: this.activeEntry.worked,
                    wins: this.activeEntry.wins,
                    ideas: this.activeEntry.ideas
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.entry) {
                    this.entries[this.selectedDate] = data.entry;
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Journal reflection saved' }
                    }));
                    this.editing = false;
                }
            });
        },

        createEntryForSelectedDate() {
            fetch('/journal', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    occurred_on: this.selectedDate
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.entry) {
                    this.entries[this.selectedDate] = data.entry;
                    this.editing = true;
                }
            });
        }
    };
};
</script>
@endsection
