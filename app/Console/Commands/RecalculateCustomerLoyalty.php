<?php

namespace App\Console\Commands;

use App\Models\Costumer;
use App\Services\CustomerLoyaltyService;
use Illuminate\Console\Command;

class RecalculateCustomerLoyalty extends Command
{
    protected $signature = 'customer-loyalty:recalculate';
    protected $description = 'Recalcule les scores, catégories et historiques de fidélité par lots.';

    public function handle(CustomerLoyaltyService $loyalty): int
    {
        $count = 0;
        Costumer::query()->select(['id', 'created_at'])->chunkById(250, function ($customers) use ($loyalty, &$count) {
            $loyalty->refreshCustomers($customers);
            $count += $customers->count();
            $this->line($count.' clientes recalculées.');
        });

        $this->info('Recalcul terminé : '.$count.' clientes.');
        return self::SUCCESS;
    }
}
