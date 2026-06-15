<?php

namespace Tests\Feature\Locations;

use App\Domains\Locations\Models\City;
use App\Domains\Locations\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        config(['passport.password_client_secret' => $client->plainSecret]);
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

    public function test_non_admin_user_cannot_create_city(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('doctor');
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/cities', $this->validPayload);

        $response->assertStatus(403);
    }

    public function test_can_create_city(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
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
        $user->assignRole('admin');
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
        $user->assignRole('admin');
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

    public function test_non_admin_user_cannot_delete_city(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('doctor');
        Passport::actingAs($user);

        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);

        $response = $this->deleteJson("/api/v1/cities/{$city->id}");

        $response->assertStatus(403);
    }

    public function test_can_delete_city(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);

        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);

        $response = $this->deleteJson("/api/v1/cities/{$city->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }

    public function test_city_list_cache_hit_returns_stale_data_until_update(): void
    {
        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);
        Cache::forget('cities:cache_version');

        $response1 = $this->getJson('/api/v1/cities?limit=50');
        $originalName = $response1->json('data')[0]['name'];

        $city->name = ['ar' => 'اسم قديم', 'en' => 'StaleCity'];
        $city->saveQuietly();

        $response2 = $this->getJson('/api/v1/cities?limit=50');
        $this->assertEquals($originalName, $response2->json('data')[0]['name']);
    }

    public function test_city_list_cache_invalidated_after_update(): void
    {
        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);
        Cache::forget('cities:cache_version');

        $this->getJson('/api/v1/cities');

        $this->putJson("/api/v1/cities/{$city->id}", [
            'name_ar' => 'حلب',
            'name_en' => 'Aleppo',
            'country_id' => $this->country->id,
        ])->assertStatus(200);

        $response = $this->getJson('/api/v1/cities');
        $this->assertEquals(['ar' => 'حلب', 'en' => 'Aleppo'], $response->json('data')[0]['name']);
    }

    public function test_city_list_cache_invalidated_after_create(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);
        Cache::forget('cities:cache_version');

        $response1 = $this->getJson('/api/v1/cities');
        $count1 = count($response1->json('data'));

        $this->postJson('/api/v1/cities', [
            'name_ar' => 'دمشق',
            'name_en' => 'Damascus',
            'country_id' => $this->country->id,
        ])->assertStatus(201);

        $response2 = $this->getJson('/api/v1/cities');
        $this->assertCount($count1 + 1, $response2->json('data'));
    }

    public function test_city_list_cache_invalidated_after_delete(): void
    {
        $city = City::create(['name' => ['ar' => 'دمشق', 'en' => 'Damascus'], 'country_id' => $this->country->id]);
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);
        Cache::forget('cities:cache_version');

        $response1 = $this->getJson('/api/v1/cities');
        $this->assertEquals(1, count($response1->json('data')));

        $this->deleteJson("/api/v1/cities/{$city->id}")->assertStatus(204);

        $response2 = $this->getJson('/api/v1/cities');
        $this->assertEmpty($response2->json('data'));
    }
}
