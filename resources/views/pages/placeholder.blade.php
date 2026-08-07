<x-layouts::app :title="__($title)">
    <section class="mx-auto flex w-full max-w-3xl flex-col gap-6 py-10">
        <div class="space-y-2">
            <flux:heading size="xl" level="1">{{ __($title) }}</flux:heading>
            <flux:text class="max-w-2xl text-base text-zinc-600 dark:text-zinc-300">
                {{ __($description) }}
            </flux:text>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-xs dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300">
                    <flux:icon.sparkles class="size-5" />
                </div>

                <div class="space-y-1">
                    <flux:heading size="lg" level="2">{{ __('Navigation target ready') }}</flux:heading>
                    <flux:text class="text-zinc-600 dark:text-zinc-300">
                        {{ __('This route is wired into the Phase 3 app shell so primary navigation has stable destinations before the full workflow screens are built.') }}
                    </flux:text>
                </div>
            </div>
        </div>
    </section>
</x-layouts::app>
