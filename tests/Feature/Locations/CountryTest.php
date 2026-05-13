<?php

namespace Tests\Feature\Locations;

use App\Domains\Locations\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CountryTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validPayload = [
            'name_ar' => 'سوريا',
            'name_en' => 'Syria',
            'code' => 'SY',
            'flag' => null,
        ];

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

    public function test_can_list_countries(): void
    {
        Country::create([
            'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
            'code' => 'SY',
        ]);
        Country::create([
            'name' => ['ar' => 'مصر', 'en' => 'Egypt'],
            'code' => 'EG',
        ]);

        $response = $this->getJson('/api/v1/countries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => [['id', 'name', 'code', 'flag', 'cities', 'created_at', 'updated_at']],
                'meta' => ['pagination'],
            ]);

        $json = $response->json();
        $this->assertCount(2, $json['data']);
    }

    public function test_can_show_country(): void
    {
        $country = Country::create([
            'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
            'code' => 'SY',
        ]);

        $response = $this->getJson("/api/v1/countries/{$country->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'name', 'code', 'flag', 'cities', 'created_at', 'updated_at'],
            ]);

        $json = $response->json();
        $this->assertEquals('SY', $json['data']['code']);
    }

    public function test_show_returns_404_for_nonexistent_country(): void
    {
        $response = $this->getJson('/api/v1/countries/' . fake()->uuid());

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_create_country(): void
    {
        $response = $this->postJson('/api/v1/countries', $this->validPayload);

        $response->assertStatus(401);
    }

    public function test_can_create_country(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/countries', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'name', 'code', 'flag', 'created_at', 'updated_at'],
            ]);

        $json = $response->json();
        $this->assertEquals('SY', $json['data']['code']);
        $this->assertEquals(['ar' => 'سوريا', 'en' => 'Syria'], $json['data']['name']);

        $this->assertDatabaseHas('countries', ['code' => 'SY']);
    }

    public function test_create_fails_with_duplicate_code(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        Country::create([
            'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
            'code' => 'SY',
        ]);

        $response = $this->postJson('/api/v1/countries', $this->validPayload);

        $response->assertStatus(422)
            ->assertJsonStructure(['status', 'message', 'errors']);
    }

    public function test_unauthenticated_user_cannot_update_country(): void
    {
        $country = Country::create([
            'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
            'code' => 'SY',
        ]);

        $response = $this->putJson("/api/v1/countries/{$country->id}", $this->validPayload);

        $response->assertStatus(401);
    }

    public function test_can_update_country(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $country = Country::create([
            'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
            'code' => 'SY',
        ]);

        $response = $this->putJson("/api/v1/countries/{$country->id}", [
            'name_ar' => 'سوريا',
            'name_en' => 'Syria',
            'code' => 'SY',
            'flag' => 'https://flagcdn.com/sy.svg',
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals('https://flagcdn.com/sy.svg', $json['data']['flag']);
    }

    public function test_unauthenticated_user_cannot_delete_country(): void
    {
        $country = Country::create([
            'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
            'code' => 'SY',
        ]);

        $response = $this->deleteJson("/api/v1/countries/{$country->id}");

        $response->assertStatus(401);
    }

    public function test_can_delete_country(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $country = Country::create([
            'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
            'code' => 'SY',
        ]);

        $response = $this->deleteJson("/api/v1/countries/{$country->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    }
}
