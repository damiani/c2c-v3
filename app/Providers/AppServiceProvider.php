<?php

namespace App\Providers;

use App\Actions\Audit\WriteAuditEvent;
use App\Authorization\TenantPermission;
use App\Contracts\Audit\AuditWriter;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuditWriter::class, WriteAuditEvent::class);
        $this->app->scoped(CurrentTenant::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Context::dehydrating(function (Repository $context): void {
            $context->addHidden('locale', Config::get('app.locale'));
        });

        Context::hydrated(function (Repository $context): void {
            if ($context->hasHidden('locale')) {
                Config::set('app.locale', $context->getHidden('locale'));
            }
        });

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Gate::define('tenant-permission', function (User $user, Tenant $tenant, string $permission): bool {
            return in_array($permission, TenantPermission::all(), true)
                && $user->hasTenantPermission($tenant, $permission);
        });

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
