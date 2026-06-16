<?php

namespace Tests\Feature\RBAC;

use App\Domains\RBAC\Models\Permission;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PermissionCacheTest extends TestCase
{
    use RefreshDatabase;

    private Permission $permission;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->permission = Permission::where('slug', 'appointments.view')->first();

        $this->setupPassportKeys();
        $this->createPasswordGrantClient();
    }

    private function setupPassportKeys(): void
    {
        $privatePath = Passport::keyPath('oauth-private.key');
        $publicPath = Passport::keyPath('oauth-public.key');

        if (file_exists($privatePath) && file_exists($publicPath)) {
            return;
        }

        $this->artisan('passport:keys', ['--force' => true]);
    }

    private function createPasswordGrantClient(): void
    {
        $client = \Laravel\Passport\Client::create([
            'name' => 'Test Password Grant Client',
            'secret' => \Illuminate\Support\Str::random(40),
            'provider' => 'users',
            'redirect_uris' => ['http://localhost'],
            'grant_types' => ['password', 'refresh_token'],
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);

        config(['passport.password_client_id' => $client->id]);
        config(['passport.password_client_secret' => $client->plainSecret]);
    }

    public function test_cache_hit_returns_stale_data_until_update(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);

        $permission = $this->permission;
        Cache::forget('permissions:cache_version');

        $response1 = $this->getJson('/api/v1/permissions');
        $originalName = collect($response1->json('data'))->firstWhere('id', $permission->id)['name'];

        $permission->name = 'Stale Permission Name';
        $permission->saveQuietly();

        $response2 = $this->getJson('/api/v1/permissions');
        $cachedName = collect($response2->json('data'))->firstWhere('id', $permission->id)['name'];
        $this->assertEquals($originalName, $cachedName);
    }

    public function test_cache_invalidated_after_update(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);
        Cache::forget('permissions:cache_version');

        $this->getJson('/api/v1/permissions');

        $this->putJson("/api/v1/permissions/{$this->permission->id}", [
            'name' => 'Updated Permission',
            'slug' => $this->permission->slug,
        ])->assertStatus(200);

        $response = $this->getJson('/api/v1/permissions');
        $updated = collect($response->json('data'))->firstWhere('id', $this->permission->id);
        $this->assertEquals('Updated Permission', $updated['name']);
    }

    public function test_cache_invalidated_after_create(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('super-admin');
        Passport::actingAs($user);
        Cache::forget('permissions:cache_version');

        $response1 = $this->getJson('/api/v1/permissions');
        $count1 = count($response1->json('data'));

        $this->postJson('/api/v1/permissions', [
            'name' => 'Test New Permission',
            'slug' => 'test.new.permission',
            'group' => 'Test',
        ])->assertStatus(201);

        $response2 = $this->getJson('/api/v1/permissions');
        $this->assertCount($count1 + 1, $response2->json('data'));
    }

    public function test_cache_invalidated_after_delete(): void
    {
        $permission = Permission::create([
            'name' => 'Test Delete Permission',
            'slug' => 'test.delete.permission',
            'group' => 'Test',
        ]);

        $user = \App\Models\User::factory()->create();
        $user->assignRole('super-admin');
        Passport::actingAs($user);
        Cache::forget('permissions:cache_version');

        $response1 = $this->getJson('/api/v1/permissions');
        $initialCount = count($response1->json('data'));

        $this->deleteJson("/api/v1/permissions/{$permission->id}")->assertStatus(204);

        $response2 = $this->getJson('/api/v1/permissions');
        $this->assertCount($initialCount - 1, $response2->json('data'));
    }
}
