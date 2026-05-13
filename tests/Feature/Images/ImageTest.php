<?php

namespace Tests\Feature\Images;

use App\Domains\Images\Models\Image;
use App\Enums\ImageTypeEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

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

    public function test_unauthenticated_user_cannot_upload_image(): void
    {
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/v1/images', [
            'file' => $file,
            'type' => ImageTypeEnum::User->value,
            'imageable_id' => fake()->uuid(),
        ]);

        $response->assertStatus(401);
    }

    public function test_can_upload_image(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $response = $this->postJson('/api/v1/images', [
            'file' => $file,
            'type' => ImageTypeEnum::User->value,
            'imageable_id' => $user->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status', 'message',
                'data' => ['id', 'url', 'type', 'imageable_id', 'created_at'],
            ]);

        $json = $response->json();
        $this->assertEquals(ImageTypeEnum::User->value, $json['data']['type']);
        $this->assertEquals($user->id, $json['data']['imageable_id']);

        $this->assertDatabaseHas('images', ['imageable_id' => $user->id]);
    }

    public function test_upload_replaces_existing_image(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $file1 = UploadedFile::fake()->image('avatar1.jpg');
        $this->postJson('/api/v1/images', [
            'file' => $file1,
            'type' => ImageTypeEnum::User->value,
            'imageable_id' => $user->id,
        ]);

        $file2 = UploadedFile::fake()->image('avatar2.jpg');
        $response = $this->postJson('/api/v1/images', [
            'file' => $file2,
            'type' => ImageTypeEnum::User->value,
            'imageable_id' => $user->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('images', 1);
    }

    public function test_upload_fails_with_invalid_file_type(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/v1/images', [
            'file' => $file,
            'type' => ImageTypeEnum::User->value,
            'imageable_id' => $user->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_fails_with_file_exceeding_max_size(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $maxSize = ImageTypeEnum::User->maxSize();
        $file = UploadedFile::fake()->create('avatar.jpg', $maxSize + 1);

        $response = $this->postJson('/api/v1/images', [
            'file' => $file,
            'type' => ImageTypeEnum::User->value,
            'imageable_id' => $user->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_fails_with_invalid_type_enum(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->postJson('/api/v1/images', [
            'file' => $file,
            'type' => 'invalid_type',
            'imageable_id' => $user->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_user_cannot_delete_image(): void
    {
        $image = Image::create([
            'url' => 'uploads/user/some-path.jpg',
            'imageable_type' => ImageTypeEnum::User->value,
            'imageable_id' => fake()->uuid(),
        ]);

        $response = $this->deleteJson("/api/v1/images/{$image->id}");

        $response->assertStatus(401);
    }

    public function test_can_delete_image(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg');
        $uploadResponse = $this->postJson('/api/v1/images', [
            'file' => $file,
            'type' => ImageTypeEnum::User->value,
            'imageable_id' => $user->id,
        ]);

        $imageId = $uploadResponse->json()['data']['id'];

        $response = $this->deleteJson("/api/v1/images/{$imageId}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('images', ['id' => $imageId]);
    }

    public function test_delete_returns_404_for_nonexistent_image(): void
    {
        $user = \App\Models\User::factory()->create();
        Passport::actingAs($user);

        $response = $this->deleteJson('/api/v1/images/' . fake()->uuid());

        $response->assertStatus(404);
    }
}
