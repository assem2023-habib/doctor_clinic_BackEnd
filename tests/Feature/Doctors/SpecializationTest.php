<?php

namespace Tests\Feature\Doctors;

use App\Domains\Doctors\Models\Specialization;
use Database\Seeders\SpecializationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;
use Tests\TestCase;

class SpecializationTest extends TestCase
{
    use RefreshDatabase;

    private Specialization $specialization;
    private array $validPayload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SpecializationSeeder::class);
        $this->specialization = Specialization::where('slug', 'cardiology')->first();

        $this->validPayload = [
            'name_ar' => 'الطب النووي',
            'name_en' => 'Nuclear Medicine',
            'slug' => 'nuclear_medicine',
            'description_ar' => 'متخصص في الطب النووي',
            'description_en' => 'Nuclear medicine specialist',
            'is_active' => true,
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

    public function test_can_list_specializations(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('doctor');
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/specializations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => [['id', 'slug', 'name', 'description', 'is_active', 'doctors_count', 'created_at', 'updated_at']],
                'meta' => ['pagination'],
            ]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_can_show_specialization(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('doctor');
        Passport::actingAs($user);

        $response = $this->getJson("/api/v1/specializations/{$this->specialization->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'slug', 'name', 'is_active', 'doctors_count', 'created_at', 'updated_at'],
            ]);

        $this->assertEquals('cardiology', $response->json('data.slug'));
    }

    public function test_show_returns_404_for_nonexistent_specialization(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('doctor');
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/specializations/' . fake()->uuid());

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_create_specialization(): void
    {
        $response = $this->postJson('/api/v1/specializations', $this->validPayload);

        $response->assertStatus(401);
    }

    public function test_non_admin_user_cannot_create_specialization(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('doctor');
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/specializations', $this->validPayload);

        $response->assertStatus(403);
    }

    public function test_can_create_specialization(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/specializations', $this->validPayload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'slug', 'name', 'is_active', 'created_at', 'updated_at'],
            ]);

        $this->assertEquals('Nuclear Medicine', $response->json('data.name.en'));
    }

    public function test_unauthenticated_user_cannot_update_specialization(): void
    {
        $response = $this->putJson("/api/v1/specializations/{$this->specialization->id}", $this->validPayload);

        $response->assertStatus(401);
    }

    public function test_can_update_specialization(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);

        $response = $this->putJson("/api/v1/specializations/{$this->specialization->id}", [
            'name_ar' => 'طب القلب والشرايين',
            'name_en' => 'Cardiology Updated',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('Cardiology Updated', $response->json('data.name.en'));
        $this->assertEquals('cardiology-updated', $response->json('data.slug'));
    }

    public function test_unauthenticated_user_cannot_delete_specialization(): void
    {
        $response = $this->deleteJson("/api/v1/specializations/{$this->specialization->id}");

        $response->assertStatus(401);
    }

    public function test_can_delete_specialization(): void
    {
        $specialization = Specialization::create([
            'name' => ['ar' => 'اختبار', 'en' => 'Test'],
            'slug' => 'test-spec',
        ]);

        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);

        $response = $this->deleteJson("/api/v1/specializations/{$specialization->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('specializations', ['id' => $specialization->id]);
    }

    public function test_cache_hit_returns_stale_data_until_update(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('doctor');
        Passport::actingAs($user);

        $specialization = $this->specialization;
        Cache::forget('specializations:cache_version');

        $response1 = $this->getJson('/api/v1/specializations?limit=50');
        $originalName = $response1->json('data')[0]['name'];

        $specialization->name = ['ar' => 'اسم قديم', 'en' => 'StaleSpec'];
        $specialization->saveQuietly();

        $response2 = $this->getJson('/api/v1/specializations?limit=50');
        $this->assertEquals($originalName, $response2->json('data')[0]['name']);
    }

    public function test_cache_invalidated_after_update(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);
        Cache::forget('specializations:cache_version');

        $this->getJson('/api/v1/specializations?limit=50');

        $this->putJson("/api/v1/specializations/{$this->specialization->id}", [
            'name_ar' => 'طب القلب المحدث',
            'name_en' => 'Cardiology Updated',
        ])->assertStatus(200);

        $response = $this->getJson('/api/v1/specializations?limit=50');
        $updated = collect($response->json('data'))->firstWhere('id', $this->specialization->id);
        $this->assertEquals('Cardiology Updated', $updated['name']['en']);
    }

    public function test_cache_invalidated_after_create(): void
    {
        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);
        Cache::forget('specializations:cache_version');

        $response1 = $this->getJson('/api/v1/specializations?limit=50');
        $count1 = count($response1->json('data'));

        $this->postJson('/api/v1/specializations', $this->validPayload)->assertStatus(201);

        $response2 = $this->getJson('/api/v1/specializations?limit=50');
        $this->assertCount($count1 + 1, $response2->json('data'));
    }

    public function test_cache_invalidated_after_delete(): void
    {
        $specialization = Specialization::create([
            'name' => ['ar' => 'اختبار', 'en' => 'Test'],
            'slug' => 'test-spec',
        ]);

        $user = \App\Models\User::factory()->create();
        $user->assignRole('admin');
        Passport::actingAs($user);
        Cache::forget('specializations:cache_version');

        $response1 = $this->getJson('/api/v1/specializations?limit=50');
        $initialCount = count($response1->json('data'));

        $this->deleteJson("/api/v1/specializations/{$specialization->id}")->assertStatus(204);

        $response2 = $this->getJson('/api/v1/specializations?limit=50');
        $this->assertCount($initialCount - 1, $response2->json('data'));
    }
}
