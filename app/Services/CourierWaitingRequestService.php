<?php

namespace App\Services;

use App\Models\CourierOrderCancellationRequest;
use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourierWaitingRequestService
{
    /**
     * Enregistre une mise en attente. Deux commandes peuvent être en attente
     * simultanément sans validation; la suivante reste soumise à validation.
     *
     * @return array{request: CourierOrderCancellationRequest, requires_review: bool, waiting_count: int}
     */
    public function submit(Order $order, User $courier, string $reason): array
    {
        return DB::transaction(function () use ($order, $courier, $reason): array {
            // Sérialise les demandes d'un même livreur pour éviter que deux
            // requêtes simultanées dépassent le plafond automatique de deux.
            User::query()->whereKey($courier->id)->lockForUpdate()->firstOrFail();
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ((string) $lockedOrder->user_id !== (string) $courier->id || $lockedOrder->status !== 'ordered') {
                throw ValidationException::withMessages([
                    'motif' => 'Cette commande n’est plus sous ta responsabilité.',
                ]);
            }

            if (CourierOrderCancellationRequest::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', 'pending')
                ->exists()) {
                throw ValidationException::withMessages([
                    'motif' => 'Une demande est déjà en attente de la décision du responsable.',
                ]);
            }

            $waitingCount = Order::query()
                ->where('user_id', $courier->id)
                ->where('status', 'ordered')
                ->where('delivery_waiting', true)
                ->count() + 1;
            $requiresReview = $waitingCount > 2;

            $waitingRequest = CourierOrderCancellationRequest::query()->create([
                'order_id' => $lockedOrder->id,
                'courier_id' => $courier->id,
                'request_type' => 'waiting',
                'reason' => trim($reason),
                'was_delivery_waiting' => (bool) $lockedOrder->delivery_waiting,
                'status' => $requiresReview ? 'pending' : 'approved',
                'reviewed_at' => $requiresReview ? null : now(),
                'review_note' => $requiresReview ? null : 'Mise en attente automatique ('.$waitingCount.' commande(s) en attente sur 2 autorisées sans validation).',
            ]);

            if (! $requiresReview) {
                $lockedOrder->delivery_waiting = true;
                $lockedOrder->motif = trim($reason);
                $lockedOrder->status_order = true;
                $lockedOrder->take = true;
                $lockedOrder->save();
            }

            return [
                'request' => $waitingRequest->load(['order', 'courier']),
                'requires_review' => $requiresReview,
                'waiting_count' => $waitingCount,
            ];
        });
    }
}
