<?php

namespace App\Providers;

use App\Actions\Audit\WriteAuditEvent;
use App\Contracts\Audit\AuditWriter;
use App\Tenancy\CurrentTenant;
use Carbon\CarbonImmutable;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
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
