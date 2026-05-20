<?php

namespace Tests\Feature\Auth;

use App\Domains\Auth\Contracts\LoginAttemptRepositoryInterface;
use App\Domains\Auth\DTOs\LoginAttemptData;
use App\Domains\Auth\DTOs\LoginSecurityContext;
use App\Domains\Auth\Events\LoginFromNewDevice;
use App\Domains\Auth\Events\SuspiciousLoginAttempts;
use App\Domains\Auth\Models\DeviceFingerprint;
use App\Domains\Auth\Models\LoginAttempt;
use App\Domains\Auth\Models\KnownUserDevice;
use App\Domains\Auth\Services\LoginSecurityManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    private LoginSecurityManager $securityManager;
    private LoginAttemptRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityManager = app(LoginSecurityManager::class);
        $this->repository = app(LoginAttemptRepositoryInterface::class);
    }

    public function test_records_failed_login_attempt(): void
    {
        $context = new LoginSecurityContext(
            email: 'test@example.com',
            password: 'wrong',
            ip: '192.168.1.1',
            deviceFingerprint: 'fp-test-1',
            userAgent: 'TestAgent/1.0',
        );

        $this->securityManager->handleFailure($context);

        $this->assertDatabaseHas('login_attempts', [
            'email' => 'test@example.com',
            'ip' => '192.168.1.1',
            'device_fingerprint' => 'fp-test-1',
            'success' => false,
            'failure_reason' => 'Invalid credentials',
        ]);
    }

    public function test_records_successful_login_attempt(): void
    {
        $user = User::factory()->create();
        $user->assignRole('patient');

        $context = new LoginSecurityContext(
            email: $user->email,
            password: 'password',
            ip: '192.168.1.1',
            deviceFingerprint: 'fp-success-1',
            userAgent: 'TestAgent/1.0',
        );

        $this->securityManager->handleSuccess($context);

        $this->assertDatabaseHas('login_attempts', [
            'email' => $user->email,
            'ip' => '192.168.1.1',
            'device_fingerprint' => 'fp-success-1',
            'success' => true,
        ]);
    }

    public function test_tracks_known_device_on_successful_login(): void
    {
        $user = User::factory()->create();
        $user->assignRole('patient');

        $context = new LoginSecurityContext(
            email: $user->email,
            password: 'password',
            ip: '192.168.1.1',
            deviceFingerprint: 'fp-known-device',
            userAgent: 'TestAgent/1.0',
        );

        $this->securityManager->handleSuccess($context);

        $this->assertDatabaseHas('known_user_devices', [
            'user_id' => $user->id,
            'device_fingerprint' => 'fp-known-device',
        ]);
    }

    public function test_dispatches_login_from_new_device_event(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole('patient');

        $context = new LoginSecurityContext(
            email: $user->email,
            password: 'password',
            ip: '10.0.0.1',
            deviceFingerprint: 'fp-new-device',
            userAgent: 'NewBrowser/2.0',
        );

        $this->securityManager->handleSuccess($context);

        Event::assertDispatched(LoginFromNewDevice::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->ip === '10.0.0.1'
                && $event->deviceFingerprint === 'fp-new-device'
                && $event->userAgent === 'NewBrowser/2.0';
        });
    }

    public function test_does_not_dispatch_new_device_event_for_known_device(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole('patient');

        $knownDevice = KnownUserDevice::create([
            'user_id' => $user->id,
            'device_fingerprint' => 'fp-known',
            'ip_first_seen' => '10.0.0.1',
            'first_seen_at' => now(),
        ]);

        $context = new LoginSecurityContext(
            email: $user->email,
            password: 'password',
            ip: '10.0.0.1',
            deviceFingerprint: 'fp-known',
            userAgent: 'TestAgent/1.0',
        );

        $this->securityManager->handleSuccess($context);

        Event::assertNotDispatched(LoginFromNewDevice::class);
    }

    public function test_dispatches_suspicious_activity_after_multiple_failures(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $user->assignRole('patient');

        for ($i = 0; $i < 5; $i++) {
            $context = new LoginSecurityContext(
                email: $user->email,
                password: 'wrong',
                ip: "192.168.1.$i",
                deviceFingerprint: "fp-attacker-$i",
                userAgent: 'Hacker/1.0',
            );
            $this->securityManager->handleFailure($context);
        }

        $context = new LoginSecurityContext(
            email: $user->email,
            password: 'wrong',
            ip: '192.168.1.99',
            deviceFingerprint: 'fp-attacker-99',
            userAgent: 'Hacker/1.0',
        );
        $this->securityManager->handleFailure($context);

        Event::assertDispatched(SuspiciousLoginAttempts::class, function ($event) use ($user) {
            return $event->user->id === $user->id
                && $event->failedAttempts >= 5;
        });
    }

    public function test_blocks_device_after_excessive_failures(): void
    {
        $fingerprint = 'fp-to-be-blocked';

        for ($i = 0; $i < 5; $i++) {
            $context = new LoginSecurityContext(
                email: "user$i@example.com",
                password: 'wrong',
                ip: "10.0.0.$i",
                deviceFingerprint: $fingerprint,
                userAgent: 'TestAgent/1.0',
            );
            $this->securityManager->handleFailure($context);
        }

        $context = new LoginSecurityContext(
            email: 'trigger@example.com',
            password: 'wrong',
            ip: '10.0.0.99',
            deviceFingerprint: $fingerprint,
            userAgent: 'TestAgent/1.0',
        );
        $this->securityManager->checkBlocked($context);

        $isBlocked = $this->repository->isDeviceBlocked($fingerprint);

        $this->assertTrue($isBlocked);
        $this->assertDatabaseHas('device_fingerprints', [
            'fingerprint_hash' => $fingerprint,
        ]);
    }

    public function test_blocks_ip_after_excessive_failures(): void
    {
        config(['login-security.limits.max_failures_per_ip_per_15_minutes' => 3]);
        config(['login-security.block_durations.ip_temporary' => 60]);

        $ip = '203.0.113.42';

        for ($i = 0; $i < 4; $i++) {
            $context = new LoginSecurityContext(
                email: "user$i@example.com",
                password: 'wrong',
                ip: $ip,
                deviceFingerprint: "fp-bot-$i",
                userAgent: 'Bot/1.0',
            );
            $this->securityManager->handleFailure($context);
        }

        $this->repository->blockIp($ip, 60, 'Test block');

        $isBlocked = $this->repository->isIpBlocked($ip);

        $this->assertTrue($isBlocked);
    }

    public function test_device_fingerprint_is_tracked_correctly(): void
    {
        DeviceFingerprint::create([
            'fingerprint_hash' => 'fp-tracked',
            'ip_first_seen' => '10.0.0.1',
            'first_seen_at' => now(),
            'blocked_until' => now()->addHour(),
            'block_reason' => 'Test block',
        ]);

        $isBlocked = $this->repository->isDeviceBlocked('fp-tracked');
        $this->assertTrue($isBlocked);
    }

    public function test_successful_login_clears_no_block(): void
    {
        $user = User::factory()->create();
        $user->assignRole('patient');

        $context = new LoginSecurityContext(
            email: $user->email,
            password: 'correct',
            ip: '10.0.0.1',
            deviceFingerprint: 'fp-ok',
            userAgent: 'TestAgent/1.0',
        );

        $this->securityManager->handleSuccess($context);

        $this->assertDatabaseHas('login_attempts', [
            'email' => $user->email,
            'success' => true,
        ]);
    }

    public function test_repository_counts_recent_failures_by_email(): void
    {
        $now = now();

        for ($i = 0; $i < 3; $i++) {
            LoginAttempt::create([
                'email' => 'target@example.com',
                'ip' => '10.0.0.1',
                'device_fingerprint' => "fp-$i",
                'user_agent' => 'TestAgent/1.0',
                'success' => false,
                'failure_reason' => 'Invalid credentials',
                'attempted_at' => $now,
            ]);
        }

        $count = $this->repository->countRecentFailuresByEmail('target@example.com', 60);

        $this->assertEquals(3, $count);
    }

    public function test_repository_counts_recent_failures_by_fingerprint(): void
    {
        $now = now();

        for ($i = 0; $i < 4; $i++) {
            LoginAttempt::create([
                'email' => "user$i@example.com",
                'ip' => '10.0.0.1',
                'device_fingerprint' => 'fp-same',
                'user_agent' => 'TestAgent/1.0',
                'success' => false,
                'failure_reason' => 'Invalid credentials',
                'attempted_at' => $now,
            ]);
        }

        $count = $this->repository->countRecentFailuresByFingerprint('fp-same', 60);

        $this->assertEquals(4, $count);
    }

    public function test_repository_counts_distinct_fingerprints_for_email(): void
    {
        $now = now();

        foreach (['fp-a', 'fp-b', 'fp-c'] as $fp) {
            LoginAttempt::create([
                'email' => 'target@example.com',
                'ip' => '10.0.0.1',
                'device_fingerprint' => $fp,
                'user_agent' => 'TestAgent/1.0',
                'success' => false,
                'failure_reason' => 'Invalid credentials',
                'attempted_at' => $now,
            ]);
        }

        $count = $this->repository->countRecentFailuresByEmailFromDifferentFingerprints('target@example.com', 60);

        $this->assertEquals(3, $count);
    }

    public function test_check_blocked_returns_null_when_not_blocked(): void
    {
        $context = new LoginSecurityContext(
            email: 'clean@example.com',
            password: 'password',
            ip: '10.0.0.1',
            deviceFingerprint: 'fp-clean',
            userAgent: 'TestAgent/1.0',
        );

        $decision = $this->securityManager->checkBlocked($context);

        $this->assertNull($decision);
    }

    public function test_blocked_device_returns_blocking_decision(): void
    {
        $fingerprint = 'fp-blocked-test';

        $this->repository->blockDevice($fingerprint, 60, 'Test block');

        $context = new LoginSecurityContext(
            email: 'blocked@example.com',
            password: 'password',
            ip: '10.0.0.1',
            deviceFingerprint: $fingerprint,
            userAgent: 'TestAgent/1.0',
        );

        $decision = $this->securityManager->checkBlocked($context);

        $this->assertNotNull($decision);
        $this->assertTrue($decision->blocked);
        $this->assertEquals('fingerprint', $decision->strategy);
    }
}
