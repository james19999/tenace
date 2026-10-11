<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ProductAuditDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Les ventes de démonstration sont réservées aux environnements local et testing.');
        }

        $prefix = 'LOCAL-AUDIT-TEST-';

        if (DB::table('orders')->where('code', 'like', $prefix.'%')->exists()) {
            $this->command?->warn('Les données de démonstration de l’audit existent déjà. Aucune nouvelle commande ajoutée.');

            return;
        }

        $products = DB::table('products')
            ->select('id', 'price')
            ->orderBy('id')
            ->limit(30)
            ->get();

        $demoProducts = DB::table('products')
            ->where('name', 'like', 'DEMO AUDIT - %')
            ->orderBy('id')
            ->get(['id', 'price']);

        $neededDemoProducts = max(0, 11 - $products->count());
        if ($demoProducts->count() < $neededDemoProducts) {
            $now = now();

            for ($index = $demoProducts->count(); $index < $neededDemoProducts; $index++) {
                $product = [
                    'name' => 'DEMO AUDIT - Produit '.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                    'price' => 1000 + ($index * 250),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                foreach ([
                    'price_by' => 500,
                    'price_market' => 1000 + ($index * 250),
                    'qt_initial' => 100,
                    'qts_seuil' => 5,
                    'qts_sell' => 0,
                    'benefice' => 0,
                    'high_price' => 0,
                    'commission_amount' => 0,
                    'description' => 'Donnée locale de démonstration pour tester l’impression de l’audit.',
                ] as $column => $value) {
                    if (Schema::hasColumn('products', $column)) {
                        $product[$column] = $value;
                    }
                }

                DB::table('products')->insert($product);
            }

            $demoProducts = DB::table('products')
                ->where('name', 'like', 'DEMO AUDIT - %')
                ->orderBy('id')
                ->get(['id', 'price']);
        }

        $products = $products->concat($demoProducts)->unique('id')->take(30)->values();

        $now = now();

        DB::transaction(function () use ($products, $prefix, $now): void {
            foreach ($products as $index => $product) {
                $quantity = ($index % 7) + 1;
                $unitPrice = max(0, (float) ($product->price ?? 0));
                $lineTotal = $unitPrice * $quantity;
                $order = [
                    'user_id' => null,
                    'costumer_id' => 'AUDIT-DEMO',
                    'subtotal' => (string) $lineTotal,
                    'tax' => '0',
                    'total' => (string) $lineTotal,
                    'code' => $prefix.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'time' => $now->format('H:i:s'),
                    'status_order' => 1,
                    'status' => 'delivered',
                    'brouillon' => 0,
                    'created_user' => null,
                    'date_order' => $now->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn('orders', 'commission')) {
                    $order['commission'] = '0';
                }
                if (Schema::hasColumn('orders', 'montant')) {
                    $order['montant'] = (string) $lineTotal;
                }
                if (Schema::hasColumn('orders', 'remis')) {
                    $order['remis'] = 0;
                }
                if (Schema::hasColumn('orders', 'take')) {
                    $order['take'] = 0;
                }
                if (Schema::hasColumn('orders', 'type')) {
                    $order['type'] = 'public';
                }
                if (Schema::hasColumn('orders', 'delivered_at')) {
                    $order['delivered_at'] = $now;
                }

                $orderId = DB::table('orders')->insertGetId($order);

                $item = [
                    'product_id' => $product->id,
                    'order_id' => $orderId,
                    'price' => $unitPrice,
                    'quantity' => $quantity,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn('order_items', 'high_price')) {
                    $item['high_price'] = 0;
                }

                DB::table('order_items')->insert($item);
            }
        });

        $this->command?->info($products->count().' commandes livrées de démonstration ajoutées pour tester l’audit des produits. Les éventuels produits créés portent le préfixe « DEMO AUDIT - ».');
    }
}
