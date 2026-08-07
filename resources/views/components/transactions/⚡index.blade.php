<?php

use App\Models\Tenant;
use App\Models\Transaction;
use App\Tenancy\CurrentTenant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $status = 'all';

    public string $search = '';

    public function mount(): void
    {
        $tenant = $this->tenant();

        abort_if($tenant === null, 403);

        Gate::authorize('viewAnyForTenant', [Transaction::class, $tenant]);
    }

    public function setStatus(string $status): void
    {
        if (! in_array($status, ['all', Transaction::STATUS_DRAFT, Transaction::STATUS_ACTIVE, Transaction::STATUS_PENDING_CLOSE, Transaction::STATUS_CLOSED, Transaction::STATUS_TERMINATED], true)) {
            return;
        }

        $this->status = $status;
    }

    #[Computed]
    public function transactions()
    {
        $tenant = $this->tenant();
        $user = auth()->user();

        if ($tenant === null || $user === null) {
            return collect();
        }

        return Transaction::query()
            ->visibleTo($user, $tenant)
            ->with(['owner', 'template'])
            ->withCount(['fieldValues', 'milestones'])
            ->when($this->status !== 'all', fn ($query) => $query->where('status', $this->status))
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('property_address', 'like', '%'.$this->search.'%');
                });
            })
            ->latest('updated_at')
            ->limit(25)
            ->get();
    }

    #[Computed]
    public function counts(): array
    {
        $tenant = $this->tenant();
        $user = auth()->user();

        if ($tenant === null || $user === null) {
            return [];
        }

        $baseQuery = Transaction::query()->visibleTo($user, $tenant);

        return [
            'all' => (clone $baseQuery)->count(),
            Transaction::STATUS_DRAFT => (clone $baseQuery)->where('status', Transaction::STATUS_DRAFT)->count(),
            Transaction::STATUS_ACTIVE => (clone $baseQuery)->where('status', Transaction::STATUS_ACTIVE)->count(),
            Transaction::STATUS_PENDING_CLOSE => (clone $baseQuery)->where('status', Transaction::STATUS_PENDING_CLOSE)->count(),
            Transaction::STATUS_CLOSED => (clone $baseQuery)->where('status', Transaction::STATUS_CLOSED)->count(),
            Transaction::STATUS_TERMINATED => (clone $baseQuery)->where('status', Transaction::STATUS_TERMINATED)->count(),
        ];
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

    private function tenant(): ?Tenant
    {
        return app(CurrentTenant::class)->get();
    }
};
?>

<div class="space-y-6" data-test="transactions-index">
    <flux:card class="rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="space-y-2">
                <flux:heading size="xl" level="1">{{ __('Transactions') }}</flux:heading>
                <flux:text class="max-w-2xl text-base text-zinc-600 dark:text-zinc-300">
                    {{ __('Create and maintain template-driven transaction files with tenant-scoped dynamic fields.') }}
                </flux:text>
            </div>

            <flux:button variant="primary" icon="plus" :href="route('transactions.create')" wire:navigate>
                {{ __('New transaction') }}
            </flux:button>
        </div>
    </flux:card>

    <flux:card class="rounded-lg border-zinc-200 bg-white p-0 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 md:flex-row md:items-center md:justify-between dark:border-zinc-800">
            <div class="flex flex-wrap gap-2">
                @foreach ([
                    'all' => __('All'),
                    \App\Models\Transaction::STATUS_DRAFT => __('Drafts'),
                    \App\Models\Transaction::STATUS_ACTIVE => __('Active'),
                    \App\Models\Transaction::STATUS_PENDING_CLOSE => __('Closing'),
                    \App\Models\Transaction::STATUS_CLOSED => __('Closed'),
                    \App\Models\Transaction::STATUS_TERMINATED => __('Terminated'),
                ] as $filterStatus => $label)
                    <flux:button
                        size="sm"
                        :variant="$this->status === $filterStatus ? 'primary' : 'outline'"
                        wire:click="setStatus('{{ $filterStatus }}')"
                    >
                        {{ $label }} {{ $this->counts[$filterStatus] ?? 0 }}
                    </flux:button>
                @endforeach
            </div>

            <div class="w-full md:max-w-xs">
                <flux:input size="sm" icon="magnifying-glass" wire:model.live.debounce.300ms="search" :placeholder="__('Search transactions')" clearable />
            </div>
        </div>

        @if ($this->transactions->isEmpty())
            <div class="p-10 text-center">
                <flux:heading size="lg">{{ __('No transactions found') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                    {{ __('Create a transaction to start entering deal data and tracking progress.') }}
                </flux:text>
                <flux:button class="mt-5" variant="primary" icon="plus" :href="route('transactions.create')" wire:navigate>
                    {{ __('New transaction') }}
                </flux:button>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Transaction') }}</flux:table.column>
                    <flux:table.column>{{ __('Template') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Fields') }}</flux:table.column>
                    <flux:table.column>{{ __('Milestones') }}</flux:table.column>
                    <flux:table.column>{{ __('Updated') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach ($this->transactions as $transaction)
                        <flux:table.row :key="$transaction->id">
                            <flux:table.cell>
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $transaction->name }}</div>
                                    <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $transaction->property_address ?? __('No address yet') }}</div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $transaction->template?->name ?? Str::headline($transaction->transaction_type) }}</flux:table.cell>
                            <flux:table.cell class="py-0">
                                <flux:badge size="sm" :color="$this->badgeColor($transaction->status)">{{ Str::headline($transaction->status) }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>{{ $transaction->field_values_count }}</flux:table.cell>
                            <flux:table.cell>{{ $transaction->milestones_count }}</flux:table.cell>
                            <flux:table.cell>{{ $transaction->updated_at?->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="sm" variant="outline" icon="pencil-square" :href="route('transactions.edit', $transaction)" wire:navigate>
                                    {{ __('Edit') }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @endif
    </flux:card>
</div>
