<?php

namespace Tests\Feature\Locations;

use App\Domains\Locations\Models\City;
use App\Domains\Locations\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CityTest extends TestCase
{
    use RefreshDatabase;

    private Country $country;
    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->country = Country::create([
            'name' => ['ar' => 'سوريا', 'en' => 'Syria'],
            'code' => 'SY',
        ]);

        $this->validPayload = [
            'name_ar' => 'دمشق',
            'name_en' => 'Damascus',
            'country_id' => $this->country->id,
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

    public function test_can_list_cities(): void
    {
        City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);
        City::create(['name' => ['ar' => 'حلب', 'en' => 'Aleppo'], 'country_id' => $this->country->id]);

        $response = $this->getJson('/api/v1/cities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => [['id', 'name', 'country_id', 'created_at', 'updated_at']],
                'meta' => ['pagination'],
            ]);

        $json = $response->json();
        $this->assertCount(2, $json['data']);
    }

    public function test_can_filter_cities_by_country(): void
    {
        $country2 = Country::create(['name' => ['ar' => 'مصر', 'en' => 'Egypt'], 'code' => 'EG']);

        City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);
        City::create(['name' => ['ar' => 'القاهرة', 'en' => 'Cairo'], 'country_id' => $country2->id]);

        $response = $this->getJson('/api/v1/cities?country_id=' . $this->country->id);

        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals('دمشق', $json['data'][0]['name']['ar']);
    }

    public function test_can_show_city(): void
    {
        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);

        $response = $this->getJson("/api/v1/cities/{$city->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'name', 'country_id', 'created_at', 'updated_at'],
            ]);

        $json = $response->json();
        $this->assertEquals('Damascus', $json['data']['name']['en']);
    }

    public function test_show_returns_404_for_nonexistent_city(): void
    {
        $response = $this->getJson('/api/v1/cities/' . fake()->uuid());

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_create_city(): void
    {
        $response = $this->postJson('/api/v1/cities', $this->validPayload);

        $response->assertStatus(401);
    }

    public function test_can_create_city(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/cities', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'name', 'country_id', 'created_at', 'updated_at'],
            ]);

        $json = $response->json();
        $this->assertEquals($this->country->id, $json['data']['country_id']);

        $this->assertDatabaseHas('cities', ['country_id' => $this->country->id]);
    }

    public function test_create_city_fails_with_invalid_country(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/cities', [
            'name_ar' => 'دمشق',
            'name_en' => 'Damascus',
            'country_id' => fake()->uuid(),
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_update_city(): void
    {
        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);

        $response = $this->putJson("/api/v1/cities/{$city->id}", $this->validPayload);

        $response->assertStatus(401);
    }

    public function test_can_update_city(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);

        $country2 = Country::create(['name' => ['ar' => 'مصر', 'en' => 'Egypt'], 'code' => 'EG']);

        $response = $this->putJson("/api/v1/cities/{$city->id}", [
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
            'country_id' => $country2->id,
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals('Cairo', $json['data']['name']['en']);
        $this->assertEquals($country2->id, $json['data']['country_id']);
    }

    public function test_unauthenticated_user_cannot_delete_city(): void
    {
        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);

        $response = $this->deleteJson("/api/v1/cities/{$city->id}");

        $response->assertStatus(401);
    }

    public function test_can_delete_city(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);

        $response = $this->deleteJson("/api/v1/cities/{$city->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }
}
