<?php

use App\Localization\LocaleFormatter;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware('web')->get('/localization-probe', function (CurrentTenant $currentTenant, LocaleFormatter $formatter) {
        return response()->json([
            'request_locale' => App::currentLocale(),
            'tenant_default_locale' => $currentTenant->get()?->default_locale,
            'date' => $formatter->formatDate('2026-08-06'),
            'area_unit' => $formatter->areaUnit(),
        ]);
    });
});

test('user locale preference wins over tenant default locale for requests', function () {
    $tenant = Tenant::factory()->create([
        'default_locale' => 'en',
        'supported_locales' => ['en', 'es'],
    ]);
    $user = User::factory()->spanishLocale()->withTenant($tenant)->create();

    $this->actingAs($user)
        ->withSession([config('tenancy.session_key') => $tenant->id])
        ->get('/localization-probe')
        ->assertOk()
        ->assertJson([
            'request_locale' => 'es',
            'tenant_default_locale' => 'en',
            'date' => '06/08/2026',
            'area_unit' => 'hectares',
        ]);
});

test('unsupported user locale falls back without using tenant default locale', function () {
    $tenant = Tenant::factory()->create([
        'default_locale' => 'es',
        'supported_locales' => ['en', 'es'],
    ]);
    $user = User::factory()->withTenant($tenant)->create(['locale' => 'fr']);

    $this->actingAs($user)
        ->withSession([config('tenancy.session_key') => $tenant->id])
        ->get('/localization-probe')
        ->assertOk()
        ->assertJson([
            'request_locale' => 'en',
            'tenant_default_locale' => 'es',
            'date' => '08/06/2026',
            'area_unit' => 'acres',
        ]);
});

test('locale formatter renders date currency and area defaults', function () {
    $formatter = app(LocaleFormatter::class);
    $date = Carbon::parse('2026-08-06 14:30:00');

    expect($formatter->formatDate($date, 'en'))->toBe('08/06/2026')
        ->and($formatter->formatDate($date, 'es'))->toBe('06/08/2026')
        ->and($formatter->formatDateTime($date, 'en'))->toBe('08/06/2026 2:30 PM')
        ->and($formatter->formatDateTime($date, 'es'))->toBe('06/08/2026 14:30')
        ->and($formatter->formatCurrency(1234.56, 'en'))->toContain('1,234.56')
        ->and($formatter->formatCurrency(1234.56, 'es'))->toContain('1.234,56')
        ->and($formatter->formatArea(12.5, 'en'))->toBe('12.50 Acres')
        ->and($formatter->formatArea(12.5, 'es'))->toBe('12,50 Hectareas');
});

test('spanish translations cover shared auth and settings strings', function () {
    App::setLocale('es');

    expect(__('Log in'))->toBe('Iniciar sesion')
        ->and(__('Settings'))->toBe('Configuracion')
        ->and(__('Tenant settings updated.'))->toBe('Configuracion de tenant actualizada.')
        ->and(__('passwords.sent'))->toBe('Te hemos enviado por correo el enlace para restablecer tu contrasena.')
        ->and(__('auth.failed'))->toBe('Estas credenciales no coinciden con nuestros registros.');
});

test('validation feedback uses localized messages and attributes', function () {
    App::setLocale('es');

    $validator = Validator::make(
        ['email' => 'not-an-email', 'locale' => 'fr'],
        ['email' => ['required', 'email'], 'locale' => ['required', 'in:en,es']],
    );

    expect($validator->errors()->first('email'))->toBe('El campo correo electronico debe ser un correo electronico valido.')
        ->and($validator->errors()->first('locale'))->toBe('El campo idioma seleccionado no es valido.');
});

test('profile locale updates do not mutate tenant default locale', function () {
    $tenant = Tenant::factory()->create([
        'default_locale' => 'en',
        'supported_locales' => ['en', 'es'],
    ]);
    $user = User::factory()->withTenant($tenant)->create(['locale' => 'en']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('name', 'Sofia Broker')
        ->set('email', $user->email)
        ->set('locale', 'es')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($user->refresh()->locale)->toBe('es')
        ->and($tenant->refresh()->default_locale)->toBe('en');
});
