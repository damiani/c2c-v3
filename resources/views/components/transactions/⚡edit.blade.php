<?php

use App\Concerns\Transactions\ManagesTransactionFieldInputs;
use App\Contracts\Audit\AuditWriter;
use App\Models\Transaction;
use App\Models\TransactionTemplate;
use App\Transactions\SaveTransactionFields;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use ManagesTransactionFieldInputs;

    public Transaction $transaction;

    public ?int $templateId = null;

    public string $name = '';

    public string $status = Transaction::STATUS_DRAFT;

    public string $saveState = 'saved';

    public ?string $lastSavedAt = null;

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
        $this->lastSavedAt = $transaction->updated_at?->format('g:i A');

        $this->initializeTemplateSelection($transaction);
    }

    public function updated(string $property): void
    {
        if (! $this->shouldAutosave($property)) {
            return;
        }

        $this->autosave($property);
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
        $this->autosave();
    }

    private function autosave(?string $property = null): void
    {
        $this->saveState = 'saving';

        try {
            $property === null
                ? $this->validateTransaction()
                : $this->validateAutosavedProperty($property);

            $this->persistTransaction();
        } catch (ValidationException $exception) {
            $this->saveState = 'invalid';

            throw $exception;
        }

        $this->saveState = 'saved';
        $this->lastSavedAt = now()->format('g:i A');

        $this->dispatch('transaction-saved');
    }

    private function shouldAutosave(string $property): bool
    {
        return $property === 'name'
            || $property === 'status'
            || Str::startsWith($property, 'fieldInputs.');
    }

    private function validateTransaction(): void
    {
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
    }

    private function validateAutosavedProperty(string $property): void
    {
        if ($property === 'name') {
            $this->validateOnly('name', [
                'name' => ['required', 'string', 'max:255'],
            ], [], [
                'name' => __('transaction name'),
            ]);

            return;
        }

        if ($property === 'status') {
            $this->validateOnly('status', [
                'status' => ['required', Rule::in([
                    Transaction::STATUS_DRAFT,
                    Transaction::STATUS_ACTIVE,
                    Transaction::STATUS_PENDING_CLOSE,
                    Transaction::STATUS_CLOSED,
                    Transaction::STATUS_TERMINATED,
                ])],
            ], [], [
                'status' => __('status'),
            ]);

            return;
        }

        $definitionId = (int) Str::after($property, 'fieldInputs.');
        $field = $this->resolvedFields()->firstWhere('field_definition_id', $definitionId);

        if ($field === null) {
            return;
        }

        $this->validateOnly($property, [
            $property => array_merge([(bool) $field['is_required'] ? 'required' : 'nullable'], $this->rulesForDataType($field)),
        ], [], [
            $property => (string) $field['label'],
        ]);
    }

    private function persistTransaction(): void
    {
        $tenant = $this->currentTenant();
        $user = auth()->user();

        abort_if($tenant === null || $user === null || $this->transaction->tenant_id !== $tenant->id, 404);

        Gate::authorize('update', $this->transaction);

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
    }
};
?>

<div class="space-y-6" data-test="transaction-edit-form">
    <flux:card class="sticky top-4 z-20 space-y-5 rounded-lg border-zinc-200 bg-white/95 shadow-xs backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95">
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

                <div wire:loading wire:target="name,status,fieldInputs" class="inline-flex items-center">
                    <flux:badge color="blue" icon="arrow-path">{{ __('Saving') }}</flux:badge>
                </div>

                <div wire:loading.remove wire:target="name,status,fieldInputs">
                    @if ($saveState === 'invalid')
                        <flux:badge color="amber" icon="exclamation-triangle">{{ __('Review changes') }}</flux:badge>
                    @else
                        <flux:badge color="green" icon="check-circle">
                            {{ $lastSavedAt ? __('Saved :time', ['time' => $lastSavedAt]) : __('Saved') }}
                        </flux:badge>
                    @endif
                </div>
            </div>
        </div>
    </flux:card>

    <flux:card class="space-y-5 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Transaction name') }}</flux:label>
                <flux:input wire:model.live.debounce.700ms="name" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Status') }}</flux:label>
                <flux:select wire:model.live="status" variant="listbox">
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
                            <flux:field variant="inline" class="self-start">
                                <flux:checkbox wire:model.live="{{ $model }}" />
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_DATE)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:date-picker type="input" wire:model.live.debounce.700ms="{{ $model }}" clearable />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_DATETIME)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:input type="datetime-local" wire:model.live.debounce.700ms="{{ $model }}" />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_MONEY)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:input.group>
                                    <flux:input.group.prefix>{{ $field['value_schema']['currency'] ?? 'USD' }}</flux:input.group.prefix>
                                    <flux:input inputmode="decimal" mask:dynamic="$money($input)" wire:model.live.debounce.700ms="{{ $model }}" />
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
                                    <flux:input inputmode="decimal" mask:dynamic="$money($input)" wire:model.live.debounce.700ms="{{ $model }}" />
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
                                <flux:input inputmode="numeric" mask:dynamic="$money($input)" wire:model.live.debounce.700ms="{{ $model }}" />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_SELECT)
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:select wire:model.live="{{ $model }}" variant="listbox" placeholder="{{ __('Select an option') }}" clearable>
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
                                <flux:textarea wire:model.live.debounce.700ms="{{ $model }}" rows="4" />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                            @break

                        @default
                            <flux:field>
                                <flux:label>{{ $field['label'] }}</flux:label>
                                <flux:input wire:model.live.debounce.700ms="{{ $model }}" />
                                <flux:error name="{{ $model }}" />
                            </flux:field>
                    @endswitch
                @endforeach
            </div>
        </flux:card>
    @endforeach
</div>
</div>
