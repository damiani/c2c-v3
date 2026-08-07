<?php

use App\Concerns\Transactions\ManagesTransactionFieldInputs;
use App\Contracts\Audit\AuditWriter;
use App\Models\Transaction;
use App\Models\TransactionTemplate;
use App\Transactions\SaveTransactionFields;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use ManagesTransactionFieldInputs;

    public Transaction $transaction;

    public ?int $templateId = null;

    public string $name = '';

    public string $status = Transaction::STATUS_DRAFT;

    /**
     * @var array<int|string, mixed>
     */
    public array $fieldInputs = [];

    public function mount(Transaction $transaction): void
    {
        $tenant = $this->currentTenant();

        abort_if($tenant === null || $transaction->tenant_id !== $tenant->id, 404);

        Gate::authorize('update', $transaction);

        $this->transaction = $transaction;
        $this->name = $transaction->name;
        $this->status = $transaction->status;

        $this->initializeTemplateSelection($transaction);
    }

    #[Computed]
    public function fieldGroups()
    {
        return $this->groupedResolvedFields();
    }

    public function optionsFor(array $field): array
    {
        return $this->normalizedOptions($field);
    }

    public function sectionLabel(string $section): string
    {
        return Str::headline($section);
    }

    public function save()
    {
        $tenant = $this->currentTenant();
        $user = auth()->user();

        abort_if($tenant === null || $user === null || $this->transaction->tenant_id !== $tenant->id, 404);

        Gate::authorize('update', $this->transaction);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in([
                Transaction::STATUS_DRAFT,
                Transaction::STATUS_ACTIVE,
                Transaction::STATUS_PENDING_CLOSE,
                Transaction::STATUS_CLOSED,
                Transaction::STATUS_TERMINATED,
            ])],
            ...$this->dynamicFieldRules(),
        ], [], [
            'name' => __('transaction name'),
            'status' => __('status'),
            ...$this->dynamicFieldAttributes(),
        ]);

        $template = $this->selectedTemplate();

        abort_if(! $template instanceof TransactionTemplate, 404);

        DB::transaction(function () use ($tenant, $user, $template): void {
            $summary = $this->summaryDataFromInputs();

            $this->transaction->update([
                'name' => $this->name,
                'status' => $this->status,
                'property_address' => $summary['property_address'],
                'property_data' => $summary['property_data'],
                'field_schema_snapshot' => $this->transaction->field_schema_snapshot ?? $this->fieldSchemaSnapshot($template),
            ]);

            app(SaveTransactionFields::class)->handle(
                transaction: $this->transaction,
                resolvedFields: $this->resolvedFields($template),
                fieldInputs: $this->fieldInputs,
                user: $user,
            );

            app(AuditWriter::class)->record(
                tenant: $tenant,
                action: 'transaction.updated',
                actor: $user,
                subject: $this->transaction,
                metadata: [
                    'template_id' => $template->id,
                    'template_version' => $template->version,
                    'status' => $this->transaction->status,
                ],
            );
        });

        $this->dispatch('transaction-saved');

        session()->flash('status', __('Transaction updated.'));
    }
};
?>

<form wire:submit="save" class="space-y-6" data-test="transaction-edit-form">
    <flux:card class="space-y-5 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:badge color="blue">{{ $this->selectedTemplate()?->name ?? __('Template') }}</flux:badge>
                    <flux:badge :color="$this->transaction->status === \App\Models\Transaction::STATUS_ACTIVE ? 'green' : 'zinc'">{{ Str::headline($this->status) }}</flux:badge>
                </div>
                <flux:heading size="xl" level="1">{{ $this->transaction->name }}</flux:heading>
                <flux:text class="max-w-2xl text-base text-zinc-600 dark:text-zinc-300">
                    {{ __('Edit transaction details through the pinned template fields and save canonical typed values.') }}
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button variant="outline" :href="route('transactions.index')" wire:navigate>
                    {{ __('Back to transactions') }}
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    {{ __('Save changes') }}
                </flux:button>
            </div>
        </div>

        @if (session('status'))
            <flux:callout class="mt-5" color="green" icon="check-circle">
                {{ session('status') }}
            </flux:callout>
        @endif
    </flux:card>

    <flux:card class="space-y-5 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Transaction name') }}</flux:label>
                <flux:input wire:model="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Status') }}</flux:label>
                <flux:select wire:model="status" variant="listbox">
                    <flux:select.option value="{{ \App\Models\Transaction::STATUS_DRAFT }}">{{ __('Draft') }}</flux:select.option>
                    <flux:select.option value="{{ \App\Models\Transaction::STATUS_ACTIVE }}">{{ __('Active') }}</flux:select.option>
                    <flux:select.option value="{{ \App\Models\Transaction::STATUS_PENDING_CLOSE }}">{{ __('Pending close') }}</flux:select.option>
                    <flux:select.option value="{{ \App\Models\Transaction::STATUS_CLOSED }}">{{ __('Closed') }}</flux:select.option>
                    <flux:select.option value="{{ \App\Models\Transaction::STATUS_TERMINATED }}">{{ __('Terminated') }}</flux:select.option>
                </flux:select>
                <flux:error name="status" />
            </flux:field>
        </div>
    </flux:card>

    @foreach ($this->fieldGroups as $section => $fields)
        <flux:card class="space-y-5 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg" level="2">{{ $this->sectionLabel($section) }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Changes update typed transaction values without changing the pinned template version.') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($fields as $field)
                    @php
                        $definitionId = (int) $field['field_definition_id'];
                        $model = 'fieldInputs.'.$definitionId;
                    @endphp

                    @switch($field['data_type'])
                        @case(\App\Models\TransactionFieldDefinition::TYPE_BOOLEAN)
                            <flux:field variant="inline">
                                <flux:checkbox wire:model="{{ $model }}" />
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_DATE)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:date-picker type="input" wire:model="{{ $model }}" clearable />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_DATETIME)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:input type="datetime-local" wire:model="{{ $model }}" />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_MONEY)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:input.group>
                                    <flux:input.group.prefix>{{ $field['value_schema']['currency'] ?? 'USD' }}</flux:input.group.prefix>
                                    <flux:input type="number" step="0.01" min="0" wire:model="{{ $model }}" />
                                </flux:input.group>
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_QUANTITY)
                        @case(\App\Models\TransactionFieldDefinition::TYPE_DECIMAL)
                        @case(\App\Models\TransactionFieldDefinition::TYPE_PERCENTAGE)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:input.group>
                                    <flux:input type="number" step="0.01" wire:model="{{ $model }}" />
                                    @if ($field['unit'])
                                        <flux:input.group.suffix>{{ Str::headline($field['unit']) }}</flux:input.group.suffix>
                                    @endif
                                </flux:input.group>
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_INTEGER)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:input type="number" step="1" wire:model="{{ $model }}" />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_SELECT)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:select wire:model="{{ $model }}" variant="listbox" placeholder="{{ __('Select an option') }}" clearable>
                                    @foreach ($this->optionsFor($field) as $optionKey => $optionLabel)
                                        <flux:select.option value="{{ $optionKey }}">{{ $optionLabel }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_LONG_TEXT)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:textarea wire:model="{{ $model }}" rows="4" />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @default
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:input wire:model="{{ $model }}" />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                    @endswitch
                @endforeach
            </div>
        </flux:card>
    @endforeach
</form>
</div>
