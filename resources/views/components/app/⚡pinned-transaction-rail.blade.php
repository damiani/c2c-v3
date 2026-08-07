<?php

use App\Models\Transaction;
use App\Tenancy\CurrentTenant;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public bool $collapsed = false;

    public string $status = Transaction::STATUS_ACTIVE;

    public function toggleCollapsed(): void
    {
        $this->collapsed = ! $this->collapsed;
    }

    public function setStatus(string $status): void
    {
        if (! in_array($status, ['all', Transaction::STATUS_ACTIVE, Transaction::STATUS_PENDING_CLOSE, Transaction::STATUS_DRAFT], true)) {
            return;
        }

        $this->status = $status;
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        $tenant = app(CurrentTenant::class)->get();

        if ($tenant === null) {
            return [
                'all' => 0,
                Transaction::STATUS_ACTIVE => 0,
                Transaction::STATUS_PENDING_CLOSE => 0,
                Transaction::STATUS_DRAFT => 0,
            ];
        }

        return [
            'all' => Transaction::query()->forTenant($tenant->id)->count(),
            Transaction::STATUS_ACTIVE => Transaction::query()->forTenant($tenant->id)->where('status', Transaction::STATUS_ACTIVE)->count(),
            Transaction::STATUS_PENDING_CLOSE => Transaction::query()->forTenant($tenant->id)->where('status', Transaction::STATUS_PENDING_CLOSE)->count(),
            Transaction::STATUS_DRAFT => Transaction::query()->forTenant($tenant->id)->where('status', Transaction::STATUS_DRAFT)->count(),
        ];
    }

    #[Computed]
    public function transactions()
    {
        $tenant = app(CurrentTenant::class)->get();

        if ($tenant === null) {
            return collect();
        }

        return Transaction::query()
            ->forTenant($tenant->id)
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->with('owner')
            ->orderByDesc('opened_at')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();
    }

    public function badgeColor(string $status): string
    {
        return match ($status) {
            Transaction::STATUS_ACTIVE => 'green',
            Transaction::STATUS_PENDING_CLOSE => 'amber',
            Transaction::STATUS_CLOSED => 'blue',
            Transaction::STATUS_TERMINATED => 'red',
            default => 'zinc',
        };
    }
};
?>

<aside class="hidden lg:block" data-test="pinned-transaction-rail">
    @if ($collapsed)
        <button
            type="button"
            wire:click="toggleCollapsed"
            class="sticky top-22 flex w-full items-center justify-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-3 text-sm font-medium text-zinc-600 shadow-xs hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:text-white"
        >
            <flux:icon.chevron-left class="size-4" />
            {{ __('Show transaction rail') }}
        </button>
    @else
        <div class="sticky top-22 space-y-4 rounded-lg border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">{{ __('Pinned transactions') }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Active and recent deal files') }}</flux:text>
                </div>

                <flux:button size="sm" variant="ghost" icon="chevron-right" wire:click="toggleCollapsed" :aria-label="__('Collapse transaction rail')" />
            </div>

            <div class="grid grid-cols-2 gap-2">
                @foreach ([
                    'all' => __('All'),
                    \App\Models\Transaction::STATUS_ACTIVE => __('Active'),
                    \App\Models\Transaction::STATUS_PENDING_CLOSE => __('Closing'),
                    \App\Models\Transaction::STATUS_DRAFT => __('Drafts'),
                ] as $status => $label)
                    <button
                        type="button"
                        wire:click="setStatus('{{ $status }}')"
                        @class([
                            'rounded-md border px-2 py-1.5 text-left text-xs font-medium transition',
                            'border-[var(--c2c-primary)] bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-200' => $this->status === $status,
                            'border-zinc-200 bg-zinc-50 text-zinc-600 hover:bg-white dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800' => $this->status !== $status,
                        ])
                    >
                        <span class="block">{{ $label }}</span>
                        <span class="text-[11px] opacity-70">{{ $this->counts[$status] ?? 0 }}</span>
                    </button>
                @endforeach
            </div>

            @if ($this->transactions->isEmpty())
                <div class="rounded-lg border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    {{ __('No transactions match this filter yet.') }}
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($this->transactions as $transaction)
                        <a href="{{ route('transactions.index') }}" class="block rounded-lg border border-zinc-200 p-3 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:border-zinc-700 dark:hover:bg-zinc-800" wire:navigate>
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $transaction->name }}</div>
                                    <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $transaction->property_address ?? __('No address yet') }}</div>
                                </div>

                                <flux:badge size="sm" :color="$this->badgeColor($transaction->status)">{{ Str::headline($transaction->status) }}</flux:badge>
                            </div>

                            <div class="mt-3 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                                <span>{{ Str::headline($transaction->transaction_type) }}</span>
                                <span>{{ $transaction->opened_at?->diffForHumans() ?? $transaction->created_at?->diffForHumans() }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</aside>
</div>
