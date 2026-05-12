<?php

namespace Tests\Feature\Auth;

use App\Enums\GenderEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private array $validPatientData;
    private array $validDoctorData;
    private array $validReceptionistData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validPatientData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'address' => '123 Main St',
            'gender' => GenderEnum::Male->value,
            'birthday_date' => '1990-01-01',
            'password' => 'Password1!',
        ];

        $this->validDoctorData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'username' => 'janesmith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
            'address' => '456 Oak Ave',
            'gender' => GenderEnum::Female->value,
            'birthday_date' => '1985-05-15',
            'password' => 'Password1!',
        ];

        $this->validReceptionistData = [
            'first_name' => 'Bob',
            'last_name' => 'Johnson',
            'username' => 'bobjohnson',
            'email' => 'bob@example.com',
            'phone' => '5551234567',
            'address' => '789 Pine Rd',
            'gender' => GenderEnum::Male->value,
            'birthday_date' => '1995-03-20',
            'password' => 'Password1!',
            'shift_start' => '09:00',
            'shift_end' => '17:00',
        ];

        Http::fake([
            '*/oauth/token' => Http::response([
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
            ], 200),
        ]);
    }

    private function assertApiResponseStructure(array $json, array $keys = ['data']): void
    {
        $this->assertArrayHasKey('status', $json);
        $this->assertArrayHasKey('message', $json);
        $this->assertArrayHasKey('data', $json);
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $json);
        }
    }

    // ---- Registration Tests ----

    public function test_can_register_patient(): void
    {
        $response = $this->postJson('/api/v1/auth/register/patient', $this->validPatientData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['access_token', 'refresh_token', 'expires_in', 'token_type', 'user'],
            ]);

        $json = $response->json();
        $this->assertEquals(201, $json['status']);
        $this->assertEquals(__('auth.register_success'), $json['message']);
        $this->assertEquals('john@example.com', $json['data']['user']['email']);
        $this->assertEquals(RoleEnum::Patient->value, $json['data']['user']['role']);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('patients', ['user_id' => User::where('email', 'john@example.com')->first()->id]);
    }

    public function test_can_register_doctor(): void
    {
        $response = $this->postJson('/api/v1/auth/register/doctor', $this->validDoctorData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['access_token', 'refresh_token', 'expires_in', 'token_type', 'user'],
            ]);

        $json = $response->json();
        $this->assertEquals(201, $json['status']);
        $this->assertEquals(__('auth.register_success'), $json['message']);
        $this->assertEquals('jane@example.com', $json['data']['user']['email']);
        $this->assertEquals(RoleEnum::Doctor->value, $json['data']['user']['role']);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_can_register_receptionist(): void
    {
        $response = $this->postJson('/api/v1/auth/register/receptionist', $this->validReceptionistData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['access_token', 'refresh_token', 'expires_in', 'token_type', 'user'],
            ]);

        $json = $response->json();
        $this->assertEquals(201, $json['status']);
        $this->assertEquals(__('auth.register_success'), $json['message']);
        $this->assertEquals('bob@example.com', $json['data']['user']['email']);
        $this->assertEquals(RoleEnum::Receptionist->value, $json['data']['user']['role']);
        $this->assertEquals('09:00', $json['data']['user']['shift_start']);
        $this->assertEquals('17:00', $json['data']['user']['shift_end']);

        $this->assertDatabaseHas('users', ['email' => 'bob@example.com']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        $this->postJson('/api/v1/auth/register/patient', $this->validPatientData);

        $response = $this->postJson('/api/v1/auth/register/patient', $this->validPatientData);

        $response->assertStatus(422)
            ->assertJsonStructure(['status', 'message', 'errors']);
        $this->assertEquals(422, $response->json()['status']);
    }

    public function test_registration_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register/patient', []);

        $response->assertStatus(422);
        $json = $response->json();
        $this->assertEquals(422, $json['status']);
        $this->assertArrayHasKey('errors', $json);
    }

    // ---- Login Tests ----

    public function test_can_login(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password1!'),
            'role' => RoleEnum::Patient,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['access_token', 'refresh_token', 'expires_in', 'token_type', 'user'],
            ]);

        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals(__('auth.login_success'), $json['message']);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401);
        $json = $response->json();
        $this->assertEquals(401, $json['status']);
        $this->assertEquals(__('auth.failed'), $json['message']);
    }

    // ---- Token Refresh Tests ----

    public function test_can_refresh_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'valid-refresh-token',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['access_token', 'refresh_token', 'expires_in', 'token_type'],
            ]);

        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals(__('auth.refresh_success'), $json['message']);
    }

    public function test_refresh_fails_without_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(422);
    }

    // ---- Authenticated Endpoint Tests ----

    public function test_can_get_profile(): void
    {
        $user = User::factory()->create(['role' => RoleEnum::Patient]);
        $user->patient()->create([]);
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals(__('auth.profile_retrieved'), $json['message']);
        $this->assertEquals($user->email, $json['data']['email']);
    }

    public function test_profile_returns_correct_resource_by_role(): void
    {
        $user = User::factory()->create(['role' => RoleEnum::Receptionist]);
        $user->receptionist()->create(['shift_start' => '09:00', 'shift_end' => '17:00']);
        Passport::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals('09:00', $json['data']['shift_start']);
        $this->assertEquals('17:00', $json['data']['shift_end']);
    }

    public function test_can_logout(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals(__('auth.logout_success'), $json['message']);
    }

    public function test_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPass1!'),
        ]);
        Passport::actingAs($user);

        $response = $this->putJson('/api/v1/auth/password', [
            'old_password' => 'CurrentPass1!',
            'new_password' => 'NewPass123!',
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals(__('auth.password_changed'), $json['message']);

        $this->assertTrue(Hash::check('NewPass123!', $user->fresh()->password));
    }

    public function test_change_password_fails_with_wrong_old_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPass1!'),
        ]);
        Passport::actingAs($user);

        $response = $this->putJson('/api/v1/auth/password', [
            'old_password' => 'WrongPassword1!',
            'new_password' => 'NewPass123!',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password1!'),
        ]);
        Passport::actingAs($user);

        $response = $this->deleteJson('/api/v1/auth/account', [
            'password' => 'Password1!',
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals(200, $json['status']);
        $this->assertEquals(__('auth.account_deleted'), $json['message']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_delete_account_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password1!'),
        ]);
        Passport::actingAs($user);

        $response = $this->deleteJson('/api/v1/auth/account', [
            'password' => 'WrongPassword1!',
        ]);

        $response->assertStatus(422);
    }

    // ---- Unauthenticated Access Tests ----

    public function test_unauthenticated_user_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
        $this->postJson('/api/v1/auth/logout')->assertStatus(401);
        $this->putJson('/api/v1/auth/password', [])->assertStatus(401);
        $this->deleteJson('/api/v1/auth/account', [])->assertStatus(401);
    }
}
