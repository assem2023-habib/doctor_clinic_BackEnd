<?php

namespace Tests\Feature\RBAC;

use App\Domains\RBAC\Models\Role;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RoleCacheTest extends TestCase
{
    use RefreshDatabase;

    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RbacSeeder::class);
        $this->role = Role::where('slug', 'doctor')->first();

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

        $role = $this->role;
        Cache::forget('roles:cache_version');

        $response1 = $this->getJson('/api/v1/roles');
        $originalName = collect($response1->json('data'))->firstWhere('id', $role->id)['name'];

        $role->name = 'Stale Role Name';
        $role->saveQuietly();

        $response2 = $this->getJson('/api/v1/roles');
        $cachedName = collect($response2->json('data'))->firstWhere('id', $role->id)['name'];
        $this->assertEquals($originalName, $cachedName);
    }

    public function test_cache_invalidated_after_update(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);
        Cache::forget('roles:cache_version');

        $this->getJson('/api/v1/roles');

        $this->putJson("/api/v1/roles/{$this->role->id}", [
            'name' => 'Updated Role',
            'slug' => $this->role->slug,
        ])->assertStatus(200);

        $response = $this->getJson('/api/v1/roles');
        $updated = collect($response->json('data'))->firstWhere('id', $this->role->id);
        $this->assertEquals('Updated Role', $updated['name']);
    }

    public function test_cache_invalidated_after_create(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('super-admin');
        Passport::actingAs($user);
        Cache::forget('roles:cache_version');

        $response1 = $this->getJson('/api/v1/roles');
        $count1 = count($response1->json('data'));

        $this->postJson('/api/v1/roles', [
            'name' => 'Test Role',
            'slug' => 'test-role',
        ])->assertStatus(201);

        $response2 = $this->getJson('/api/v1/roles');
        $this->assertCount($count1 + 1, $response2->json('data'));
    }

    public function test_cache_invalidated_after_delete(): void
    {
        $role = Role::create([
            'name' => 'Test Delete Role',
            'slug' => 'test-delete-role',
        ]);

        $user = \App\Models\User::factory()->create();
        $user->assignRole('super-admin');
        Passport::actingAs($user);
        Cache::forget('roles:cache_version');

        $response1 = $this->getJson('/api/v1/roles');
        $initialCount = count($response1->json('data'));

        $this->deleteJson("/api/v1/roles/{$role->id}")->assertStatus(204);

        $response2 = $this->getJson('/api/v1/roles');
        $this->assertCount($initialCount - 1, $response2->json('data'));
    }
}
