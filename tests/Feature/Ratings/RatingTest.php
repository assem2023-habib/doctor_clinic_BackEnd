<?php

namespace Tests\Feature\Ratings;

use App\Domains\Ratings\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\Passport;
use Tests\TestCase;

class RatingTest extends TestCase
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

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create($overrides);
    }

    private function createRating(User $rater, array $overrides = []): Rating
    {
        $target = User::factory()->create();

        return Rating::create(array_merge([
            'rater_id' => $rater->id,
            'type' => 'user',
            'rateable_id' => $target->id,
            'rateable_type' => User::class,
            'rating' => 5,
            'comment' => 'Great!',
        ], $overrides));
    }

    public function test_can_list_ratings(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createRating($user);
        $this->createRating($user, ['rating' => 3]);

        $response = $this->getJson('/api/v1/ratings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data',
                'meta' => ['pagination'],
            ]);

        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertCount(2, $json['data']);
    }

    public function test_can_list_ratings_with_filters(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createRating($user, ['rating' => 5]);
        $this->createRating($user, ['rating' => 3]);

        $response = $this->getJson('/api/v1/ratings?rating=5');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals(5, $json['data'][0]['rating']);
    }

    public function test_can_show_rating(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $rating = $this->createRating($user);

        $response = $this->getJson("/api/v1/ratings/{$rating->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'type', 'rating', 'comment', 'rater', 'created_at'],
            ]);

        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals(5, $json['data']['rating']);
    }

    public function test_can_create_rating(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $target = $this->createUser();

        $response = $this->postJson('/api/v1/ratings', [
            'type' => 'user',
            'rateable_id' => $target->id,
            'rateable_type' => User::class,
            'rating' => 5,
            'comment' => 'Excellent service',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'type', 'rating', 'comment'],
            ]);

        $json = $response->json();
        $this->assertEquals('Excellent service', $json['data']['comment']);
        $this->assertEquals(5, $json['data']['rating']);

        $this->assertDatabaseHas('ratings', [
            'rater_id' => $user->id,
            'rateable_id' => $target->id,
            'rating' => 5,
        ]);
    }

    public function test_cannot_create_duplicate_rating(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $target = $this->createUser();

        $this->postJson('/api/v1/ratings', [
            'type' => 'user',
            'rateable_id' => $target->id,
            'rateable_type' => User::class,
            'rating' => 5,
        ]);

        $response = $this->postJson('/api/v1/ratings', [
            'type' => 'user',
            'rateable_id' => $target->id,
            'rateable_type' => User::class,
            'rating' => 4,
        ]);

        $response->assertStatus(409);
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/ratings', []);

        $response->assertStatus(422);
    }

    public function test_can_update_own_rating(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $rating = $this->createRating($user);

        $response = $this->putJson("/api/v1/ratings/{$rating->id}", [
            'rating' => 3,
            'comment' => 'Updated review',
        ]);

        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(3, $json['data']['rating']);
        $this->assertEquals('Updated review', $json['data']['comment']);
    }

    public function test_cannot_update_others_rating(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        Passport::actingAs($other);

        $rating = $this->createRating($owner);

        $response = $this->putJson("/api/v1/ratings/{$rating->id}", [
            'rating' => 2,
        ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_own_rating(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $rating = $this->createRating($user);

        $response = $this->deleteJson("/api/v1/ratings/{$rating->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);
    }

    public function test_admin_can_delete_any_rating(): void
    {
        $admin = $this->createUser();
        $admin->assignRole('admin');
        Passport::actingAs($admin);

        $owner = $this->createUser();
        $rating = $this->createRating($owner);

        $response = $this->deleteJson("/api/v1/ratings/{$rating->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('ratings', ['id' => $rating->id]);
    }

    public function test_cannot_delete_others_rating(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        Passport::actingAs($other);

        $rating = $this->createRating($owner);

        $response = $this->deleteJson("/api/v1/ratings/{$rating->id}");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_ratings(): void
    {
        $response = $this->getJson('/api/v1/ratings');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/ratings', []);
        $response->assertStatus(401);
    }

    public function test_ratings_list_cache_hit_returns_stale_data_until_update(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);
        Cache::forget('ratings:cache_version');

        $rating = $this->createRating($user);
        $rating->load('rater');

        $response1 = $this->getJson('/api/v1/ratings');
        $originalRating = $response1->json('data')[0]['rating'];

        $rating->rating = 1;
        $rating->saveQuietly();

        $response2 = $this->getJson('/api/v1/ratings');
        $this->assertEquals($originalRating, $response2->json('data')[0]['rating']);
    }

    public function test_ratings_list_cache_invalidated_after_create(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);
        Cache::forget('ratings:cache_version');

        $response1 = $this->getJson('/api/v1/ratings');
        $count1 = count($response1->json('data'));

        $target = $this->createUser();
        $this->postJson('/api/v1/ratings', [
            'type' => 'user',
            'rateable_id' => $target->id,
            'rateable_type' => User::class,
            'rating' => 4,
            'comment' => 'Nice!',
        ])->assertStatus(201);

        $response2 = $this->getJson('/api/v1/ratings');
        $this->assertCount($count1 + 1, $response2->json('data'));
    }

    public function test_ratings_list_cache_invalidated_after_update(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);
        Cache::forget('ratings:cache_version');

        $rating = $this->createRating($user);

        $this->getJson('/api/v1/ratings');

        $this->putJson("/api/v1/ratings/{$rating->id}", [
            'rating' => 2,
            'comment' => 'Changed',
        ])->assertStatus(200);

        $response = $this->getJson('/api/v1/ratings');
        $this->assertEquals(2, $response->json('data')[0]['rating']);
    }

    public function test_ratings_show_cache_hit_returns_stale_data_until_invalidation(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);
        Cache::forget('ratings:cache_version');

        $rating = $this->createRating($user);

        $response1 = $this->getJson("/api/v1/ratings/{$rating->id}");
        $originalRating = $response1->json('data')['rating'];

        $rating->comment = 'Modified directly';
        $rating->saveQuietly();

        $response2 = $this->getJson("/api/v1/ratings/{$rating->id}");
        $this->assertEquals($originalRating, $response2->json('data')['rating']);
    }
}
