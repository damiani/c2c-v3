<?php

use App\Models\Document;
use App\Models\Milestone;
use App\Models\TenantMembership;
use App\Models\Transaction;
use App\Tenancy\CurrentTenant;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    #[Computed]
    public function tenant()
    {
        return app(CurrentTenant::class)->get();
    }

    #[Computed]
    public function membership(): ?TenantMembership
    {
        $user = auth()->user();

        if ($user === null || $this->tenant === null) {
            return null;
        }

        return $user->membershipForTenant($this->tenant);
    }

    /**
     * @return array{label: string, description: string, primaryMetric: string}
     */
    #[Computed]
    public function roleDashboard(): array
    {
        return match ($this->membership?->role) {
            TenantMembership::ROLE_COORDINATOR => [
                'label' => __('Coordinator dashboard'),
                'description' => __('Prioritize deadlines, approvals, document reviews, and stalled files across assigned transactions.'),
                'primaryMetric' => __('Deadlines due'),
            ],
            TenantMembership::ROLE_BACK_OFFICE => [
                'label' => __('Back-office dashboard'),
                'description' => __('Track review queues, signing readiness, compliance status, and commission release dependencies.'),
                'primaryMetric' => __('Review items'),
            ],
            TenantMembership::ROLE_OWNER, TenantMembership::ROLE_ADMIN, TenantMembership::ROLE_BROKER_ADMIN => [
                'label' => __('Broker/Admin dashboard'),
                'description' => __('See team transaction volume, pending closings, risk items, and partner-tab activity across the tenant.'),
                'primaryMetric' => __('Active files'),
            ],
            default => [
                'label' => __('Agent dashboard'),
                'description' => __('Focus on your active transactions, client-facing deadlines, document requests, and next best actions.'),
                'primaryMetric' => __('My active files'),
            ],
        };
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        if ($this->tenant === null) {
            return [
                'activeTransactions' => 0,
                'pendingClosings' => 0,
                'pendingMilestones' => 0,
                'reviewDocuments' => 0,
            ];
        }

        return [
            'activeTransactions' => Transaction::query()
                ->forTenant($this->tenant->id)
                ->where('status', Transaction::STATUS_ACTIVE)
                ->count(),
            'pendingClosings' => Transaction::query()
                ->forTenant($this->tenant->id)
                ->where('status', Transaction::STATUS_PENDING_CLOSE)
                ->count(),
            'pendingMilestones' => Milestone::query()
                ->forTenant($this->tenant->id)
                ->where('status', Milestone::STATUS_PENDING)
                ->count(),
            'reviewDocuments' => Document::query()
                ->forTenant($this->tenant->id)
                ->where('status', Document::STATUS_IN_REVIEW)
                ->count(),
        ];
    }

    #[Computed]
    public function actionItems()
    {
        if ($this->tenant === null) {
            return collect();
        }

        $milestones = Milestone::query()
            ->forTenant($this->tenant->id)
            ->with('transaction')
            ->where('status', Milestone::STATUS_PENDING)
            ->orderBy('due_at')
            ->limit(5)
            ->get()
            ->map(fn (Milestone $milestone) => [
                'type' => __('Deadline'),
                'label' => $milestone->title,
                'meta' => $milestone->transaction?->name ?? __('Unassigned transaction'),
                'due' => $milestone->due_at?->diffForHumans() ?? __('No due date'),
                'color' => $milestone->due_at && $milestone->due_at->isPast() ? 'red' : 'amber',
            ]);

        $documents = Document::query()
            ->forTenant($this->tenant->id)
            ->with('transaction')
            ->where('status', Document::STATUS_IN_REVIEW)
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn (Document $document) => [
                'type' => __('Review'),
                'label' => $document->title,
                'meta' => $document->transaction?->name ?? $document->original_filename,
                'due' => __('Needs review'),
                'color' => 'blue',
            ]);

        return $milestones
            ->concat($documents)
            ->take(6)
            ->values();
    }

    #[Computed]
    public function recentTransactions()
    {
        if ($this->tenant === null) {
            return collect();
        }

        return Transaction::query()
            ->forTenant($this->tenant->id)
            ->withCount(['documents', 'milestones'])
            ->latest('updated_at')
            ->limit(6)
            ->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function partnerTabs(): array
    {
        return collect($this->tenant?->enabled_integrations ?? [])
            ->mapWithKeys(fn (string $integration) => [
                $integration => Str::headline($integration),
            ])
            ->all();
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

<div class="space-y-6" data-test="dashboard-overview">
    <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl space-y-2">
                <div class="text-sm font-medium text-[var(--c2c-primary)]">{{ $this->tenant?->brandedName() ?? __('Contract2Close') }}</div>
                <flux:heading size="xl" level="1">{{ $this->roleDashboard['label'] }}</flux:heading>
                <flux:text class="text-base text-zinc-600 dark:text-zinc-300">
                    {{ $this->roleDashboard['description'] }}
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button variant="primary" icon="plus" :href="route('transactions.index')" wire:navigate>
                    {{ __('New transaction') }}
                </flux:button>
                <flux:button variant="outline" icon="document-plus" :href="route('documents.index')" wire:navigate>
                    {{ __('Upload document') }}
                </flux:button>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('Dashboard summary') }}">
        @foreach ([
            ['label' => $this->roleDashboard['primaryMetric'], 'value' => $this->stats['activeTransactions'], 'hint' => __('Open tenant transactions'), 'icon' => 'building-office-2'],
            ['label' => __('Pending closings'), 'value' => $this->stats['pendingClosings'], 'hint' => __('Transactions moving toward close'), 'icon' => 'calendar-days'],
            ['label' => __('Open deadlines'), 'value' => $this->stats['pendingMilestones'], 'hint' => __('Milestones not yet complete'), 'icon' => 'clock'],
            ['label' => __('Documents in review'), 'value' => $this->stats['reviewDocuments'], 'hint' => __('Files awaiting review action'), 'icon' => 'document-magnifying-glass'],
        ] as $stat)
            <flux:card class="space-y-4 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div class="space-y-1">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $stat['label'] }}</flux:text>
                        <div class="text-3xl font-semibold text-zinc-950 dark:text-white">{{ $stat['value'] }}</div>
                    </div>
                    <div class="flex size-10 items-center justify-center rounded-lg bg-blue-50 text-[var(--c2c-primary)] dark:bg-blue-950/30">
                        <flux:icon :name="$stat['icon']" class="size-5" />
                    </div>
                </div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $stat['hint'] }}</flux:text>
            </flux:card>
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <flux:card class="rounded-lg border-zinc-200 bg-white p-0 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <div>
                    <flux:heading size="lg" level="2">{{ __('Action queue') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Deadlines and review work that need attention first.') }}</flux:text>
                </div>

                <flux:badge color="blue">{{ $this->actionItems->count() }}</flux:badge>
            </div>

            @if ($this->actionItems->isEmpty())
                <div class="p-8 text-center">
                    <flux:heading size="sm">{{ __('No urgent actions yet') }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('As transactions, milestones, and review documents are added, priority work will appear here.') }}</flux:text>
                </div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->actionItems as $item)
                        <div class="flex items-start justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <flux:badge size="sm" :color="$item['color']">{{ $item['type'] }}</flux:badge>
                                    <span class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $item['label'] }}</span>
                                </div>
                                <div class="mt-1 truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $item['meta'] }}</div>
                            </div>
                            <div class="shrink-0 text-right text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ $item['due'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        <flux:card class="space-y-4 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg" level="2">{{ __('Role focus') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Dashboard emphasis follows your tenant role.') }}</flux:text>
            </div>

            <div class="space-y-3">
                @foreach ([
                    __('Agent') => __('Client deadlines, documents, and personal active files.'),
                    __('Coordinator/Assistant') => __('Cross-file deadlines, review blockers, and stalled workflow.'),
                    __('Broker/Admin') => __('Team volume, risk, partner visibility, and tenant configuration.'),
                    __('Back-Office') => __('Compliance readiness, approval queues, and release blockers.'),
                ] as $label => $description)
                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-800">
                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $label }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</div>
                    </div>
                @endforeach
            </div>
        </flux:card>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <flux:card class="rounded-lg border-zinc-200 bg-white p-0 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <div>
                    <flux:heading size="lg" level="2">{{ __('Recent transactions') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tenant-scoped activity for quick scanning.') }}</flux:text>
                </div>

                <flux:button size="sm" variant="outline" :href="route('transactions.index')" wire:navigate>
                    {{ __('View all') }}
                </flux:button>
            </div>

            @if ($this->recentTransactions->isEmpty())
                <div class="p-8 text-center">
                    <flux:heading size="sm">{{ __('No transactions yet') }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Create the first transaction to populate this dashboard and the pinned rail.') }}</flux:text>
                </div>
            @else
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Transaction') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Documents') }}</flux:table.column>
                        <flux:table.column>{{ __('Milestones') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($this->recentTransactions as $transaction)
                            <flux:table.row :key="$transaction->id">
                                <flux:table.cell>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium">{{ $transaction->name }}</div>
                                        <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $transaction->property_address ?? Str::headline($transaction->transaction_type) }}</div>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="py-0">
                                    <flux:badge size="sm" :color="$this->badgeColor($transaction->status)">{{ Str::headline($transaction->status) }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell>{{ $transaction->documents_count }}</flux:table.cell>
                                <flux:table.cell>{{ $transaction->milestones_count }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>

        <flux:card id="partner-tabs" class="space-y-4 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg" level="2">{{ __('Partner tabs') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tenant-enabled integrations stay visible in the dashboard and navigation.') }}</flux:text>
            </div>

            @if ($this->partnerTabs === [])
                <div class="rounded-lg border border-dashed border-zinc-200 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    {{ __('No partner tabs are enabled for this tenant yet.') }}
                </div>
            @else
                <div class="space-y-2">
                    @foreach ($this->partnerTabs as $slug => $label)
                        <a id="partner-{{ $slug }}" href="{{ route('tenant.edit') }}" class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-3 text-sm hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800" wire:navigate>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $label }}</span>
                            <flux:badge size="sm" color="green">{{ __('Enabled') }}</flux:badge>
                        </a>
                    @endforeach
                </div>
            @endif
        </flux:card>
    </section>
</div>
