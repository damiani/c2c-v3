<?php

namespace Database\Factories;

use App\Models\Lease;
use App\Models\LeaseNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaseNotification>
 */
class LeaseNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $lease = Lease::factory()->create();

        return [
            'tenant_id' => $lease->tenant_id,
            'lease_id' => $lease->id,
            'lead_time_months' => fake()->randomElement([2, 3, 4, 5]),
            'alert_type' => 'renewal_window',
            'alert_at' => now()->addMonths(6),
            'fired_at' => null,
            'status' => LeaseNotification::STATUS_SCHEDULED,
            'agent_action' => null,
            'escalation_status' => LeaseNotification::ESCALATION_NORMAL,
            'metadata' => [],
        ];
    }

    /**
     * Attach the notification to an existing lease.
     */
    public function forLease(Lease $lease): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $lease->tenant_id,
            'lease_id' => $lease->getKey(),
        ]);
    }
}
