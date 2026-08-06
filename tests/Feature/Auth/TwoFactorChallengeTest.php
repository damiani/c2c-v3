<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());
    }

    public function test_two_factor_challenge_redirects_to_login_when_not_authenticated(): void
    {
        $response = $this->get(route('two-factor.login'));

        $response->assertRedirect(route('login'));
    }

    public function test_two_factor_challenge_can_be_rendered(): void
    {
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $user = User::factory()->withTwoFactor()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));
    }

    public function test_two_factor_login_with_recovery_code_preserves_selected_tenant_session_context(): void
    {
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]);

        $tenant = Tenant::factory()->create();
        $user = User::factory()
            ->withTenant($tenant)
            ->withTwoFactor()
            ->create();

        $this->withSession([config('tenancy.session_key') => $tenant->id])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password',
                'remember' => true,
            ])
            ->assertRedirect(route('two-factor.login'))
            ->assertSessionHas('login.id', $user->id)
            ->assertSessionHas('login.remember', true)
            ->assertSessionHas(config('tenancy.session_key'), $tenant->id);

        $recoveryCode = $user->recoveryCodes()[0];

        $this->post(route('two-factor.login.store'), [
            'recovery_code' => $recoveryCode,
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotContains($recoveryCode, $user->fresh()->recoveryCodes());
        $this->assertEquals($tenant->id, session(config('tenancy.session_key')));
        $this->assertFalse(session()->has('login.id'));
    }
}
