<?php

namespace Tests\Feature\Receptionists;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ReceptionistCacheTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionistUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupPassportKeys();
        $this->createPasswordGrantClient();

        $this->receptionistUser = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Receptionist',
            'email' => 'receptionist@cachetest.com',
        ]);
        $this->receptionistUser->assignRole('receptionist');
        $this->receptionistUser->receptionist()->create([
            'shift_start' => '09:00',
            'shift_end' => '17:00',
        ]);
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
        Cache::forget('receptionists:cache_version');

        $response1 = $this->getJson('/api/v1/receptionists');
        $originalName = $response1->json('data')[0]['first_name'];

        $this->receptionistUser->first_name = 'StaleReceptionist';
        $this->receptionistUser->saveQuietly();

        $response2 = $this->getJson('/api/v1/receptionists');
        $this->assertEquals($originalName, $response2->json('data')[0]['first_name']);
    }

    public function test_cache_invalidated_after_update(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Passport::actingAs($admin);
        Cache::forget('receptionists:cache_version');

        $this->getJson('/api/v1/receptionists');

        $this->putJson("/api/v1/receptionists/{$this->receptionistUser->id}", [
            'first_name' => 'UpdatedName',
            'last_name' => 'Receptionist',
            'email' => 'receptionist@cachetest.com',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
        ])->assertStatus(200);

        $response = $this->getJson('/api/v1/receptionists');
        $this->assertEquals('UpdatedName', $response->json('data')[0]['first_name']);
    }

    public function test_cache_invalidated_after_create(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Passport::actingAs($admin);
        Cache::forget('receptionists:cache_version');

        $response1 = $this->getJson('/api/v1/receptionists');
        $count1 = count($response1->json('data'));

        $this->postJson('/api/v1/receptionists', [
            'first_name' => 'New',
            'last_name' => 'Receptionist',
            'username' => 'newreceptionist',
            'email' => 'new@receptionist.com',
            'phone' => '+963911111111',
            'gender' => 'female',
            'birthday_date' => '1995-06-15',
            'password' => 'Password@123',
            'shift_start' => '08:00',
            'shift_end' => '16:00',
        ])->assertStatus(201);

        $response2 = $this->getJson('/api/v1/receptionists');
        $this->assertCount($count1 + 1, $response2->json('data'));
    }

    public function test_cache_invalidated_after_delete(): void
    {
        $targetUser = User::factory()->create([
            'first_name' => 'Delete',
            'last_name' => 'Me',
            'email' => 'delete@receptionist.com',
        ]);
        $targetUser->assignRole('receptionist');
        $targetUser->receptionist()->create([
            'shift_start' => '09:00',
            'shift_end' => '17:00',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Passport::actingAs($admin);
        Cache::forget('receptionists:cache_version');

        $response1 = $this->getJson('/api/v1/receptionists');
        $initialCount = count($response1->json('data'));

        $this->deleteJson("/api/v1/receptionists/{$targetUser->id}")->assertStatus(204);

        $response2 = $this->getJson('/api/v1/receptionists');
        $this->assertCount($initialCount - 1, $response2->json('data'));
    }
}
