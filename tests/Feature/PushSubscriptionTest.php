<?php

use App\Jobs\SendActivityPushNotifications;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushNotificationService;

use function Pest\Laravel\mock;

test('a subscription is saved for the signed-in member', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('push.subscriptions.store'), [
            'subscription' => [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
            ],
        ])
        ->assertCreated();

    expect(PushSubscription::sole()->user->is($user))->toBeTrue();
});

test('activity notifies the other members devices but not the actor', function () {
    $actor = User::factory()->create();
    $partner = User::factory()->create();
    PushSubscription::factory()->for($actor)->create();
    $partnerDevice = PushSubscription::factory()->for($partner)->create();

    mock(PushNotificationService::class)
        ->shouldReceive('send')
        ->once()
        ->withArgs(fn (PushSubscription $subscription) => $subscription->is($partnerDevice))
        ->andReturnTrue();

    (new SendActivityPushNotifications($actor))->handle(app(PushNotificationService::class));
});
