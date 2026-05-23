<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\DiscoveredHost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HostEnrollmentService
{
    public function preEnroll(DiscoveredHost $host): Agent
    {
        return DB::transaction(function () use ($host) {
            $existingAgent = null;

            if ($host->agent_id) {
                $existingAgent = Agent::find($host->agent_id);
            }

            if (! $existingAgent) {
                $existingAgent = Agent::query()
                    ->where('discovered_host_id', $host->id)
                    ->orWhere(function ($query) use ($host) {
                        $query->where('ip_address', $host->ip_address);

                        if ($host->hostname) {
                            $query->orWhere('hostname', $host->hostname);
                        }
                    })
                    ->first();
            }

            $token      = Str::random(48);
            $expiresAt  = now()->addHours(48);

            if ($existingAgent) {
                DB::table('agents')
                    ->where('id', $existingAgent->id)
                    ->update([
                        'discovered_host_id'          => $host->id,
                        'agent_name'                  => $existingAgent->agent_name ?: ($host->hostname ?: 'agent-'.$host->ip_address),
                        'hostname'                    => $host->hostname,
                        'ip_address'                  => $host->ip_address,
                        'host_role'                   => $host->host_role ?? 'client',
                        'status'                      => 'pending_enrollment',
                        'enrollment_status'           => 'pending',
                        'enrollment_token'            => $token,
                        'enrollment_token_expires_at' => $expiresAt,
                        'updated_at'                  => now(),
                    ]);

                $agent = Agent::find($existingAgent->id);
            } else {
                $agent = Agent::create([
                    'agent_uuid'                  => (string) Str::uuid(),
                    'discovered_host_id'          => $host->id,
                    'agent_name'                  => $host->hostname ?: 'agent-'.$host->ip_address,
                    'hostname'                    => $host->hostname,
                    'ip_address'                  => $host->ip_address,
                    'host_role'                   => $host->host_role ?? 'client',
                    'status'                      => 'pending_enrollment',
                    'enrollment_status'           => 'pending',
                    'enrollment_token'            => $token,
                    'enrollment_token_expires_at' => $expiresAt,
                    'risk_level'                  => 'normal',
                    'risk_score'                  => 0,
                    'is_isolated'                 => false,
                    'metadata'                    => [
                        'source'   => 'pre_enrollment_from_discovered_host',
                        'host_ip'  => $host->ip_address,
                        'host_mac' => $host->mac_address,
                    ],
                ]);
            }

            DB::table('discovered_hosts')
                ->where('id', $host->id)
                ->update([
                    'agent_id' => $agent->id,
                    'enrollment_status' => 'pending',
                    'is_monitored' => true,
                    'retired_at' => null,
                    'retired_reason' => null,
                    'updated_at' => now(),
                ]);

            return $agent->refresh();
        });
    }

    public function linkRealEnrollment(array $payload, Agent $agent): Agent
    {
        return DB::transaction(function () use ($payload, $agent) {
            $host = DiscoveredHost::query()
                ->where('ip_address', $payload['ip_address'] ?? null)
                ->when(! empty($payload['hostname']), function ($query) use ($payload) {
                    $query->orWhere('hostname', $payload['hostname']);
                })
                ->first();

            DB::table('agents')
                ->where('id', $agent->id)
                ->update([
                    'discovered_host_id'          => $host?->id,
                    'status'                      => 'active',
                    'enrollment_status'           => 'enrolled',
                    'enrollment_token'            => null,       // usage unique — détruit après enrollment
                    'enrollment_token_expires_at' => null,
                    'enrolled_at'                 => now(),
                    'last_seen_at'                => now(),
                    'updated_at'                  => now(),
                ]);

            if ($host) {
                DB::table('discovered_hosts')
                    ->where('id', $host->id)
                    ->update([
                        'agent_id' => $agent->id,
                        'enrollment_status' => 'enrolled',
                        'enrolled_at' => now(),
                        'is_monitored' => true,
                        'discovery_status' => 'approved',
                        'retired_at' => null,
                        'retired_reason' => null,
                        'updated_at' => now(),
                    ]);
            }

            return Agent::find($agent->id);
        });
    }
}
