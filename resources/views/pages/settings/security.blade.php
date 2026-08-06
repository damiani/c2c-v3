<?php

use App\Concerns\PasswordValidationRules;
use App\Models\IdentityProviderAccount;
use App\Tenancy\CurrentTenant;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Attributes\On;

new #[Title('Security settings')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $canManageTwoFactor;

    public bool $twoFactorEnabled;

    public bool $requiresConfirmation;

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $this->canManageTwoFactor = Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            if (Fortify::confirmsTwoFactorAuthentication() && is_null(auth()->user()->two_factor_confirmed_at)) {
                $disableTwoFactorAuthentication(auth()->user());
            }

            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
            $this->requiresConfirmation = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
        }
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }

    /**
     * Handle the two-factor authentication enabled event.
     */
    #[On('two-factor-enabled')]
    public function onTwoFactorEnabled(): void
    {
        $this->twoFactorEnabled = true;
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disable(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
        $disableTwoFactorAuthentication(auth()->user());

        $this->twoFactorEnabled = false;
    }

    public function unlinkIdentityProviderAccount(int $accountId): void
    {
        $tenant = app(CurrentTenant::class)->get();

        abort_if($tenant === null, 404);

        $account = IdentityProviderAccount::query()
            ->whereBelongsTo($tenant)
            ->whereBelongsTo(Auth::user(), 'user')
            ->findOrFail($accountId);

        $account->delete();

        unset($this->identityProviderAccounts);

        Flux::toast(variant: 'success', text: __('Connected account removed.'));
    }

    /**
     * @return Collection<int, IdentityProviderAccount>
     */
    #[Computed]
    public function identityProviderAccounts(): Collection
    {
        $tenant = app(CurrentTenant::class)->get();

        if ($tenant === null) {
            return new Collection();
        }

        return Auth::user()
            ->identityProviderAccounts()
            ->whereBelongsTo($tenant)
            ->orderBy('provider')
            ->get();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Security settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form method="POST" wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Current password')"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />
            <flux:input
                wire:model="password"
                :label="__('New password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit" data-test="update-password-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>

        @if ($canManageTwoFactor)
            <section class="mt-12">
                <flux:heading>{{ __('Two-factor authentication') }}</flux:heading>
                <flux:subheading>{{ __('Manage your two-factor authentication settings') }}</flux:subheading>

                <div class="flex flex-col w-full mx-auto space-y-6 text-sm" wire:cloak>
                    @if ($twoFactorEnabled)
                        <div class="space-y-4">
                            <flux:text>
                                {{ __('You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.') }}
                            </flux:text>

                            <div class="flex justify-start">
                                <flux:button
                                    variant="danger"
                                    wire:click="disable"
                                >
                                    {{ __('Disable 2FA') }}
                                </flux:button>
                            </div>

                            <livewire:pages::settings.two-factor.recovery-codes :$requiresConfirmation />
                        </div>
                    @else
                        <div class="space-y-4">
                            <flux:text variant="subtle">
                                {{ __('When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.') }}
                            </flux:text>

                            <flux:modal.trigger name="two-factor-setup-modal">
                                <flux:button
                                    variant="primary"
                                    wire:click="$dispatch('start-two-factor-setup')"
                                >
                                    {{ __('Enable 2FA') }}
                                </flux:button>
                            </flux:modal.trigger>

                            <livewire:pages::settings.two-factor-setup-modal :requires-confirmation="$requiresConfirmation" />
                        </div>
                    @endif
                </div>
            </section>
        @endif

        <section class="mt-12 space-y-4">
            <div>
                <flux:heading>{{ __('Connected accounts') }}</flux:heading>
                <flux:subheading>{{ __('Manage external sign-in providers for this tenant') }}</flux:subheading>
            </div>

            <div class="space-y-3">
                @forelse ($this->identityProviderAccounts as $account)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-white/10" wire:key="identity-provider-account-{{ $account->id }}">
                        <div class="min-w-0">
                            <flux:text class="font-medium">{{ __(config("sso.providers.{$account->provider}.label", $account->provider)) }}</flux:text>
                            <flux:text variant="subtle" class="truncate">{{ $account->email ?? $account->provider_user_id }}</flux:text>
                        </div>

                        <flux:button variant="danger" size="sm" wire:click="unlinkIdentityProviderAccount({{ $account->id }})">
                            {{ __('Disconnect') }}
                        </flux:button>
                    </div>
                @empty
                    <flux:text variant="subtle">{{ __('No external accounts are connected for this tenant.') }}</flux:text>
                @endforelse
            </div>

            <flux:button variant="outline" :href="route('sso.redirect', ['provider' => 'google'])">
                {{ __('Connect Google') }}
            </flux:button>
        </section>
    </x-pages::settings.layout>
</section>
