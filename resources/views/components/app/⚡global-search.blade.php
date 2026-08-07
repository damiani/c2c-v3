<?php

use App\Models\Contact;
use App\Models\Document;
use App\Models\Form;
use App\Models\Transaction;
use App\Tenancy\CurrentTenant;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $modalName = 'global-search';

    public bool $compact = false;

    public string $query = '';

    /**
     * Build tenant-scoped global search results for the app shell.
     *
     * @return array<string, list<array{label: string, meta: string, href: string, badge?: string}>>
     */
    #[Computed]
    public function results(): array
    {
        $query = trim($this->query);
        $tenant = app(CurrentTenant::class)->get();

        if ($tenant === null || mb_strlen($query) < 2) {
            return [
                'transactions' => [],
                'contacts' => [],
                'documents' => [],
                'forms' => [],
            ];
        }

        return [
            'transactions' => Transaction::query()
                ->forTenant($tenant->id)
                ->where(function ($builder) use ($query): void {
                    $builder
                        ->where('name', 'like', "%{$query}%")
                        ->orWhere('property_address', 'like', "%{$query}%")
                        ->orWhere('status', 'like', "%{$query}%");
                })
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (Transaction $transaction) => [
                    'label' => $transaction->name,
                    'meta' => $transaction->property_address ?? Str::headline($transaction->transaction_type),
                    'href' => route('transactions.index'),
                    'badge' => Str::headline($transaction->status),
                ])
                ->all(),
            'contacts' => Contact::query()
                ->forTenant($tenant->id)
                ->where(function ($builder) use ($query): void {
                    $builder
                        ->where('display_name', 'like', "%{$query}%")
                        ->orWhere('company_name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (Contact $contact) => [
                    'label' => $contact->display_name,
                    'meta' => $contact->company_name ?? Str::headline($contact->contact_type),
                    'href' => route('contacts.index'),
                ])
                ->all(),
            'documents' => Document::query()
                ->forTenant($tenant->id)
                ->where(function ($builder) use ($query): void {
                    $builder
                        ->where('title', 'like', "%{$query}%")
                        ->orWhere('original_filename', 'like', "%{$query}%")
                        ->orWhere('document_type', 'like', "%{$query}%");
                })
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (Document $document) => [
                    'label' => $document->title,
                    'meta' => $document->original_filename,
                    'href' => route('documents.index'),
                    'badge' => Str::headline($document->status),
                ])
                ->all(),
            'forms' => Form::query()
                ->forTenant($tenant->id)
                ->where(function ($builder) use ($query): void {
                    $builder
                        ->where('title', 'like', "%{$query}%")
                        ->orWhere('form_type', 'like', "%{$query}%")
                        ->orWhere('source', 'like', "%{$query}%");
                })
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (Form $form) => [
                    'label' => $form->title,
                    'meta' => Str::headline($form->form_type ?? $form->source),
                    'href' => route('forms.index'),
                    'badge' => Str::headline($form->source),
                ])
                ->all(),
        ];
    }

    public function resultCount(): int
    {
        return collect($this->results)
            ->sum(fn (array $items) => count($items));
    }
};
?>

<div>
    <flux:modal.trigger :name="$modalName">
        <button
            type="button"
            class="flex h-10 w-full items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-left text-sm text-zinc-500 shadow-xs transition hover:bg-white hover:text-zinc-700 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800"
            data-test="global-search-trigger"
        >
            <flux:icon.magnifying-glass class="size-4 shrink-0" />
            <span class="min-w-0 flex-1 truncate">
                {{ $compact ? __('Search') : __('Search transactions, contacts, documents, forms') }}
            </span>
            @unless ($compact)
                <kbd class="hidden rounded border border-zinc-200 bg-white px-1.5 py-0.5 text-[11px] font-medium text-zinc-400 sm:inline dark:border-zinc-700 dark:bg-zinc-950">/</kbd>
            @endunless
        </button>
    </flux:modal.trigger>

    <flux:modal :name="$modalName" class="md:w-2xl">
        <div class="space-y-5">
            <div class="space-y-1">
                <flux:heading size="lg">{{ __('Global search') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-300">
                    {{ __('Find tenant-scoped transactions, contacts, documents, and forms.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model.live.debounce.250ms="query"
                icon="magnifying-glass"
                :placeholder="__('Search by address, name, file, or form')"
                autofocus
                data-test="global-search-input"
            />

            <div wire:loading.delay class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-sm text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/30 dark:text-blue-200">
                {{ __('Searching...') }}
            </div>

            @if (mb_strlen(trim($query)) < 2)
                <div class="rounded-lg border border-dashed border-zinc-200 p-6 text-center dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('Start with at least two characters') }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Try a property address, client name, document title, or form type.') }}</flux:text>
                </div>
            @elseif ($this->resultCount() === 0)
                <div class="rounded-lg border border-zinc-200 p-6 text-center dark:border-zinc-700">
                    <flux:heading size="sm">{{ __('No matching records') }}</flux:heading>
                    <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Only records in the current tenant are searched.') }}</flux:text>
                </div>
            @else
                <div class="max-h-[28rem] space-y-5 overflow-y-auto pr-1">
                    @foreach ($this->results as $section => $items)
                        @if ($items !== [])
                            <section class="space-y-2" data-test="global-search-section-{{ $section }}">
                                <div class="text-xs font-semibold uppercase text-zinc-500 dark:text-zinc-400">{{ __(Str::headline($section)) }}</div>

                                <div class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 bg-white dark:divide-zinc-800 dark:border-zinc-800 dark:bg-zinc-900">
                                    @foreach ($items as $item)
                                        <a href="{{ $item['href'] }}" class="flex items-center justify-between gap-4 px-3 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800" wire:navigate>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $item['label'] }}</span>
                                                <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $item['meta'] }}</span>
                                            </span>

                                            @isset($item['badge'])
                                                <flux:badge size="sm" color="blue">{{ $item['badge'] }}</flux:badge>
                                            @endisset
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </flux:modal>
</div>
