<?php

namespace Tests\Feature\Ratings;

use App\Domains\Ratings\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AppRatingTest extends TestCase
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

    private function createUser(): User
    {
        return User::factory()->create();
    }

    private function createAppRating(User $rater, string $type, int $rating = 5): Rating
    {
        return Rating::create([
            'rater_id' => $rater->id,
            'type' => $type,
            'rating' => $rating,
            'comment' => 'Comment for ' . $type,
        ]);
    }

    public function test_returns_only_app_ratings_excluding_user_type(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createAppRating($user, 'service');
        $this->createAppRating($user, 'center');
        $this->createAppRating($user, 'appointment_system');

        Rating::create([
            'rater_id' => $user->id,
            'type' => 'user',
            'rateable_id' => $user->id,
            'rateable_type' => 'App\Models\User',
            'rating' => 4,
            'comment' => 'User rating',
        ]);

        $response = $this->getJson('/api/v1/app-ratings');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertCount(3, $json['data']);
        $this->assertEquals(200, $json['status']);
    }

    public function test_can_filter_by_single_type(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createAppRating($user, 'service');
        $this->createAppRating($user, 'center');
        $this->createAppRating($user, 'appointment_system');

        $response = $this->getJson('/api/v1/app-ratings?type=service');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertCount(1, $json['data']);
        $this->assertEquals('service', $json['data'][0]['type']);
    }

    public function test_can_filter_by_multiple_types(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createAppRating($user, 'service');
        $this->createAppRating($user, 'center');
        $this->createAppRating($user, 'appointment_system');

        $response = $this->getJson('/api/v1/app-ratings?type[]=service&type[]=center');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertCount(2, $json['data']);
        $types = collect($json['data'])->pluck('type')->toArray();
        $this->assertContains('service', $types);
        $this->assertContains('center', $types);
    }

    public function test_can_filter_by_rating_value(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createAppRating($user, 'service', 5);
        $this->createAppRating($user, 'service', 3);

        $response = $this->getJson('/api/v1/app-ratings?type=service&rating=5');

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertCount(1, $json['data']);
        $this->assertEquals(5, $json['data'][0]['rating']);
    }

    public function test_response_has_simplified_structure(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createAppRating($user, 'service');

        $response = $this->getJson('/api/v1/app-ratings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'rater' => ['name'],
                        'comment',
                        'rating',
                        'created_at',
                    ],
                ],
                'meta' => ['pagination'],
            ]);

        $json = $response->json();
        $this->assertArrayNotHasKey('rateable_id', $json['data'][0]);
        $this->assertArrayNotHasKey('rateable_type', $json['data'][0]);
        $this->assertArrayHasKey('name', $json['data'][0]['rater']);
        $this->assertArrayNotHasKey('first_name', $json['data'][0]['rater']);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $response = $this->getJson('/api/v1/app-ratings');
        $response->assertStatus(401);
    }
}
