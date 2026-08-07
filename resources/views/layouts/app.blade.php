<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-[#fbfaf7] dark:bg-zinc-950">
        <div class="mx-auto grid w-full max-w-[1680px] gap-6 px-4 py-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:px-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0">
                {{ $slot }}
            </div>

            <livewire:app.pinned-transaction-rail />
        </div>
    </flux:main>
</x-layouts::app.sidebar>
