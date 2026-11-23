<?php

namespace App\Services;

use App\Models\Orders\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class CourierWeeklyBonusCalculator
{
    private const ENVELOPES = ['productivity' => 4500, 'speed' => 6000, 'presence' => 1500, 'professionalism' => 3000];
    private const RATING_FIELDS = ['politeness', 'presentation', 'instructions', 'money_handling', 'discipline'];

    public function eligibleOrders(object $week): Collection
    {
        return $this->deliveredOrders($week)->filter(function (Order $order): bool {
            $due = max(0, (int) ($order->montant ?? ((int) $order->total + (int) $order->tax)));
            $confirmed = (int) $order->payments->whereIn('status', ['collected', 'confirmed'])->sum('amount');
            $proofSatisfied = $order->delivery_mode !== 'expedition' || $order->deliveryPhotos->isNotEmpty();
            return $proofSatisfied && (int) $order->paid_amount >= $due && $confirmed >= $due;
        })->values();
    }

    public function deliveredOrders(object $week): Collection
    {
        return Order::query()->with(['deliveryPhotos:id,order_id', 'payments:id,order_id,amount,status', 'user:id,name'])
            ->where('status', 'delivered')->whereNotNull('user_id')
            ->whereBetween('delivered_at', [$week->week_start.' 00:00:00', $week->week_end.' 23:59:59'])
            ->get();
    }

    public function calculate(object $week): array
    {
        $couriers = User::query()->where('user_type', User::ROLE_LIVREUR)->where('active', true)->orderBy('id')->get();
        $orders = $this->eligibleOrders($week);
        $deliveredForPresence = $this->deliveredOrders($week);
        $ratings = \DB::table('courier_bonus_ratings')->where('week_id', $week->id)->get()->keyBy('courier_id');
        $exceptions = \DB::table('courier_bonus_late_exceptions')->where('week_id', $week->id)->whereNotNull('validated_at')->pluck('order_id')->flip();
        $points = [];
        $deliveryPresence = $deliveredForPresence->filter(fn (Order $order) => $order->delivered_at)
            ->groupBy(fn (Order $order) => $order->user_id.'|'.$order->delivered_at->toDateString());
        foreach ($couriers as $courier) {
            $mine = $orders->where('user_id', (string) $courier->id);
            $ontime = $mine->filter(function (Order $order) use ($exceptions): bool {
                if ($exceptions->has($order->id)) return true;
                $start = $order->courier_departed_at;
                return $start && $order->delivered_at && ! $start->greaterThan($order->delivered_at)
                    && $start->diffInSeconds($order->delivered_at) <= 45 * 60;
            });
            $deliveredDates = $deliveryPresence->keys()->filter(fn ($key) => str_starts_with($key, $courier->id.'|'))->map(fn ($key) => substr($key, strpos($key, '|') + 1));
            $daysPresent = $deliveredDates->unique()->count();
            $rating = $ratings->get($courier->id);
            $professionalism = $rating ? collect(self::RATING_FIELDS)->sum(fn ($field) => (int) $rating->{$field}) : 0;
            $points[$courier->id] = [
                'courier' => $courier,
                'productivity' => $mine->count(),
                'speed' => $ontime->count(),
                'presence' => $daysPresent,
                'professionalism' => $professionalism,
                'rating' => $rating,
                'orders' => $mine->pluck('id')->values()->all(),
            ];
        }

        $active = [];
        foreach (self::ENVELOPES as $criterion => $envelope) {
            $total = array_sum(array_column($points, $criterion));
            if ($total > 0) $active[$criterion] = $envelope;
        }

        $awards = [];
        foreach ($points as $courierId => $point) {
            $awards[$courierId] = ['productivity' => 0, 'speed' => 0, 'presence' => 0, 'professionalism' => 0];
        }
        if ($active) {
            $weightSum = array_sum($active);
            $cells = [];
            foreach ($active as $criterion => $weight) {
                $totalPoints = array_sum(array_column($points, $criterion));
                $criterionBudget = (int) $week->budget * $weight / $weightSum;
                foreach ($points as $courierId => $point) {
                    if ($point[$criterion] <= 0) continue;
                    $exact = $criterionBudget * $point[$criterion] / $totalPoints;
                    $floor = (int) floor($exact);
                    $cells[] = ['courier_id' => $courierId, 'criterion' => $criterion, 'amount' => $floor, 'remainder' => $exact - $floor];
                }
            }
            $remaining = (int) $week->budget - array_sum(array_column($cells, 'amount'));
            usort($cells, fn ($a, $b) => ($b['remainder'] <=> $a['remainder']) ?: (($a['courier_id'] <=> $b['courier_id']) ?: (array_search($a['criterion'], array_keys(self::ENVELOPES), true) <=> array_search($b['criterion'], array_keys(self::ENVELOPES), true))));
            foreach ($cells as $i => $cell) {
                $awards[$cell['courier_id']][$cell['criterion']] = $cell['amount'] + ($i < $remaining ? 1 : 0);
            }
        }

        return ['points' => $points, 'awards' => $awards, 'orders' => $orders, 'distributed' => array_sum(array_map('array_sum', $awards)), 'active_criteria' => array_keys($active)];
    }
}
