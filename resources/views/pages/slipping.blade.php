@extends('layouts.app')

@section('title', 'Slipping Items')
@section('header_breadcrumbs', 'DAILYLOG // SLIPPING')

@section('content')
<div 
    x-data="slippingComponent({{ json_encode($slippingItems) }})"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Slipping Items Triage" badge="">
        <x-slot:badge>
            <span x-text="items.length"></span>
        </x-slot:badge>
    </x-ui.section-header>

    <!-- Help Banner -->
    <div class="p-3 bg-warning/5 border border-warning/20 rounded-sm text-xs text-text-main flex items-start space-x-3">
        <span class="text-warning text-sm">⚠</span>
        <div class="space-y-1">
            <div class="font-semibold text-warning">What is a Slipping Item?</div>
            <div class="text-text-muted leading-relaxed">
                These are active goals, courses, or reference items that haven't received updates recently. DailyLOG aggregates these to prevent them from slipping into silence. Use the quick actions below to triage.
            </div>
        </div>
    </div>

    <!-- Slipping Items list -->
    <div class="space-y-3.5">
        <template x-if="items.length === 0">
            <div class="py-12 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                No slipping items! Your personal life OS is in perfect alignment.
            </div>
        </template>
        
        <template x-for="item in items" :key="item.id">
            <div 
                :class="{
                    'border-danger/30 hover:border-danger/60': item.severity === 'high',
                    'border-warning/30 hover:border-warning/60': item.severity === 'medium',
                    'border-border hover:border-border-strong': item.severity === 'low'
                }"
                class="bg-surface border rounded-sm p-4 text-xs flex flex-col md:flex-row md:items-center justify-between gap-4 transition-colors"
            >
                <div>
                    <div class="flex items-center space-x-2.5">
                        <span :class="{
                            'bg-danger': item.severity === 'high',
                            'bg-warning': item.severity === 'medium',
                            'bg-text-subtle': item.severity === 'low'
                        }" class="h-2 w-2 rounded-full flex-shrink-0 animate-pulse"></span>
                        
                        <span class="bg-surface-2 border border-border text-[9px] font-mono px-1 rounded-sm text-text-subtle uppercase" x-text="item.type"></span>
                        
                        <h4 class="font-bold text-text-main text-sm" x-text="item.title"></h4>
                    </div>
                    
                    <p class="text-text-muted mt-1.5 leading-normal">
                        Untouched for <span class="font-mono text-warning font-semibold" x-text="item.days + ' days'"></span> · 
                        Severity: <span :class="{
                            'text-danger font-semibold': item.severity === 'high',
                            'text-warning font-semibold': item.severity === 'medium',
                            'text-text-subtle': item.severity === 'low'
                        }" x-text="item.severity.toUpperCase()"></span>
                    </p>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex items-center space-x-1.5 flex-wrap gap-y-1">
                    <x-ui.button variant="primary" @click="triage(item.id, 'resume')">
                        Resume
                    </x-ui.button>
                    <x-ui.button variant="secondary" @click="triage(item.id, 'schedule')">
                        Schedule task
                    </x-ui.button>
                    <x-ui.button variant="secondary" @click="triage(item.id, 'snooze')">
                        Snooze
                    </x-ui.button>
                    <x-ui.button variant="ghost" @click="triage(item.id, 'let-go')" class="text-text-subtle hover:text-danger">
                        Let go
                    </x-ui.button>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
window.slippingComponent = function(initialItems) {
    return {
        items: initialItems,

        triage(id, action) {
            let item = this.items.find(x => x.id === id);
            if (!item) return;

            fetch(`/slipping/${id}/${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.items = this.items.filter(x => x.id !== id);
                    
                    let msg = '';
                    if (action === 'resume') {
                        msg = `Resumed work on: ${item.title}`;
                    } else if (action === 'schedule') {
                        msg = `Task scheduled for: ${item.title}`;
                    } else if (action === 'snooze') {
                        msg = `Snoozed alert for: ${item.title}`;
                    } else if (action === 'let-go') {
                        msg = `Let go (archived): ${item.title}`;
                    }

                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: msg }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
