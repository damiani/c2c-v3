<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    @php
        $currentTenant = app(\App\Tenancy\CurrentTenant::class)->get();
        $currentUser = auth()->user();
        $membership = $currentTenant && $currentUser ? $currentUser->membershipForTenant($currentTenant) : null;
        $roleLabel = str($membership?->role ?? 'member')->replace('_', ' ')->title();
        $tenantName = $currentTenant?->brandedName() ?? config('app.name', 'Contract2Close');
        $primaryColor = $currentTenant?->primary_color ?? '#2563eb';
        $accentColor = $currentTenant?->accent_color ?? '#16a34a';
        $enabledIntegrations = collect($currentTenant?->enabled_integrations ?? [])
            ->mapWithKeys(fn (string $integration) => [
                $integration => str($integration)->replace('-', ' ')->title()->toString(),
            ]);
        $navItems = [
            ['label' => __('Dashboard'), 'route' => 'dashboard', 'matches' => 'dashboard', 'icon' => 'home'],
            ['label' => __('Transactions'), 'route' => 'transactions.index', 'matches' => 'transactions.*', 'icon' => 'building-office-2', 'badge' => 'Live'],
            ['label' => __('Documents'), 'route' => 'documents.index', 'matches' => 'documents.*', 'icon' => 'document-text'],
            ['label' => __('Forms'), 'route' => 'forms.index', 'matches' => 'forms.*', 'icon' => 'clipboard-document-list'],
            ['label' => __('Contacts'), 'route' => 'contacts.index', 'matches' => 'contacts.*', 'icon' => 'user-group'],
            ['label' => __('Teams'), 'route' => 'teams.index', 'matches' => 'teams.*', 'icon' => 'users'],
            ['label' => __('Reports'), 'route' => 'reports.index', 'matches' => 'reports.*', 'icon' => 'chart-bar-square'],
        ];
    @endphp

    <body
        class="min-h-screen bg-[#fbfaf7] text-zinc-950 antialiased dark:bg-zinc-950 dark:text-white"
        style="--color-accent: {{ $primaryColor }}; --color-accent-content: {{ $primaryColor }}; --color-accent-foreground: #ffffff; --c2c-primary: {{ $primaryColor }}; --c2c-accent: {{ $accentColor }};"
    >
        <flux:sidebar sticky collapsible class="border-e border-zinc-200 bg-white/95 shadow-[1px_0_0_rgba(24,24,27,0.03)] backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 px-2 py-1" wire:navigate aria-label="{{ __('Dashboard') }}">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-[var(--c2c-primary)] text-sm font-semibold text-white shadow-sm">
                        C2C
                    </span>
                    <span class="grid min-w-0 in-data-flux-sidebar-collapsed:hidden">
                        <span class="truncate text-sm font-semibold text-zinc-950 dark:text-white">{{ $tenantName }}</span>
                        <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $roleLabel }}</span>
                    </span>
                </a>

                <flux:sidebar.collapse class="max-lg:hidden in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Workspace')" class="grid">
                    @foreach ($navItems as $item)
                        <flux:sidebar.item
                            :icon="$item['icon']"
                            :href="route($item['route'])"
                            :current="request()->routeIs($item['matches'])"
                            :badge="$item['badge'] ?? null"
                            wire:navigate
                        >
                            {{ $item['label'] }}
                        </flux:sidebar.item>
                    @endforeach
                </flux:sidebar.group>

                @if ($enabledIntegrations->isNotEmpty())
                    <flux:sidebar.group expandable icon="puzzle-piece" :heading="__('Partner tabs')" class="grid">
                        @foreach ($enabledIntegrations as $integration => $label)
                            <flux:sidebar.item href="{{ route('dashboard') }}#partner-{{ $integration }}" icon="arrow-top-right-on-square" wire:navigate>
                                {{ $label }}
                            </flux:sidebar.item>
                        @endforeach
                    </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:sidebar.spacer />
            <flux:sidebar.nav>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.edit', 'security.edit', 'appearance.edit', 'tenant.edit')" wire:navigate>
                    {{ __('Settings') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="gap-3 border-b border-zinc-200 bg-white/90 backdrop-blur lg:hidden dark:border-zinc-800 dark:bg-zinc-950/90">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <div class="min-w-0 flex-1">
                <livewire:app.global-search modal-name="mobile-global-search" compact />
            </div>

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <header class="hidden border-b border-zinc-200 bg-white/90 backdrop-blur lg:block dark:border-zinc-800 dark:bg-zinc-950/90">
            <div class="flex h-16 items-center gap-4 px-6">
                <div class="min-w-0">
                    <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Current workspace') }}</div>
                    <div class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $tenantName }}</div>
                </div>

                <div class="mx-auto w-full max-w-2xl">
                    <livewire:app.global-search />
                </div>

                <flux:tooltip :content="__('Notifications')">
                    <flux:button icon="bell" variant="ghost" class="relative" aria-label="{{ __('Notifications') }}">
                        <span class="absolute right-2 top-2 size-2 rounded-full bg-[var(--c2c-primary)]"></span>
                    </flux:button>
                </flux:tooltip>

                <flux:button variant="primary" icon="plus" :href="route('transactions.index')" wire:navigate>
                    {{ __('New transaction') }}
                </flux:button>
            </div>
        </header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
