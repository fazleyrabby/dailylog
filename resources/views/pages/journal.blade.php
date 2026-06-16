@extends('layouts.app')

@section('title', 'Journal')
@section('header_breadcrumbs', 'DAILYLOG // JOURNAL')

@section('content_padding', 'p-0')

@section('content')
<div
    x-data="Object.assign(journalComponent({{ json_encode($journalEntries) }}), panelResizer({key:'journal', initial:360, min:280, max:560}))"
    x-init="initPanelResizer()"
    class="h-[calc(100vh-48px)] flex flex-col md:flex-row overflow-hidden bg-surface"
    :class="resizing ? 'cursor-col-resize' : ''"
>
    <!-- BACKDROP FOR MOBILE SIDEBAR -->
    <div 
        x-show="isMobile && showLeftPanel" 
        x-transition.opacity 
        @click="showLeftPanel = false" 
        class="fixed inset-0 bg-black/50 z-20"
        style="display: none;"
    ></div>

    <!-- LEFT SIDEBAR: Calendar & History -->
    <div 
        :class="[
            isMobile ? 'fixed inset-y-0 left-0 z-30 w-72 bg-surface shadow-2xl transform transition-transform duration-200 ease-in-out' : 'relative md:translate-x-0 md:shadow-none md:flex-shrink-0 border-r border-border md:max-h-full transition-all duration-200 ease-in-out',
            isMobile && (showLeftPanel ? 'translate-x-0' : '-translate-x-full')
        ]"
        :style="isMobile ? '' : 'width:' + (showLeftPanel ? panelWidth + 'px' : '0px')" 
        class="flex flex-col bg-surface md:bg-surface-2/10 h-full overflow-hidden"
    >
        <!-- Calendar Grid Header -->
        <div class="p-3 border-b border-border bg-surface">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-text-main uppercase tracking-wider">Calendar</h3>
                <div class="flex items-center space-x-1">
                    <button @click="prevMonth()" class="p-1 hover:bg-surface-2 border border-border rounded-sm cursor-pointer select-none text-[10px] font-mono leading-none">&lt;</button>
                    <span class="text-xs font-bold text-text-main font-mono px-2" x-text="monthYearLabel()"></span>
                    <button @click="nextMonth()" class="p-1 hover:bg-surface-2 border border-border rounded-sm cursor-pointer select-none text-[10px] font-mono leading-none">&gt;</button>
                </div>
            </div>
            
            <!-- Simple grid representing current month -->
            <div class="grid grid-cols-7 gap-1 text-center font-mono text-[10px]">
                <span class="text-text-subtle font-semibold">M</span>
                <span class="text-text-subtle font-semibold">T</span>
                <span class="text-text-subtle font-semibold">W</span>
                <span class="text-text-subtle font-semibold">T</span>
                <span class="text-text-subtle font-semibold">F</span>
                <span class="text-text-subtle font-semibold">S</span>
                <span class="text-text-subtle font-semibold">S</span>
                
                <!-- Blank padding slots -->
                <template x-for="blank in daysGrid.blankDays">
                    <span class="h-6 w-full flex items-center justify-center text-text-subtle/20 select-none">-</span>
                </template>

                <!-- Month days -->
                <template x-for="day in daysGrid.days" :key="day">
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
                    @click="selectDateFromString(entry.occurred_on)"
                    :class="selectedDate === entry.occurred_on ? 'bg-accent-subtle-bg/30 border-l-2 border-l-accent' : 'hover:bg-surface-2/30 border-l-2 border-l-transparent'"
                    class="p-3 cursor-pointer transition-colors"
                >
                    <div class="font-semibold text-xs text-text-main" x-text="entry.date"></div>
                    <p class="text-[10px] text-text-muted mt-1 truncate" x-text="entry.worked || 'No reflection details captured yet.'"></p>
                </div>
            </template>
        </div>
    </div>

    <!-- DRAG HANDLE RESIZER -->
    <div
        x-show="showLeftPanel"
        @mousedown="startPanelResize($event)"
        class="hidden md:flex w-2.5 flex-shrink-0 h-full z-10 cursor-col-resize items-center justify-center group relative"
    >
        <div class="w-[1px] h-full bg-border group-hover:bg-accent transition-colors duration-150"></div>
        <div class="absolute top-1/2 -translate-y-1/2 w-1 h-7 rounded-full bg-border/60 group-hover:bg-accent transition-colors duration-150 shadow-xs"></div>
    </div>

    <!-- RIGHT SECTION: Journal Content Editor -->
    <div class="flex-grow flex flex-col h-full bg-surface overflow-hidden min-w-0">
        
        <!-- Controls Header -->
        <div class="px-4 py-2.5 border-b border-border bg-surface-2/10 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <button 
                    @click="toggleLeftPanel()" 
                    class="mr-1.5 p-1 bg-surface hover:bg-surface-2 border border-border rounded-xs cursor-pointer select-none text-[10px] font-mono leading-none flex items-center space-x-1 text-text-muted hover:text-text-main"
                    title="Toggle Calendar Panel"
                >
                    <span x-text="showLeftPanel ? '◂' : '▸'"></span>
                    <span x-text="showLeftPanel ? 'Hide Calendar' : 'Calendar'"></span>
                </button>
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-text-subtle" x-text="getFormattedDateHeader()"></span>
            </div>
            <div class="flex items-center space-x-2">
                <template x-if="activeEntry">
                    <x-ui.button variant="secondary" size="sm" @click="editing = !editing">
                        <span x-text="editing ? '👁 Read Mode' : '✎ Edit Reflection'"></span>
                    </x-ui.button>
                </template>
                <template x-if="editing && activeEntry">
                    <x-ui.button variant="primary" size="sm" @click="saveEntry()">
                        Save Entry
                    </x-ui.button>
                </template>
            </div>
        </div>

        <!-- Editor canvas -->
        <div class="flex-grow p-6 overflow-y-auto max-w-2xl mx-auto w-full leading-relaxed select-text font-serif-reading">
            <!-- READ MODE -->
            <div x-show="!editing" class="space-y-6">
                
                <template x-if="activeEntry">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-[10px] font-bold uppercase tracking-wider text-text-subtle font-sans border-b border-border pb-1 mb-2.5">// What I Learned Today</h3>
                            <div class="text-base text-text-main font-serif-reading bg-surface-2/40 p-4 border border-border rounded-xs whitespace-pre-line select-text min-h-[4rem] leading-relaxed" x-text="activeEntry.learned || 'Nothing captured.'"></div>
                        </div>

                        <div>
                            <h3 class="text-[10px] font-bold uppercase tracking-wider text-text-subtle font-sans border-b border-border pb-1 mb-2.5">// What I Worked On Today</h3>
                            <div class="text-base text-text-main font-serif-reading bg-surface-2/40 p-4 border border-border rounded-xs whitespace-pre-line select-text min-h-[4rem] leading-relaxed" x-text="activeEntry.worked || 'Nothing captured.'"></div>
                        </div>

                        <div>
                            <h3 class="text-[10px] font-bold uppercase tracking-wider text-text-subtle font-sans border-b border-border pb-1 mb-2.5">// Wins & Milestones</h3>
                            <div class="text-base text-text-main font-serif-reading bg-surface-2/40 p-4 border border-border rounded-xs whitespace-pre-line select-text min-h-[4rem] leading-relaxed" x-text="activeEntry.wins || 'Nothing captured.'"></div>
                        </div>

                        <div>
                            <h3 class="text-[10px] font-bold uppercase tracking-wider text-text-subtle font-sans border-b border-border pb-1 mb-2.5">// Ideas Captured</h3>
                            <div class="text-base text-text-main font-serif-reading bg-surface-2/40 p-4 border border-border rounded-xs whitespace-pre-line select-text min-h-[3.5rem] leading-relaxed" x-text="activeEntry.ideas || 'Nothing captured.'"></div>
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
            <div x-show="editing" class="space-y-5">
                <template x-if="activeEntry">
                    <div class="space-y-5">
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wider text-text-subtle block mb-1.5 font-sans">// What I Learned Today</label>
                            <x-ui.textarea x-model="activeEntry.learned" rows="3" class="font-serif-reading text-base leading-relaxed p-3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wider text-text-subtle block mb-1.5 font-sans">// What I Worked On Today</label>
                            <x-ui.textarea x-model="activeEntry.worked" rows="3" class="font-serif-reading text-base leading-relaxed p-3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wider text-text-subtle block mb-1.5 font-sans">// Wins & Milestones</label>
                            <x-ui.textarea x-model="activeEntry.wins" rows="3" class="font-serif-reading text-base leading-relaxed p-3"></x-ui.textarea>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wider text-text-subtle block mb-1.5 font-sans">// Ideas Captured</label>
                            <x-ui.textarea x-model="activeEntry.ideas" rows="2" class="font-serif-reading text-base leading-relaxed p-3"></x-ui.textarea>
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
        currentYear: 2026,
        currentMonth: 5, // June is index 5
        editing: false,
        entries: initialEntries,

        monthNames: [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ],

        get sortedEntries() {
            return Object.values(this.entries).sort((a, b) => b.occurred_on.localeCompare(a.occurred_on));
        },

        get activeEntry() {
            return this.entries[this.selectedDate] || null;
        },

        monthYearLabel() {
            return this.monthNames[this.currentMonth] + ' ' + this.currentYear;
        },

        prevMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
            this.editing = false;
        },

        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
            this.editing = false;
        },

        get daysGrid() {
            let year = this.currentYear;
            let month = this.currentMonth;
            
            let firstDay = new Date(year, month, 1).getDay();
            let blankDaysCount = firstDay === 0 ? 6 : firstDay - 1;
            let totalDays = new Date(year, month + 1, 0).getDate();
            
            return {
                blankDays: Array.from({length: blankDaysCount}, (_, i) => i),
                days: Array.from({length: totalDays}, (_, i) => i + 1)
            };
        },

        getDateString(day) {
            let monthStr = String(this.currentMonth + 1).padStart(2, '0');
            let dayStr = String(day).padStart(2, '0');
            return `${this.currentYear}-${monthStr}-${dayStr}`;
        },

        hasEntry(day) {
            return !!this.entries[this.getDateString(day)];
        },

        selectDate(day) {
            this.selectedDate = this.getDateString(day);
            this.editing = false;
            if (this.isMobile) {
                this.showLeftPanel = false;
            }
        },

        selectDateFromString(occurredOn) {
            this.selectedDate = occurredOn;
            this.editing = false;
            
            let parts = occurredOn.split('-');
            if (parts.length === 3) {
                this.currentYear = parseInt(parts[0]);
                this.currentMonth = parseInt(parts[1]) - 1;
            }
            if (this.isMobile) {
                this.showLeftPanel = false;
            }
        },

        getDateClass(day) {
            let dateStr = this.getDateString(day);
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
                    if (this.isMobile) {
                        this.showLeftPanel = false;
                    }
                }
            });
        }
    };
};
</script>
@endsection
