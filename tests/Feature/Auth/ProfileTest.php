<?php

namespace Tests\Feature\Auth;

use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProfileTest extends TestCase
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
        config(['passport.password_client_secret' => $client->secret]);
    }

    public function test_authenticated_user_can_update_profile(): void
    {
        $user = User::factory()->create([
            'role' => RoleEnum::Patient,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'phone' => '+963911111111',
        ]);
        Passport::actingAs($user);

        $response = $this->putJson('/api/v1/auth/me', [
            'first_name' => 'John Updated',
            'last_name' => 'Smith',
            'username' => 'johnsmith',
            'email' => 'john.smith@example.com',
            'gender' => GenderEnum::Male->value,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'message', 'data' => ['id', 'first_name', 'last_name', 'email']]);

        $json = $response->json();
        $this->assertEquals('John Updated', $json['data']['first_name']);
        $this->assertEquals('john.smith@example.com', $json['data']['email']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'John Updated',
            'email' => 'john.smith@example.com',
        ]);
    }

    public function test_unauthenticated_user_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/v1/auth/me', [
            'first_name' => 'Hacked',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_partially_update_profile(): void
    {
        $user = User::factory()->create([
            'role' => RoleEnum::Patient,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'phone' => '+963911111111',
        ]);
        Passport::actingAs($user);

        $response = $this->putJson('/api/v1/auth/me', [
            'phone' => '+963922222222',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '+963922222222',
        ]);
    }
}
