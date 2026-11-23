<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StockLocation;
use App\Services\FinanceCashLedgerService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CourierBonusPayoutController extends Controller
{
    public function request(Request $request, int $week): RedirectResponse
    {
        $courier = $request->user();
        abort_unless($courier->user_type === User::ROLE_LIVREUR, 403);
        $location = $courier->primaryStockLocation;
        if (! $location || $location->type !== 'shop' || ! $location->is_active) {
            throw ValidationException::withMessages(['bonus' => 'Ta boutique de rattachement n’est pas configurée. Contacte le responsable avant de demander le retrait.']);
        }

        DB::transaction(function () use ($courier, $week, $location): void {
            $period = DB::table('courier_bonus_weeks')->where('id', $week)->lockForUpdate()->first();
            abort_unless($period, 404);
            if ($period->status !== 'approved' || Carbon::parse($period->week_end)->isFuture()) {
                throw ValidationException::withMessages(['bonus' => 'La demande sera disponible après la fin de la semaine et l’approbation finale des primes.']);
            }

            $award = DB::table('courier_bonus_awards')->where('week_id', $period->id)->where('courier_id', $courier->id)->first();
            if (! $award || (int) $award->total_amount <= 0) {
                throw ValidationException::withMessages(['bonus' => 'Aucune prime n’est à retirer pour cette semaine.']);
            }

            $existing = DB::table('courier_bonus_payout_requests')->where('week_id', $period->id)->where('courier_id', $courier->id)->lockForUpdate()->first();
            if ($existing && $existing->status !== 'rejected') {
                throw ValidationException::withMessages(['bonus' => 'Une demande existe déjà pour cette semaine.']);
            }

            $values = ['amount' => (int) $award->total_amount, 'stock_location_id' => $location->id, 'status' => 'requested', 'requested_at' => now(), 'handled_by' => null, 'handled_at' => null, 'manager_note' => null, 'updated_at' => now()];
            if ($existing) {
                DB::table('courier_bonus_payout_requests')->where('id', $existing->id)->update($values);
            } else {
                $values += ['week_id' => $period->id, 'courier_id' => $courier->id, 'created_at' => now()];
                DB::table('courier_bonus_payout_requests')->insert($values);
            }

            DB::table('courier_bonus_audits')->insert([
                'week_id' => $period->id, 'actor_id' => $courier->id, 'event' => 'payout_requested',
                'reason' => 'Demande de remise de prime envoyée par le livreur.',
                'after_data' => json_encode(['courier_id' => $courier->id, 'amount' => (int) $award->total_amount], JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return redirect()->route('employees.self-service')->with('success', 'Ta demande de retrait a été envoyée au responsable de boutique.');
    }

    public function index(): View
    {
        $locationIds = StockLocation::query()->where('type', 'shop')->where('is_active', true)
            ->where('responsible_user_id', auth()->id())->pluck('id');
        $requests = DB::table('courier_bonus_payout_requests as r')
            ->join('courier_bonus_weeks as w', 'w.id', '=', 'r.week_id')
            ->join('users as u', 'u.id', '=', 'r.courier_id')
            ->leftJoin('users as h', 'h.id', '=', 'r.handled_by')
            ->whereIn('r.stock_location_id', $locationIds)
            ->select('r.*', 'w.week_start', 'w.week_end', 'u.name as courier_name', 'u.phone as courier_phone', 'h.name as handler_name')
            ->orderByRaw("CASE WHEN r.status = 'requested' THEN 0 ELSE 1 END")
            ->orderByDesc('r.requested_at')
            ->paginate(20);

        return view('courier-bonuses.payouts', compact('requests'));
    }

    public function handle(Request $request, int $payout, FinanceCashLedgerService $ledger): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', 'in:paid,rejected'],
            'manager_note' => ['nullable', 'string', 'max:1000', 'required_if:decision,rejected'],
        ]);

        DB::transaction(function () use ($request, $payout, $data, $ledger): void {
            $item = DB::table('courier_bonus_payout_requests')->where('id', $payout)->lockForUpdate()->first();
            abort_unless($item, 404);
            if ($item->status !== 'requested') {
                throw ValidationException::withMessages(['payout' => 'Cette demande a déjà été traitée.']);
            }

            $status = $data['decision'];
            if ($status === 'paid') {
                $location = StockLocation::query()->whereKey($item->stock_location_id)->where('type', 'shop')
                    ->where('is_active', true)->where('responsible_user_id', $request->user()->id)->first();
                abort_unless($location, 403, 'Cette demande ne concerne pas une boutique dont tu es responsable.');
                $ledger->record([
                    'movement_type' => 'expense_payment',
                    'from_custody' => 'shop',
                    'to_custody' => 'external',
                    'amount' => (int) $item->amount,
                    'stock_location_id' => $location->id,
                    'source_type' => 'courier_bonus_payout',
                    'source_id' => $item->id,
                    'source_key' => 'courier-bonus-payout:'.$item->id,
                    'source_reference' => 'Prime livreur · '.Carbon::parse(DB::table('courier_bonus_weeks')->where('id', $item->week_id)->value('week_start'))->format('d/m/Y'),
                    'occurred_at' => now(),
                    'description' => 'Remise de prime hebdomadaire au livreur #'.$item->courier_id.'.',
                    'created_by' => $request->user()->id,
                ]);
            }
            DB::table('courier_bonus_payout_requests')->where('id', $item->id)->update([
                'status' => $status, 'handled_by' => $request->user()->id, 'handled_at' => now(),
                'manager_note' => $data['manager_note'] ?? null, 'updated_at' => now(),
            ]);
            DB::table('courier_bonus_audits')->insert([
                'week_id' => $item->week_id, 'actor_id' => $request->user()->id,
                'event' => $status === 'paid' ? 'payout_handed_over' : 'payout_rejected',
                'reason' => $data['manager_note'] ?? 'Prime remise en espèces au livreur.',
                'before_data' => json_encode(['status' => $item->status, 'amount' => (int) $item->amount], JSON_UNESCAPED_UNICODE),
                'after_data' => json_encode(['status' => $status, 'courier_id' => (int) $item->courier_id, 'amount' => (int) $item->amount], JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $message = $data['decision'] === 'paid' ? 'Remise de la prime confirmée.' : 'Demande refusée. Le livreur pourra la renvoyer après correction.';
        return redirect()->route('courier-bonuses.payouts')->with('success', $message);
    }
}
