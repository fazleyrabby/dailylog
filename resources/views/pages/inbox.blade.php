@extends('layouts.app')

@section('title', 'Inbox')
@section('header_breadcrumbs', 'DAILYLOG // INBOX')

@section('content')
<div 
    x-data="inboxComponent()"
    class="max-w-4xl mx-auto space-y-6"
>
    <!-- Header -->
    <x-ui.section-header title="Inbox Triage Container" badge="3" />

    <!-- Help Banner -->
    <div class="p-3 bg-surface-2 border border-border rounded-sm text-xs text-text-muted leading-relaxed">
        Anything captured via command palette without formatting syntax lands here. Use the quick triage keys to file them into tasks, notes, or archive.
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
                <div class="py-3 px-1 flex flex-col md:flex-row md:items-center justify-between gap-4 text-xs group hover:bg-surface-2/10 transition-colors">
                    <div class="min-w-0 space-y-1">
                        <div class="font-medium text-text-main leading-normal select-text" x-text="item.text"></div>
                        <div class="flex items-center space-x-1.5 font-mono text-[9px] text-text-subtle">
                            <span>Project:</span>
                            <span class="text-accent uppercase font-bold" x-text="'@' + item.project"></span>
                        </div>
                    </div>
                    
                    <!-- Quick action buttons -->
                    <div class="flex items-center space-x-1.5 flex-shrink-0">
                        <x-ui.button variant="secondary" @click="fileAsTask(item.id)">
                            File Task
                        </x-ui.button>
                        <x-ui.button variant="secondary" @click="fileAsNote(item.id)">
                            File Note
                        </x-ui.button>
                        <button 
                            @click="archive(item.id)"
                            class="p-1.5 border border-border hover:bg-surface rounded-sm text-text-subtle hover:text-danger cursor-pointer"
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
window.inboxComponent = function() {
    return {
        items: [
            { id: 1, text: 'Review Docker production configurations before push to ECS cluster', project: 'DevOps' },
            { id: 2, text: 'Need to research Redis Streams scaling consumer groups acknowledge packets', project: 'DailyLOG' },
            { id: 3, text: 'Buy SSL certificates from Let\'s Encrypt for dev sandbox environment', project: 'Freelancing' }
        ],

        fileAsTask(id) {
            let item = this.items.find(x => x.id === id);
            if (item) {
                this.items = this.items.filter(x => x.id !== id);
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'Filed as Task: ' + (item.text.length > 20 ? item.text.substring(0, 20) + '...' : item.text), action: 'Undo' }
                }));
            }
        },

        fileAsNote(id) {
            let item = this.items.find(x => x.id === id);
            if (item) {
                this.items = this.items.filter(x => x.id !== id);
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'Filed as Note: ' + (item.text.length > 20 ? item.text.substring(0, 20) + '...' : item.text), action: 'Undo' }
                }));
            }
        },

        archive(id) {
            let item = this.items.find(x => x.id === id);
            if (item) {
                this.items = this.items.filter(x => x.id !== id);
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'Archived: ' + (item.text.length > 20 ? item.text.substring(0, 20) + '...' : item.text), action: 'Undo' }
                }));
            }
        }
    };
};
</script>
@endsection
