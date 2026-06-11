@props([
    'headers' => []
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto border border-border rounded-sm bg-surface']) }}>
    <table class="min-w-full divide-y divide-border text-sm">
        @if(isset($thead))
            <thead class="bg-surface-2/40 sticky top-0">
                {{ $thead }}
            </thead>
        @else
            <thead class="bg-surface-2/40 sticky top-0">
                <tr>
                    @foreach($headers as $header)
                        <th scope="col" class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-text-muted">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody class="divide-y divide-border bg-surface">
            {{ $slot }}
        </tbody>
    </table>
</div>
