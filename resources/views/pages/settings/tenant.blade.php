<?php

use App\Models\Tenant;
use App\Tenancy\CurrentTenant;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tenant settings')] class extends Component
{
    #[Locked]
    public int $tenantId;

    public string $display_name = '';

    public string $logo_path = '';

    public string $primary_color = '#2563eb';

    public string $accent_color = '#16a34a';

    public string $sender_name = '';

    public string $sender_email = '';

    public string $default_locale = 'en';

    /** @var list<string> */
    public array $supported_locales = [];

    /** @var list<string> */
    public array $enabled_integrations = [];

    public function mount(): void
    {
        $tenant = $this->resolvedTenant();

        $this->authorizeTenantSettings($tenant);

        $this->tenantId = $tenant->id;
        $this->display_name = $tenant->display_name ?? '';
        $this->logo_path = $tenant->logo_path ?? '';
        $this->primary_color = $tenant->primary_color;
        $this->accent_color = $tenant->accent_color;
        $this->sender_name = $tenant->sender_name ?? '';
        $this->sender_email = $tenant->sender_email ?? '';
        $this->default_locale = $tenant->default_locale;
        $this->supported_locales = $tenant->supported_locales ?? [$tenant->default_locale];
        $this->enabled_integrations = $tenant->enabled_integrations ?? [];
    }

    public function updateTenantSettings(): void
    {
        $tenant = $this->resolvedTenant();

        $this->authorizeTenantSettings($tenant);

        $validated = $this->validate();
        $supportedLocales = $this->uniqueStringList($validated['supported_locales']);

        if (! in_array($validated['default_locale'], $supportedLocales, true)) {
            throw ValidationException::withMessages([
                'default_locale' => __('The default language must be enabled for this tenant.'),
            ]);
        }

        $tenant->fill([
            'display_name' => $this->nullableString($validated['display_name']),
            'logo_path' => $this->nullableString($validated['logo_path']),
            'primary_color' => strtolower($validated['primary_color']),
            'accent_color' => strtolower($validated['accent_color']),
            'sender_name' => $this->nullableString($validated['sender_name']),
            'sender_email' => $this->nullableString($validated['sender_email']),
            'default_locale' => $validated['default_locale'],
            'supported_locales' => $supportedLocales,
            'enabled_integrations' => $this->uniqueStringList($validated['enabled_integrations'] ?? []),
        ]);

        $tenant->save();

        Flux::toast(variant: 'success', text: __('Tenant settings updated.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'sender_email' => ['nullable', 'email', 'max:255'],
            'default_locale' => ['required', 'string', Rule::in($this->localeKeys())],
            'supported_locales' => ['required', 'array', 'min:1'],
            'supported_locales.*' => ['required', 'string', Rule::in($this->localeKeys())],
            'enabled_integrations' => ['array'],
            'enabled_integrations.*' => ['required', 'string', Rule::in($this->integrationKeys())],
        ];
    }

    private function resolvedTenant(): Tenant
    {
        $tenant = app(CurrentTenant::class)->get();

        abort_if($tenant === null, 404);

        if (isset($this->tenantId)) {
            abort_unless($tenant->id === $this->tenantId, 403);
        }

        return $tenant;
    }

    private function authorizeTenantSettings(Tenant $tenant): void
    {
        Gate::authorize('manageBranding', $tenant);
        Gate::authorize('manageIntegrations', $tenant);
    }

    /**
     * @return list<string>
     */
    private function localeKeys(): array
    {
        return array_keys(config('localization.supported_locales', []));
    }

    /**
     * @return list<string>
     */
    private function integrationKeys(): array
    {
        return array_keys(config('tenant_settings.integrations', []));
    }

    /**
     * @param  array<int, string>  $values
     * @return list<string>
     */
    private function uniqueStringList(array $values): array
    {
        return array_values(array_unique($values));
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
};
?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Tenant settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Tenant')" :subheading="__('Manage tenant branding, locales, and integrations')">
        <form wire:submit="updateTenantSettings" class="my-6 w-full space-y-6">
            <flux:input wire:model="display_name" :label="__('Display name')" type="text" autocomplete="organization" />

            <flux:input wire:model="logo_path" :label="__('Logo path')" type="text" autocomplete="off" />

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="primary_color" :label="__('Primary color')" type="color" required />
                <flux:input wire:model="accent_color" :label="__('Accent color')" type="color" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="sender_name" :label="__('Sender name')" type="text" autocomplete="organization" />
                <flux:input wire:model="sender_email" :label="__('Sender email')" type="email" autocomplete="email" />
            </div>

            <flux:select wire:model="default_locale" :label="__('Default language')" required>
                @foreach (config('localization.supported_locales') as $locale => $name)
                    <flux:select.option :value="$locale">{{ __($name) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:checkbox.group wire:model="supported_locales" :label="__('Supported languages')">
                @foreach (config('localization.supported_locales') as $locale => $name)
                    <flux:checkbox :value="$locale" :label="__($name)" wire:key="tenant-supported-locale-{{ $locale }}" />
                @endforeach
            </flux:checkbox.group>

            <flux:checkbox.group wire:model="enabled_integrations" :label="__('Visible integrations')">
                @foreach (config('tenant_settings.integrations') as $integration => $label)
                    <flux:checkbox :value="$integration" :label="__($label)" wire:key="tenant-integration-{{ $integration }}" />
                @endforeach
            </flux:checkbox.group>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" class="w-full sm:w-auto" data-test="update-tenant-settings-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
