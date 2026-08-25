<?php

namespace Modules\Auth\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Auth\Models\PushSubscription;
use Modules\Auth\Models\User;

/**
 * Sends Web Push notifications to subscribed browsers using the VAPID protocol.
 *
 * Requires VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY to be set in .env.
 * Generate them with: php artisan webpush:vapid
 */
class WebPushService
{
    /**
     * Send a web push notification to all subscriptions belonging to $user.
     *
     * @param  User   $user
     * @param  array{title: string, body: string, icon?: string, url?: string} $payload
     */
    public function sendToUser(User $user, array $payload): void
    {
        $user->pushSubscriptions()->each(function (PushSubscription $subscription) use ($payload) {
            $this->send($subscription, $payload);
        });
    }

    /**
     * Send a web push notification to a single subscription.
     *
     * This method builds a minimal VAPID-signed HTTP request to the push service
     * endpoint. It uses JSON serialization of the payload (the browser service worker
     * will receive this as the event data).
     *
     * @param  PushSubscription $subscription
     * @param  array            $payload
     */
    public function send(PushSubscription $subscription, array $payload): void
    {
        $vapidPublicKey  = config('webpush.vapid.public_key');
        $vapidPrivateKey = config('webpush.vapid.private_key');
        $vapidSubject    = config('webpush.vapid.subject');

        if (empty($vapidPublicKey) || empty($vapidPrivateKey)) {
            return;
        }

        $endpoint   = $subscription->endpoint;
        $body       = json_encode($payload);
        $expiration = time() + 43200; // 12 hours

        $jwt = $this->buildVapidJwt($endpoint, $vapidSubject, $expiration, $vapidPrivateKey);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'vapid t=' . $jwt . ', k=' . $vapidPublicKey,
                'Content-Type'  => 'application/json',
                'TTL'           => '86400',
            ])->withBody($body, 'application/json')->post($endpoint);

            // If the subscription is no longer valid, remove it.
            if (in_array($response->status(), [404, 410], true)) {
                $subscription->delete();
            }
        } catch (\Throwable) {
            // Silently ignore transient network errors to avoid blocking callers.
        }
    }

    /**
     * Build a VAPID JWT token.
     *
     * @see https://datatracker.ietf.org/doc/html/rfc8292
     */
    private function buildVapidJwt(string $endpoint, string $subject, int $expiration, string $privateKeyBase64Url): string
    {
        $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);

        $header = $this->base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = $this->base64UrlEncode(json_encode([
            'aud' => $audience,
            'sub' => $subject,
            'exp' => $expiration,
        ]));

        $signingInput = $header . '.' . $claims;

        $privateKeyDer = base64_decode(strtr($privateKeyBase64Url, '-_', '+/'));
        $privateKey    = openssl_pkey_get_private(
            $this->derToEcPem($privateKeyDer)
        );

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $signingInput . '.' . $this->base64UrlEncode($this->derSignatureToRaw($signature));
    }

    /**
     * Wrap a raw 32-byte EC private key DER into a PEM-encoded EC private key.
     */
    private function derToEcPem(string $rawPrivateKey): string
    {
        // secp256r1 OID (prime256v1)
        $oid = "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $der = "\x30\x77"                         // SEQUENCE
             . "\x02\x01\x01"                     // version = 1
             . "\x04\x20" . $rawPrivateKey        // privateKey (32 bytes)
             . "\xa0\x0a" . $oid;                 // parameters

        return "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----\n";
    }

    /**
     * Convert a DER-encoded ECDSA signature to a raw 64-byte (r || s) signature.
     */
    private function derSignatureToRaw(string $der): string
    {
        // DER SEQUENCE { INTEGER r, INTEGER s }
        $pos = 2; // skip SEQUENCE tag and length
        // r
        $pos++; // skip INTEGER tag
        $rLen = ord($der[$pos++]);
        $r    = substr($der, $pos, $rLen);
        $pos += $rLen;
        // s
        $pos++; // skip INTEGER tag
        $sLen = ord($der[$pos++]);
        $s    = substr($der, $pos, $sLen);

        // Strip leading zero byte used to indicate positive integer in DER
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        // Pad to 32 bytes each
        return str_pad($r, 32, "\x00", STR_PAD_LEFT)
             . str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
