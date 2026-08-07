<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-[#fbfaf7] dark:bg-zinc-950">
        <div class="mx-auto w-full max-w-[1440px] px-4 py-6 lg:px-6">
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts::app.sidebar>
