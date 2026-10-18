<?php

namespace App\Services;

use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StockLocationApprovalService
{
    public function canApprove(User $user, StockLocation $location, ?int $initiatorId): bool
    {
        if ($initiatorId !== null && (int) $initiatorId === (int) $user->id) {
            return false;
        }

        $responsibleId = $this->activeResponsibleId($location);
        if ($responsibleId && $responsibleId !== (int) $initiatorId) {
            return $responsibleId === (int) $user->id;
        }

        return $user->user_type === 'ADMINUSER';
    }

    public function assertCanApprove(User $user, StockLocation $location, ?int $initiatorId): void
    {
        if ($this->canApprove($user, $location, $initiatorId)) {
            return;
        }

        $responsibleId = $this->activeResponsibleId($location);
        if ($responsibleId && $responsibleId !== (int) $initiatorId) {
            throw ValidationException::withMessages([
                'approval' => 'Seul le responsable désigné de cet emplacement peut valider cette opération.',
            ]);
        }

        if ($responsibleId && $responsibleId === (int) $initiatorId) {
            throw ValidationException::withMessages([
                'approval' => 'Le responsable a saisi cette opération. Un Admin différent doit la valider.',
            ]);
        }

        throw ValidationException::withMessages([
            'approval' => 'Aucun responsable n’étant désigné, seul un Admin différent de la personne ayant saisi l’opération peut la valider.',
        ]);
    }

    public function activeResponsibleId(StockLocation $location): ?int
    {
        $responsibleId = (int) ($location->responsible_user_id ?? 0);
        if ($responsibleId === 0) {
            return null;
        }

        return User::query()
            ->whereKey($responsibleId)
            ->where('active', true)
            ->whereIn('user_type', ['ADMINUSER', 'MNG'])
            ->exists() ? $responsibleId : null;
    }

    public function waitingMessage(StockLocation $location, ?int $initiatorId): string
    {
        $responsibleId = $this->activeResponsibleId($location);

        if ($responsibleId && $responsibleId !== (int) $initiatorId) {
            $name = User::query()->whereKey($responsibleId)->value('name');

            return 'Seul le responsable désigné'.($name ? ' ('.$name.')' : '').' peut valider cette opération.';
        }

        if ($responsibleId) {
            return 'Le responsable a saisi cette opération. Un Admin différent doit la valider.';
        }

        return 'Aucun responsable actif n’est désigné : seul un Admin différent de la personne ayant saisi l’opération peut la valider.';
    }
}
