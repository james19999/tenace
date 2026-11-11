<?php

namespace App\Services;

use App\Models\CourierPushSubscription;
use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class CourierPushNotificationService
{
    public function notifyOrderAvailability(int $orderId, ?int $actorId = null): void
    {
        $order = Order::query()->with('user')->find($orderId);
        if (! $order || $order->status !== 'ordered' || (int) $order->brouillon !== 1 || $order->type !== 'PU') {
            return;
        }

        $assignedCourier = $order->user?->user_type === User::ROLE_LIVREUR ? $order->user : null;
        if ($assignedCourier) {
            // A courier taking a course must not receive a second "new course" alert.
            if ($actorId && (int) $assignedCourier->id === $actorId) {
                return;
            }
            $recipients = User::query()->whereKey($assignedCourier->id)->where('active', true)->get();
        } else {
            $isAvailable = ! $order->status_order && ! $order->take && blank($order->user_id);
            if (! $isAvailable || ($order->created_at?->toDateString() !== today()->toDateString()
                && (! $order->date_order || $order->date_order < today()->toDateString()))) {
                return;
            }

            $country = strtoupper(trim((string) ($order->country_code ?: $order->shipping_country ?: config('services.fedapay.country_code', 'TG'))));
            $recipients = User::query()
                ->where('user_type', User::ROLE_LIVREUR)
                ->where('active', true)
                ->when($country !== '', fn ($query) => $query->where(function ($q) use ($country): void {
                    $q->whereNull('country_code')->orWhere('country_code', '')->orWhereRaw('UPPER(country_code) = ?', [$country]);
                }))
                ->get(['id']);
        }

        $recipientIds = $recipients->pluck('id');
        if ($recipientIds->isEmpty()) {
            return;
        }

        $subscriptions = CourierPushSubscription::query()->whereIn('user_id', $recipientIds)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $publicKey = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');
        $subject = config('services.webpush.subject');
        if (! $publicKey || ! $privateKey || ! $subject) {
            return;
        }

        $webPush = new WebPush(
            ['VAPID' => ['subject' => $subject, 'publicKey' => $publicKey, 'privateKey' => $privateKey]],
            ['TTL' => 300, 'urgency' => 'normal', 'topic' => 'course-'.$order->id],
            12
        );

        foreach ($subscriptions as $subscription) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                    'contentEncoding' => $subscription->content_encoding,
                ]),
                json_encode([
                    'title' => $assignedCourier ? 'Course programmée pour toi' : 'Nouvelle course disponible',
                    'body' => trim(($order->code ?: 'Commande #'.$order->id).' · '.($order->date_order ? \Carbon\Carbon::parse($order->date_order)->format('d/m/Y') : 'Aujourd’hui').' à '.($order->time ?: 'heure à confirmer')),
                    'url' => route('livrable'),
                    'tag' => 'course-'.$order->id,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                ['TTL' => 300, 'urgency' => 'normal', 'topic' => 'course'.$order->id]
            );
        }

        try {
            $webPush->flushPooled(function ($report): void {
                if (! $report->isSubscriptionExpired()) {
                    return;
                }
                CourierPushSubscription::query()
                    ->where('endpoint_hash', hash('sha256', $report->getEndpoint()))
                    ->delete();
            }, 100, 20);
        } catch (\Throwable $exception) {
            Log::warning('Courier web push delivery failed.', [
                'order_id' => $order->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
