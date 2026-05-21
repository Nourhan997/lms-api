<?php

declare(strict_types=1);

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => ['database', 'redis', 'queue'],
                'version',
            ]);
    }

    public function test_health_shows_database_status(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonPath('services.database', 'ok');
    }

    public function test_health_shows_redis_status(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJsonStructure(['services' => ['redis']]);
    }
}
