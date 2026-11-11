<?php

namespace App\Http\Controllers;

use App\Models\CourierPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourierPushSubscriptionController extends Controller
{
    public function publicKey(): JsonResponse
    {
        $key = config('services.webpush.public_key');
        abort_unless(is_string($key) && $key !== '', 503, 'Les notifications push ne sont pas encore configurées.');

        return response()->json(['publicKey' => $key]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->user_type === 'LVS' && $request->user()->active, 403);

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:4096'],
            'keys.p256dh' => ['required', 'string', 'max:512'],
            'keys.auth' => ['required', 'string', 'max:256'],
            'contentEncoding' => ['nullable', 'in:aes128gcm,aesgcm'],
        ]);

        $host = strtolower((string) parse_url($data['endpoint'], PHP_URL_HOST));
        $trustedHost = $host === 'fcm.googleapis.com'
            || $host === 'web.push.apple.com'
            || $host === 'updates.push.services.mozilla.com'
            || str_ends_with($host, '.push.services.mozilla.com')
            || str_ends_with($host, '.notify.windows.com');
        abort_unless(parse_url($data['endpoint'], PHP_URL_SCHEME) === 'https' && $trustedHost, 422, 'Le service de notification du navigateur n’est pas reconnu.');
        $hash = hash('sha256', $data['endpoint']);

        CourierPushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => $hash],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aes128gcm',
                'last_used_at' => now(),
            ]
        );

        return response()->json(['message' => 'Notifications activées sur cet appareil.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        abort_unless($request->user()?->user_type === 'LVS', 403);

        $data = $request->validate(['endpoint' => ['required', 'string', 'url', 'max:4096']]);
        CourierPushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->delete();

        return response()->json(['message' => 'Notifications désactivées sur cet appareil.']);
    }
}
