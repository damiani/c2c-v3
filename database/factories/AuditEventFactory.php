<?php

namespace Database\Factories;

use App\Models\AuditEvent;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'actor_type' => null,
            'actor_id' => null,
            'subject_type' => null,
            'subject_id' => null,
            'action' => fake()->randomElement([
                'tenant.created',
                'tenant.updated',
                'document.uploaded',
            ]),
            'source' => AuditEvent::SOURCE_APPLICATION,
            'metadata' => [],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'occurred_at' => now(),
        ];
    }
}
