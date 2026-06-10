@extends('layouts.app')

@section('title', 'Inbox')
@section('header_breadcrumbs', 'DAILYLOG // INBOX')

@section('content')
<div 
    x-data="inboxComponent({{ json_encode($entries) }}, {{ json_encode($projects) }})"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Inbox Triage Container" badge="">
        <x-slot:badge>
            <span x-text="items.length"></span>
        </x-slot:badge>
    </x-ui.section-header>

    <!-- Help Banner -->
    <div class="p-3 bg-surface-2 border border-border rounded-sm text-xs text-text-muted leading-relaxed">
        Anything captured via command palette without formatting syntax lands here. Use the quick triage actions to convert them into tasks, notes, or archive.
    </div>

    <!-- Triage List Rows -->
    <x-ui.card>
        <div class="divide-y divide-border">
            <template x-if="items.length === 0">
                <div class="py-12 text-center text-xs text-text-muted">
                    No items in triage inbox. Nice work clearing things out!
                </div>
            </template>
            
            <template x-for="item in items" :key="item.id">
                <div class="py-4 px-3 flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs group hover:bg-surface-2/10 transition-colors">
                    <div class="min-w-0 space-y-2 flex-grow">
                        <div class="font-medium text-text-main leading-normal select-text text-sm" x-text="item.text"></div>
                        <div class="flex items-center space-x-2">
                            <span class="text-[10px] text-text-subtle font-mono uppercase">Assign Project:</span>
                            <select 
                                x-model="item.project_id"
                                class="bg-surface border border-border px-2 py-0.5 rounded-sm focus:outline-none focus:border-accent text-text-main text-[10px] font-mono"
                            >
                                <option :value="null">None (Inbox)</option>
                                <template x-for="p in projects" :key="p.id">
                                    <option :value="p.id" x-text="p.name" :selected="item.project_id === p.id"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Quick action buttons -->
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <x-ui.button variant="secondary" size="sm" @click="triage(item.id, 'task')">
                            File Task
                        </x-ui.button>
                        <x-ui.button variant="secondary" size="sm" @click="triage(item.id, 'note')">
                            File Note
                        </x-ui.button>
                        <button 
                            @click="triage(item.id, 'archive')"
                            class="p-1.5 border border-border hover:bg-surface-2 rounded-sm text-text-subtle hover:text-danger cursor-pointer transition-colors"
                            title="Archive item"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </x-ui.card>
</div>

<script>
window.inboxComponent = function(initialItems, initialProjects) {
    return {
        items: initialItems,
        projects: initialProjects,

        triage(id, action) {
            let item = this.items.find(x => x.id === id);
            if (!item) return;

            fetch(`/inbox/${id}/triage`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    project_id: item.project_id
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.items = this.items.filter(x => x.id !== id);
                    
                    let actionMsg = action === 'archive' ? 'Archived' : `Filed as ${action.charAt(0).toUpperCase() + action.slice(1)}`;
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: `${actionMsg}: ` + (item.text.length > 25 ? item.text.substring(0, 25) + '...' : item.text) }
                    }));
                }
            });
        }
    };
};
</script>
@endsection
