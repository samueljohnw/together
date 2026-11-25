<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Alerts every other household member's subscribed devices that there is new activity.
 *
 * Dispatched after the response so the person posting never waits on push services.
 */
class SendActivityPushNotifications
{
    use Queueable;

    public function __construct(public User $actor) {}

    public function handle(PushNotificationService $push): void
    {
        PushSubscription::query()
            ->whereNot('user_id', $this->actor->getKey())
            ->whereNotNull('user_id')
            ->each(function (PushSubscription $subscription) use ($push): void {
                $push->send($subscription);
            });
    }
}
