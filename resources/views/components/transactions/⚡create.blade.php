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

    public ?int $templateId = null;

    public string $name = '';

    public string $status = Transaction::STATUS_DRAFT;

    /**
     * @var array<int|string, mixed>
     */
    public array $fieldInputs = [];

    public function mount(): void
    {
        $tenant = $this->currentTenant();

        abort_if($tenant === null, 403);

        Gate::authorize('createForTenant', [Transaction::class, $tenant]);

        $this->initializeTemplateSelection();
    }

    public function updatedTemplateId(): void
    {
        $this->initializeFieldInputs();
    }

    #[Computed]
    public function templates()
    {
        return $this->availableTemplates();
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

        abort_if($tenant === null || $user === null, 403);

        Gate::authorize('createForTenant', [Transaction::class, $tenant]);

        $this->validate([
            'templateId' => ['required', Rule::exists('transaction_templates', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in([Transaction::STATUS_DRAFT, Transaction::STATUS_ACTIVE])],
            ...$this->dynamicFieldRules(),
        ], [], [
            'templateId' => __('transaction template'),
            'name' => __('transaction name'),
            'status' => __('status'),
            ...$this->dynamicFieldAttributes(),
        ]);

        $template = $this->selectedTemplate();

        abort_if(! $template instanceof TransactionTemplate, 404);

        $transaction = DB::transaction(function () use ($tenant, $user, $template): Transaction {
            $summary = $this->summaryDataFromInputs();

            $transaction = Transaction::query()->create([
                'tenant_id' => $tenant->id,
                'owner_user_id' => $user->id,
                'transaction_template_id' => $template->id,
                'transaction_template_version' => $template->version,
                'transaction_type' => $template->transaction_type,
                'status' => $this->status,
                'name' => $this->name,
                'property_address' => $summary['property_address'],
                'property_data' => $summary['property_data'],
                'field_schema_snapshot' => $this->fieldSchemaSnapshot($template),
                'metadata' => [
                    'created_from' => 'transaction_create_workflow',
                ],
                'opened_at' => now(),
            ]);

            app(SaveTransactionFields::class)->handle(
                transaction: $transaction,
                resolvedFields: $this->resolvedFields($template),
                fieldInputs: $this->fieldInputs,
                user: $user,
            );

            app(AuditWriter::class)->record(
                tenant: $tenant,
                action: 'transaction.created',
                actor: $user,
                subject: $transaction,
                metadata: [
                    'template_id' => $template->id,
                    'template_version' => $template->version,
                    'status' => $transaction->status,
                ],
            );

            return $transaction;
        });

        session()->flash('status', __('Transaction created.'));

        return redirect()->route('transactions.edit', $transaction);
    }
};
?>

<form wire:submit="save" class="space-y-6" data-test="transaction-create-form">
    <section class="rounded-lg border border-zinc-200 bg-white p-5 shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <flux:heading size="xl" level="1">{{ __('New transaction') }}</flux:heading>
                <flux:text class="max-w-2xl text-base text-zinc-600 dark:text-zinc-300">
                    {{ __('Choose a template, enter the starting deal facts, and save a tenant-scoped transaction file.') }}
                </flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button variant="outline" :href="route('transactions.index')" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    {{ __('Create transaction') }}
                </flux:button>
            </div>
        </div>
    </section>

    <flux:card class="space-y-5 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-3">
            <flux:select wire:model.live="templateId" variant="listbox" :label="__('Template')">
                @foreach ($this->templates as $template)
                    <flux:select.option value="{{ $template->id }}">{{ $template->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="name" :label="__('Transaction name')" :placeholder="__('123 Main St sale')" />

            <flux:select wire:model="status" variant="listbox" :label="__('Starting status')">
                <flux:select.option value="{{ \App\Models\Transaction::STATUS_DRAFT }}">{{ __('Draft') }}</flux:select.option>
                <flux:select.option value="{{ \App\Models\Transaction::STATUS_ACTIVE }}">{{ __('Active') }}</flux:select.option>
            </flux:select>
        </div>
    </flux:card>

    @foreach ($this->fieldGroups as $section => $fields)
        <flux:card class="space-y-5 rounded-lg border-zinc-200 bg-white shadow-xs dark:border-zinc-800 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg" level="2">{{ $this->sectionLabel($section) }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Template fields resolve tenant, team, and user display preferences.') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($fields as $field)
                    @php
                        $definitionId = (int) $field['field_definition_id'];
                        $model = 'fieldInputs.'.$definitionId;
                    @endphp

                    @switch($field['data_type'])
                        @case(\App\Models\TransactionFieldDefinition::TYPE_BOOLEAN)
                            <flux:checkbox wire:model="{{ $model }}" label="{{ $field['label'] }}" />
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_DATE)
                            <flux:date-picker type="input" wire:model="{{ $model }}" label="{{ $field['label'] }}" clearable />
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_DATETIME)
                            <flux:input type="datetime-local" wire:model="{{ $model }}" label="{{ $field['label'] }}" />
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
                            <flux:input type="number" step="1" wire:model="{{ $model }}" label="{{ $field['label'] }}" />
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_SELECT)
                            <flux:select wire:model="{{ $model }}" variant="listbox" placeholder="{{ __('Select an option') }}" label="{{ $field['label'] }}" clearable>
                                @foreach ($this->optionsFor($field) as $optionKey => $optionLabel)
                                    <flux:select.option value="{{ $optionKey }}">{{ $optionLabel }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @break

                        @case(\App\Models\TransactionFieldDefinition::TYPE_LONG_TEXT)
                            <flux:textarea wire:model="{{ $model }}" label="{{ $field['label'] }}" rows="4" />
                            @break

                        @default
                            <flux:input wire:model="{{ $model }}" label="{{ $field['label'] }}" />
                    @endswitch
                @endforeach
            </div>
        </flux:card>
    @endforeach
</form>
</div>
