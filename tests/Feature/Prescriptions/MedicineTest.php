<?php

namespace Tests\Feature\Prescriptions;

use App\Domains\Prescriptions\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class MedicineTest extends TestCase
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

    private function createUser(array $roles = ['patient']): User
    {
        $user = User::factory()->create();
        $user->syncRoles($roles);
        return $user;
    }

    private function createMedicine(array $overrides = []): Medicine
    {
        return Medicine::create(array_merge([
            'name' => ['en' => 'Paracetamol', 'ar' => 'باراسيتامول'],
            'description' => ['en' => 'Pain reliever', 'ar' => 'مسكن ألم'],
            'barcode' => '6281001001001',
            'manufacturer' => 'GSK',
        ], $overrides));
    }

    public function test_can_list_medicines(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createMedicine();
        $this->createMedicine(['name' => ['en' => 'Ibuprofen', 'ar' => 'ايبوبروفين'], 'barcode' => '6281001001002']);

        $response = $this->getJson('/api/v1/medicines');

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

    public function test_can_search_medicines_by_name(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createMedicine();
        $this->createMedicine(['name' => ['en' => 'Ibuprofen', 'ar' => 'ايبوبروفين']]);

        $response = $this->getJson('/api/v1/medicines?search=Paracetamol');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Paracetamol', $json['data'][0]['name']['en']);
    }

    public function test_can_filter_by_manufacturer(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $this->createMedicine();
        $this->createMedicine(['name' => ['en' => 'Ibuprofen', 'ar' => 'ايبوبروفين'], 'manufacturer' => 'Pfizer']);

        $response = $this->getJson('/api/v1/medicines?manufacturer=Pfizer');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertCount(1, $json['data']);
        $this->assertEquals('Ibuprofen', $json['data'][0]['name']['en']);
    }

    public function test_can_show_medicine(): void
    {
        $user = $this->createUser();
        Passport::actingAs($user);

        $medicine = $this->createMedicine();

        $response = $this->getJson("/api/v1/medicines/{$medicine->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'description', 'barcode', 'manufacturer'],
            ]);

        $json = $response->json();
        $this->assertEquals('Paracetamol', $json['data']['name']['en']);
        $this->assertEquals('باراسيتامول', $json['data']['name']['ar']);
        $this->assertEquals('GSK', $json['data']['manufacturer']);
    }

    public function test_can_create_medicine(): void
    {
        $user = $this->createUser(['admin']);
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/medicines', [
            'name_ar' => 'باراسيتامول',
            'name_en' => 'Paracetamol',
            'description_ar' => 'مسكن ألم',
            'description_en' => 'Pain reliever',
            'barcode' => '6281001001001',
            'manufacturer' => 'GSK',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['id', 'name', 'description', 'barcode', 'manufacturer'],
            ]);

        $json = $response->json();
        $this->assertEquals('Paracetamol', $json['data']['name']['en']);
        $this->assertEquals('باراسيتامول', $json['data']['name']['ar']);
        $this->assertEquals('GSK', $json['data']['manufacturer']);
        $this->assertEquals('6281001001001', $json['data']['barcode']);

        $this->assertDatabaseHas('medicines', [
            'barcode' => '6281001001001',
            'manufacturer' => 'GSK',
        ]);
    }

    public function test_can_update_medicine(): void
    {
        $user = $this->createUser(['admin']);
        Passport::actingAs($user);

        $medicine = $this->createMedicine();

        $response = $this->putJson("/api/v1/medicines/{$medicine->id}", [
            'name_ar' => 'باراسيتامول محدث',
            'name_en' => 'Paracetamol Updated',
            'description_ar' => 'مسكن ألم محدث',
            'description_en' => 'Pain reliever updated',
            'barcode' => '6281001001002',
            'manufacturer' => 'Bayer',
        ]);

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertEquals('Paracetamol Updated', $json['data']['name']['en']);
        $this->assertEquals('Bayer', $json['data']['manufacturer']);
    }

    public function test_can_delete_medicine(): void
    {
        $user = $this->createUser(['admin']);
        Passport::actingAs($user);

        $medicine = $this->createMedicine();

        $response = $this->deleteJson("/api/v1/medicines/{$medicine->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('medicines', ['id' => $medicine->id]);
    }

    public function test_patient_can_create_medicine(): void
    {
        $user = $this->createUser(['patient']);
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/medicines', [
            'name_ar' => 'باراسيتامول',
            'name_en' => 'Paracetamol',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('medicines', [
            'name->en' => 'Paracetamol',
        ]);
    }

    public function test_patient_daily_limit_exceeded(): void
    {
        $user = $this->createUser(['patient']);
        Passport::actingAs($user);

        for ($i = 0; $i < 15; $i++) {
            Medicine::create([
                'name' => ['en' => "Medicine $i", 'ar' => "دواء $i"],
                'created_by' => "{$user->id} | {$user->first_name} {$user->last_name}",
            ]);
        }

        $response = $this->postJson('/api/v1/medicines', [
            'name_ar' => 'باراسيتامول',
            'name_en' => 'Paracetamol',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['daily_limit']);
    }

    public function test_patient_can_create_up_to_fifteen_per_day(): void
    {
        $user = $this->createUser(['patient']);
        Passport::actingAs($user);

        for ($i = 0; $i < 14; $i++) {
            Medicine::create([
                'name' => ['en' => "Medicine $i", 'ar' => "دواء $i"],
                'created_by' => "{$user->id} | {$user->first_name} {$user->last_name}",
            ]);
        }

        $response = $this->postJson('/api/v1/medicines', [
            'name_ar' => 'باراسيتامول',
            'name_en' => 'Paracetamol',
        ]);

        $response->assertStatus(201);
    }

    public function test_patient_can_list_medicines(): void
    {
        $user = $this->createUser(['patient']);
        Passport::actingAs($user);

        $this->createMedicine();

        $response = $this->getJson('/api/v1/medicines');

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertCount(1, $json['data']);
    }

    public function test_doctor_can_create_medicine(): void
    {
        $user = $this->createUser(['doctor']);
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/medicines', [
            'name_ar' => 'باراسيتامول',
            'name_en' => 'Paracetamol',
            'manufacturer' => 'GSK',
        ]);

        $response->assertStatus(201);
    }

    public function test_receptionist_can_create_medicine(): void
    {
        $user = $this->createUser(['receptionist']);
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/medicines', [
            'name_ar' => 'باراسيتامول',
            'name_en' => 'Paracetamol',
        ]);

        $response->assertStatus(201);
    }

    public function test_creating_medicine_without_name_en_fails(): void
    {
        $user = $this->createUser(['admin']);
        Passport::actingAs($user);

        $response = $this->postJson('/api/v1/medicines', [
            'name_ar' => 'باراسيتامول',
        ]);

        $response->assertStatus(422);
    }
}
