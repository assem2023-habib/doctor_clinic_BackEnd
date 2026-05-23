<?php

namespace Tests\Feature\Receptionists;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ReceptionistActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_admin_can_activate_receptionist_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Passport::actingAs($admin);

        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('receptionist');
        $user->receptionist()->create([
            'shift_start' => '09:00',
            'shift_end' => '17:00',
        ]);

        $response = $this->putJson("/api/v1/receptionists/{$user->receptionist->id}/activate-account");

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data' => ['id', 'first_name', 'last_name', 'email', 'is_active']]);

        $json = $response->json();
        $this->assertEquals(__('auth.account_activated'), $json['message']);
        $this->assertTrue($json['data']['is_active']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_non_admin_cannot_activate_receptionist_account(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole('receptionist');
        $user->receptionist()->create([
            'shift_start' => '09:00',
            'shift_end' => '17:00',
        ]);
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/receptionists/{$user->receptionist->id}/activate-account");

        $response->assertStatus(403);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }
}
