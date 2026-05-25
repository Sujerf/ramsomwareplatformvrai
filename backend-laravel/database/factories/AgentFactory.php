<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'agent_uuid'        => (string) Str::uuid(),
            'agent_name'        => $this->faker->lexify('agent-????????'),
            'hostname'          => $this->faker->domainWord() . '-pc',
            'ip_address'        => $this->faker->ipv4(),
            'mac_address'       => $this->faker->macAddress(),
            'host_role'         => 'client',
            'status'            => 'active',
            'enrollment_status' => 'enrolled',
            'risk_level'        => 'normal',
            'risk_score'        => 0,
            'is_isolated'       => false,
        ];
    }
}
