<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PushNotificationService
{
    public function send(PushSubscription $subscription): bool
    {
        $publicKey = config('services.together.vapid_public_key');
        $privateKey = config('services.together.vapid_private_key');

        if (blank($publicKey) || blank($privateKey)) {
            Log::warning('Web Push is not configured; notification was skipped.');

            return false;
        }

        try {
            $endpoint = $subscription->endpoint;
            $audience = $this->endpointAudience($endpoint);
            $authorization = $this->authorizationHeader($audience, $publicKey, $privateKey);
        } catch (RuntimeException $exception) {
            Log::warning('A Web Push subscription could not be prepared.', [
                'subscription_id' => $subscription->getKey(),
                'reason' => $exception->getMessage(),
            ]);

            return false;
        }

        $curl = curl_init($endpoint);

        if ($curl === false) {
            return false;
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => [
                "Authorization: {$authorization}",
                'TTL: 60',
                'Urgency: normal',
                'Content-Length: 0',
            ],
        ]);

        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($response !== false && $status >= 200 && $status < 300) {
            $subscription->forceFill(['last_used_at' => now()])->save();

            return true;
        }

        if (in_array($status, [404, 410], true)) {
            $subscription->delete();

            return false;
        }

        Log::warning('Web Push delivery failed.', [
            'subscription_id' => $subscription->getKey(),
            'status' => $status,
            'error' => $error,
        ]);

        return false;
    }

    private function endpointAudience(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        $host = strtolower($parts['host'] ?? '');
        $allowedHost = $host === 'fcm.googleapis.com'
            || $host === 'updates.push.services.mozilla.com'
            || str_ends_with($host, '.notify.windows.com')
            || str_ends_with($host, '.push.apple.com');

        if (($parts['scheme'] ?? '') !== 'https' || ! $allowedHost) {
            throw new RuntimeException('The push endpoint host is not supported.');
        }

        $port = isset($parts['port']) && $parts['port'] !== 443 ? ':'.$parts['port'] : '';

        return 'https://'.$host.$port;
    }

    private function authorizationHeader(string $audience, string $publicKey, string $privateKey): string
    {
        $privatePem = base64_decode($privateKey, true);
        $key = $privatePem === false ? false : openssl_pkey_get_private($privatePem);

        if ($key === false) {
            throw new RuntimeException('The VAPID private key is invalid.');
        }

        $details = openssl_pkey_get_details($key);
        $derivedPublicKey = $this->base64UrlEncode("\x04".$details['ec']['x'].$details['ec']['y']);

        if (! hash_equals($publicKey, $derivedPublicKey)) {
            throw new RuntimeException('The VAPID public and private keys do not match.');
        }

        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => now()->addHours(12)->timestamp,
            'sub' => config('services.together.vapid_subject'),
        ], JSON_THROW_ON_ERROR));
        $signingInput = $header.'.'.$claims;

        if (! openssl_sign($signingInput, $derSignature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('The VAPID token could not be signed.');
        }

        $signature = $this->derSignatureToRaw($derSignature);
        $token = $signingInput.'.'.$this->base64UrlEncode($signature);

        return "vapid t={$token}, k={$publicKey}";
    }

    private function derSignatureToRaw(string $signature): string
    {
        $offset = 2;
        $r = $this->readDerInteger($signature, $offset);
        $s = $this->readDerInteger($signature, $offset);

        if (strlen($r) > 32 || strlen($s) > 32) {
            throw new RuntimeException('The VAPID signature has an invalid length.');
        }

        return str_pad($r, 32, "\x00", STR_PAD_LEFT).str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    private function readDerInteger(string $signature, int &$offset): string
    {
        if (($signature[$offset] ?? null) !== "\x02") {
            throw new RuntimeException('The VAPID signature could not be decoded.');
        }

        $length = ord($signature[$offset + 1] ?? "\x00");
        $offset += 2;
        $integer = substr($signature, $offset, $length);
        $offset += $length;

        return ltrim($integer, "\x00");
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
