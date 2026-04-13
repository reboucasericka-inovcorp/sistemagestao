<?php

namespace Tests\Feature\Api\Settings\Logs;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class LogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_activity_logs_with_meta(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $this->actingAs($user, 'sanctum');

        $this->createLog([
            'log_name' => 'articles',
            'description' => 'created',
            'causer_id' => $user->id,
            'causer_type' => User::class,
            'properties' => ['ip' => '127.0.0.1', 'device' => 'Chrome'],
        ]);

        $response = $this->getJson('/api/v1/activity-logs');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('data.0.user_name', 'John Doe')
            ->assertJsonPath('data.0.menu', 'Articles')
            ->assertJsonPath('data.0.action', 'Criado')
            ->assertJsonPath('data.0.device', 'Chrome')
            ->assertJsonPath('data.0.ip_address', '127.0.0.1');
    }

    public function test_it_filters_activity_logs_by_user(): void
    {
        $john = User::factory()->create(['name' => 'John Doe']);
        $jane = User::factory()->create(['name' => 'Jane Smith']);
        $this->actingAs($john, 'sanctum');

        $this->createLog([
            'log_name' => 'articles',
            'description' => 'updated',
            'causer_id' => $john->id,
            'causer_type' => User::class,
        ]);
        $this->createLog([
            'log_name' => 'articles',
            'description' => 'updated',
            'causer_id' => $jane->id,
            'causer_type' => User::class,
        ]);

        $response = $this->getJson('/api/v1/activity-logs?user=John');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user_name', 'John Doe');
    }

    public function test_it_filters_activity_logs_by_menu(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');

        $this->createLog([
            'log_name' => 'articles',
            'description' => 'created',
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);
        $this->createLog([
            'log_name' => 'companies',
            'description' => 'updated',
            'causer_id' => $user->id,
            'causer_type' => User::class,
        ]);

        $response = $this->getJson('/api/v1/activity-logs?menu=articles');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.menu', 'Articles');
    }

    private function createLog(array $payload): void
    {
        Activity::query()->create(array_merge([
            'log_name' => 'default',
            'description' => 'updated',
            'causer_id' => null,
            'causer_type' => null,
            'subject_id' => null,
            'subject_type' => null,
            'event' => null,
            'properties' => [],
        ], $payload));
    }
}
