<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PushController extends Controller
{
    public function storeSubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription.endpoint' => ['required', 'url:https', 'max:2048'],
            'subscription.keys.p256dh' => ['required', 'string', 'max:255'],
            'subscription.keys.auth' => ['required', 'string', 'max:255'],
        ]);

        $subscription = $validated['subscription'];
        $endpoint = $subscription['endpoint'];
        $this->validatePushEndpoint($endpoint);

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'user_id' => $request->user()->getKey(),
                'endpoint' => $endpoint,
                'public_key' => $subscription['keys']['p256dh'],
                'auth_token' => $subscription['keys']['auth'],
                'user_agent' => Str::limit((string) $request->userAgent(), 1024, ''),
            ],
        );

        return response()->json(['subscribed' => true], 201);
    }

    public function destroySubscription(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url:https', 'max:2048'],
        ]);

        PushSubscription::where('endpoint_hash', hash('sha256', $validated['endpoint']))->delete();

        return response()->json(['subscribed' => false]);
    }

    private function validatePushEndpoint(string $endpoint): void
    {
        $host = strtolower(parse_url($endpoint, PHP_URL_HOST) ?: '');
        $scheme = strtolower(parse_url($endpoint, PHP_URL_SCHEME) ?: '');
        $isSupported = $host === 'fcm.googleapis.com'
            || $host === 'updates.push.services.mozilla.com'
            || str_ends_with($host, '.notify.windows.com')
            || str_ends_with($host, '.push.apple.com');

        if ($scheme !== 'https' || ! $isSupported) {
            throw ValidationException::withMessages([
                'endpoint' => 'This browser push service is not supported.',
            ]);
        }
    }
}
