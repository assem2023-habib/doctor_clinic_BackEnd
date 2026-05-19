<?php

namespace Tests\Feature\Notifications;

use App\Domains\Notifications\Channels\DatabaseChannel;
use App\Domains\Notifications\Channels\LogChannel;
use App\Domains\Notifications\DTOs\NotificationData;
use App\Domains\Notifications\Models\Notification;
use App\Domains\Notifications\Services\NotificationManager;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_channel_writes_to_log(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn ($message, $context) =>
                str_contains($message, 'test.event') && $context['topic'] === 'test.event'
            );

        $channel = new LogChannel();
        $channel->send(new NotificationData(
            topic: 'test.event',
            title: 'Test Title',
            body: ['key' => 'value'],
            userIds: [],
        ));
    }

    public function test_database_channel_creates_notification_record(): void
    {
        $user = User::factory()->create(['role' => RoleEnum::Patient]);

        $channel = new DatabaseChannel();
        $channel->send(new NotificationData(
            topic: 'test.event',
            title: 'Test Title',
            body: ['message' => 'Hello'],
            userIds: [$user->id],
        ));

        $this->assertDatabaseHas('notifications', [
            'topic' => 'test.event',
            'title' => 'Test Title',
        ]);

        $notification = Notification::where('topic', 'test.event')->first();
        $this->assertNotNull($notification);

        $this->assertDatabaseHas('notification_user', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_database_channel_skips_pivot_when_no_user_ids(): void
    {
        $channel = new DatabaseChannel();
        $channel->send(new NotificationData(
            topic: 'test.event',
            title: 'No Recipients',
            body: [],
            userIds: [],
        ));

        $notification = Notification::where('topic', 'test.event')->first();
        $this->assertNotNull($notification);
        $this->assertCount(0, $notification->users);
    }

    public function test_notification_manager_sends_to_enabled_channels(): void
    {
        $user = User::factory()->create(['role' => RoleEnum::Patient]);

        $manager = app(NotificationManager::class);

        config(['notification.events.test_custom_event' => ['log', 'database']]);
        config(['notification.channels.log.enabled' => true]);
        config(['notification.channels.database.enabled' => true]);

        $manager->send('test_custom_event', new NotificationData(
            topic: 'test_custom_event',
            title: 'Manager Test',
            body: ['msg' => 'test'],
            userIds: [$user->id],
        ));

        $this->assertDatabaseHas('notifications', [
            'topic' => 'test_custom_event',
            'title' => 'Manager Test',
        ]);
    }

    public function test_notification_manager_skips_disabled_channel(): void
    {
        $manager = app(NotificationManager::class);

        config(['notification.events.test_skipped_event' => ['log']]);
        config(['notification.channels.log.enabled' => false]);

        Log::shouldReceive('info')->never();

        $manager->send('test_skipped_event', new NotificationData(
            topic: 'test_skipped_event',
            title: 'Should Not Log',
            body: [],
            userIds: [],
        ));
    }

    public function test_notification_manager_skips_unknown_channel(): void
    {
        $manager = app(NotificationManager::class);

        config(['notification.events.test_unknown_event' => ['nonexistent']]);

        Log::shouldReceive('info')->never();

        $manager->send('test_unknown_event', new NotificationData(
            topic: 'test_unknown_event',
            title: 'Unknown Channel',
            body: [],
            userIds: [],
        ));
    }

    public function test_notification_manager_skips_undefined_event(): void
    {
        $manager = app(NotificationManager::class);

        Log::shouldReceive('info')->never();

        $manager->send('undefined.event', new NotificationData(
            topic: 'undefined.event',
            title: 'Undefined',
            body: [],
            userIds: [],
        ));
    }
}
