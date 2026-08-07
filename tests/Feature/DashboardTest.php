<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Tenancy\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\TenantScenario;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_renders_phase_three_app_shell_navigation_and_components(): void
    {
        $tenant = Tenant::factory()
            ->branded()
            ->create([
                'display_name' => 'Chicago REALTORS',
                'enabled_integrations' => ['mls-feed', 'forms-library'],
            ]);

        $user = User::factory()
            ->asTenantOwner($tenant)
            ->create();

        $this->actingAs($user);

        $response = $this->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Chicago REALTORS')
            ->assertSee('Dashboard')
            ->assertSee('Transactions')
            ->assertSee('Documents')
            ->assertSee('Forms')
            ->assertSee('Contacts')
            ->assertSee('Teams')
            ->assertSee('Reports')
            ->assertSee('Partner tabs')
            ->assertSee('Mls Feed')
            ->assertSee('Forms Library')
            ->assertSeeLivewire('dashboard.overview')
            ->assertSeeLivewire('app.global-search')
            ->assertDontSee('data-test="pinned-transaction-rail"', false)
            ->assertDontSee('Pinned transactions');
    }

    public function test_primary_navigation_targets_render_authenticated_placeholders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $routeNames = [
            'documents.index',
            'forms.index',
            'contacts.index',
            'teams.index',
            'reports.index',
        ];

        foreach ($routeNames as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('Navigation target ready');
        }
    }

    public function test_dashboard_is_action_queue_first_and_tenant_scoped(): void
    {
        $scenario = TenantScenario::create();

        $scenario->tenant->update(['enabled_integrations' => ['mls-feed']]);
        $scenario->transaction->update([
            'name' => 'Lakeview sale',
            'property_address' => '123 Lakeview Ave',
        ]);
        $scenario->milestone->update([
            'title' => 'Inspection objection deadline',
            'due_at' => now()->addDay(),
        ]);
        $scenario->document->update([
            'title' => 'Lakeview purchase agreement',
            'status' => Document::STATUS_IN_REVIEW,
        ]);
        $scenario->outsideTransaction->update(['name' => 'Outside tenant deal']);

        $this->actingAs($scenario->owner);

        $response = $this->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Action queue')
            ->assertSee('Inspection objection deadline')
            ->assertSee('Lakeview purchase agreement')
            ->assertSee('Recent transactions')
            ->assertSee('Lakeview sale')
            ->assertSee('Mls Feed')
            ->assertDontSee('Outside tenant deal');
    }

    public function test_dashboard_role_copy_changes_by_membership_role(): void
    {
        $tenant = Tenant::factory()->create();
        $coordinator = User::factory()
            ->withTenant($tenant, TenantMembership::ROLE_COORDINATOR)
            ->create();

        $this->actingAs($coordinator);

        $response = $this->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertSee('Coordinator dashboard')
            ->assertSee('Prioritize deadlines');
    }

    public function test_global_search_returns_only_current_tenant_records(): void
    {
        $scenario = TenantScenario::create();

        $scenario->transaction->update([
            'name' => 'Lakeview sale',
            'property_address' => '123 Lakeview Ave',
        ]);
        $scenario->contact->update(['display_name' => 'Lakeview Buyer']);
        $scenario->document->update(['title' => 'Lakeview disclosure package']);
        $scenario->form->update(['title' => 'Lakeview rider form']);
        $scenario->outsideTransaction->update(['name' => 'Lakeview outside transaction']);

        app(CurrentTenant::class)->set($scenario->tenant);

        Livewire::actingAs($scenario->owner)
            ->test('app.global-search')
            ->set('query', 'Lakeview')
            ->assertSee('Lakeview sale')
            ->assertSee('Lakeview Buyer')
            ->assertSee('Lakeview disclosure package')
            ->assertSee('Lakeview rider form')
            ->assertDontSee('Lakeview outside transaction');
    }

    public function test_app_shell_does_not_render_pinned_transaction_rail(): void
    {
        $scenario = TenantScenario::create();

        $this->actingAs($scenario->owner);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-test="pinned-transaction-rail"', false)
            ->assertDontSee('Pinned transactions');
    }
}
