<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DeactivateCourierAccounts extends Command
{
    protected $signature = 'couriers:deactivate-for-night {--courier-id= : Limiter le test à un livreur précis}';

    protected $description = 'Désactive les comptes livreur à la fin de la journée.';

    public function handle(): int
    {
        $couriers = User::query()
            ->where('user_type', User::ROLE_LIVREUR)
            ->where('active', true);

        if ($courierId = $this->option('courier-id')) {
            $couriers->whereKey($courierId);
        }

        $deactivated = $couriers->update(['active' => false, 'updated_at' => now()]);

        $this->info($deactivated.' compte(s) livreur désactivé(s).');

        if ($this->option('courier-id') && $deactivated === 0) {
            $this->warn('Aucun livreur actif ne correspond à cet identifiant.');
        }

        return self::SUCCESS;
    }
}
