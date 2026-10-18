<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Production's legacy product balance represents the selling boutique.
     * This one-time conversion deliberately does not run in local or test environments.
     */
    public function up(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        DB::transaction(function (): void {
            $setting = DB::table('stock_module_settings')->orderByDesc('id')->lockForUpdate()->first();
            $rules = $setting && $setting->rules ? (array) json_decode($setting->rules, true) : [];
            if (! empty($rules['legacy_catalogue_stock_migrated_to_shop'])) {
                return;
            }

            $warehouse = $this->canonicalLocation('ENTREPOT-TENACOS', 'ENTREPOT TENACOS', 'warehouse');
            $shop = $this->canonicalLocation('BOUTIQUE-TENACOS', 'BOUTIQUE TENACOS', 'shop');
            $this->canonicalLocation('TRANSIT', 'Stock en transit', 'transit');
            $this->canonicalLocation('BLOQUE', 'Stock bloqué / quarantaine', 'blocked');

            // Retain historical location IDs for old documents, but hide extra user sites.
            DB::table('stock_locations')->whereIn('type', ['warehouse', 'shop'])
                ->whereNotIn('id', [$warehouse->id, $shop->id])->update(['is_active' => false, 'updated_at' => now()]);

            $products = DB::table('products')->orderBy('id')->lockForUpdate()->get(['id', 'name', 'qt_initial', 'price_market']);
            $balances = DB::table('stock_movement_lines as sml')
                ->join('stock_movements as sm', 'sm.id', '=', 'sml.movement_id')
                ->where('sm.status', 'posted')
                ->whereIn('sml.location_id', DB::table('stock_locations')->whereIn('type', ['warehouse', 'shop'])->select('id'))
                ->select('sml.location_id', 'sml.product_id')
                ->selectRaw('SUM(sml.quantity_delta) as quantity')
                ->groupBy('sml.location_id', 'sml.product_id')
                ->get()->keyBy(fn ($row) => $row->location_id.':'.$row->product_id);

            $now = now();
            $movement = DB::table('stock_movements')->insertGetId([
                'movement_number' => 'TMP-'.Str::uuid(), 'type' => 'opening_balance', 'status' => 'posted',
                'source_type' => 'production_migration', 'source_reference' => 'catalogue-to-boutique-2026-10',
                'occurred_at' => $now, 'origin' => 'migration',
                'reason' => 'Reprise unique du stock catalogue historique vers BOUTIQUE TENACOS.',
                'notes' => 'Les emplacements opérationnels supplémentaires ont été désactivés. ENTREPOT TENACOS démarre à zéro; les quantités réelles seront saisies par inventaire.',
                'metadata' => json_encode(['migration' => '2026_10_17_000002', 'warehouse_id' => $warehouse->id, 'shop_id' => $shop->id]),
                'created_by' => null, 'approved_by' => null, 'posted_by' => null,
                'approved_at' => $now, 'posted_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('stock_movements')->where('id', $movement)->update([
                'movement_number' => sprintf('ST-MVT-%s-%06d', $now->format('Y'), $movement),
            ]);

            foreach ($products as $product) {
                $targetShop = max(0, (float) ($product->qt_initial ?? 0));
                $locations = DB::table('stock_locations')->whereIn('type', ['warehouse', 'shop'])->get(['id', 'type', 'name']);
                foreach ($locations as $location) {
                    $current = (float) ($balances[$location->id.':'.$product->id]->quantity ?? 0);
                    $target = $location->id === $shop->id ? $targetShop : 0.0;
                    $delta = $target - $current;
                    if (abs($delta) < 0.0005) {
                        continue;
                    }

                    DB::table('stock_movement_lines')->insert([
                        'movement_id' => $movement, 'product_id' => $product->id,
                        'location_id' => $location->id, 'quantity_delta' => $delta,
                        'unit_cost' => $product->price_market, 'unit' => 'piece',
                        'notes' => $location->id === $shop->id
                            ? 'Solde catalogue courant repris dans la boutique.'
                            : 'Remise à zéro du solde opérationnel avant la reprise en boutique.',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);

                    DB::table('stock_audit_logs')->insert([
                        'actor_role' => 'SYSTEM', 'action' => 'inventory.production_catalogue_migrated',
                        'subject_type' => 'App\\Models\\Product', 'subject_id' => $product->id,
                        'before_values' => json_encode(['location_id' => $location->id, 'location_name' => $location->name, 'quantity' => $current]),
                        'after_values' => json_encode(['location_id' => $location->id, 'location_name' => $location->name, 'quantity' => $target, 'movement_id' => $movement]),
                        'reason' => 'Reprise des quantités catalogue de production vers BOUTIQUE TENACOS.',
                        'origin' => 'migration', 'result' => 'success', 'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }

            $rules = array_replace($rules, [
                'default_sales_location_id' => (int) $shop->id,
                'legacy_catalogue_stock_migrated_to_shop' => true,
            ]);
            if ($setting) {
                DB::table('stock_module_settings')->where('id', $setting->id)->update([
                    'rules' => json_encode($rules), 'updated_by' => null, 'updated_at' => $now,
                ]);
            } else {
                DB::table('stock_module_settings')->insert([
                    'rules' => json_encode($rules), 'updated_by' => null,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        });
    }

    private function canonicalLocation(string $code, string $name, string $type): object
    {
        $location = DB::table('stock_locations')->where('code', $code)->first();
        if ($location) {
            DB::table('stock_locations')->where('id', $location->id)->update([
                'name' => $name, 'type' => $type, 'is_active' => true,
                'updated_at' => now(),
            ]);
            return (object) ['id' => $location->id];
        }

        $id = DB::table('stock_locations')->insertGetId([
            'code' => $code, 'name' => $name, 'type' => $type, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return (object) ['id' => $id];
    }

    public function down(): void
    {
        // Intentionally irreversible: restore from the required pre-deployment database backup.
    }
};
