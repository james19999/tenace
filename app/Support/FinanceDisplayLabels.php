<?php

namespace App\Support;

class FinanceDisplayLabels
{
    public static function custody(?string $value): string
    {
        return match ($value) {
            'external' => 'Hors de la caisse',
            'shop' => 'Espèces de la boutique',
            'general_cash' => 'Caisse générale',
            'transit' => 'Fonds en transit',
            'bank' => 'Banque',
            'mobile_money' => 'Portefeuille mobile',
            null, '' => '—',
            default => self::humanize($value),
        };
    }

    public static function movement(?string $value): string
    {
        return match ($value) {
            'opening_balance' => 'Fonds initiaux',
            'sale_cash_received' => 'Vente encaissée',
            'expense_payment' => 'Dépense payée',
            'staff_loan_disbursed' => 'Prêt au personnel accordé',
            'tontine_contribution' => 'Cotisation tontine',
            'tontine_recovery' => 'Somme de tontine récupérée',
            'salary_payment' => 'Salaire versé',
            'salary_advance' => 'Avance sur salaire',
            'supplier_payment' => 'Paiement fournisseur',
            'staff_advance_paid' => 'Avance au personnel versée',
            'staff_advance_repaid' => 'Remboursement d’avance reçu',
            'staff_loan_salary_deduction' => 'Retenue mensuelle de prêt sur salaire',
            'staff_loan_repayment_plan_updated' => 'Plan de remboursement du prêt modifié',
            'commission_paid' => 'Commission versée',
            'cash_refund' => 'Remboursement client',
            'remittance_handover' => 'Remise prise en charge',
            'central_cash_received' => 'Fonds reçus par le DG',
            'bank_deposit_confirmed' => 'Dépôt bancaire confirmé',
            'cash_count_overage' => 'Excédent de caisse déclaré',
            'cash_shortage_approved' => 'Manque de caisse validé',
            'transit_shortage_approved' => 'Écart de transit validé',
            null, '' => 'Opération',
            default => self::humanize($value),
        };
    }

    public static function status(?string $value): string
    {
        return match ($value) {
            'draft' => 'Brouillon',
            'submitted' => 'Transmise',
            'awaiting_collector' => 'En attente de collecte',
            'awaiting_review', 'awaiting_finance' => 'À contrôler',
            'awaiting_director' => 'En attente de validation du DG',
            'authorized_to_pay' => 'Autorisée à payer',
            'partially_paid' => 'Partiellement payée',
            'in_transit' => 'En transit',
            'discrepancy_open' => 'Écart à traiter',
            'received' => 'Réception confirmée',
            'approved' => 'Validée',
            'closed' => 'Clôturée',
            'posted' => 'Enregistrée',
            'rejected' => 'Rejetée',
            'paid' => 'Payée',
            'partial' => 'Partiellement réglée',
            'unpaid' => 'Non réglée',
            'cancelled', 'canceled' => 'Annulée',
            'reopened' => 'Rouverte',
            'collected' => 'Collectée',
            'resolved' => 'Résolue',
            null, '' => 'Non renseigné',
            default => self::humanize($value),
        };
    }

    public static function stockReceiptStatus(?string $value): string
    {
        return match ($value) {
            'validated' => 'Réception complète validée',
            'partial' => 'Réception partielle',
            default => self::status($value),
        };
    }

    public static function action(?string $value): string
    {
        return match ($value) {
            'prepared_and_shop_signed' => 'Bon préparé et signé par la boutique',
            'collector_signed_handover' => 'Collecte signée et prise en charge',
            'receipt_confirmed' => 'Réception confirmée',
            'discrepancy_resolved' => 'Écart résolu',
            'supplier_invoice_registered' => 'Facture fournisseur enregistrée',
            'supplier_invoice_approved' => 'Facture fournisseur validée',
            'supplier_invoice_rejected' => 'Facture fournisseur rejetée',
            'supplier_invoice_payment_recorded' => 'Règlement fournisseur enregistré',
            'delivery_fee_excluded_from_shop_cash', 'Delivery fee excluded from shop cash' => 'Frais de livraison exclus de la caisse de la boutique',
            'submitted' => 'Demande de dépense transmise',
            'finance_review_approved' => 'Contrôle financier validé',
            'finance_review_rejected' => 'Contrôle financier refusé',
            'director_approved' => 'Dépense autorisée par le DG',
            'director_authorized_at_creation' => 'Avance autorisée directement par le DG',
            'director_rejected' => 'Dépense refusée par le DG',
            'payment_recorded' => 'Paiement enregistré',
            'cash_count_submitted' => 'Comptage de caisse transmis',
            'cash_count_resubmitted_after_reopening' => 'Nouveau comptage transmis après réouverture',
            'cash_closure_approved' => 'Clôture de caisse validée',
            'cash_closure_reopened' => 'Clôture de caisse rouverte',
            'budget_created' => 'Budget enregistré',
            'staff_advance_disbursed' => 'Avance au personnel versée',
            'staff_advance_repayment' => 'Remboursement d’avance reçu',
            'commission_settled' => 'Commission réglée',
            'asset_registered' => 'Immobilisation enregistrée',
            'employee_salary_updated' => 'Salaire convenu modifié',
            'salary_period_created' => 'Mois de salaire ajouté au suivi',
            'tontine_created' => 'Tontine créée',
            'tontine_contribution_paid' => 'Cotisation de tontine payée',
            'tontine_recovery_received' => 'Somme de tontine récupérée',
            'salary_advance_paid' => 'Avance sur salaire versée',
            'salary_payment_paid' => 'Salaire versé',
            'staff_advance_repaid' => 'Remboursement d’avance reçu',
            null, '' => 'Action',
            default => self::humanize($value),
        };
    }

    public static function reportType(?string $value): string
    {
        return match ($value) {
            'cash' => 'Mouvements de caisse',
            'remittances' => 'Remises boutiques',
            'expenses' => 'Dépenses',
            'suppliers' => 'Factures fournisseurs',
            null, '' => 'Rapport',
            default => self::humanize($value),
        };
    }

    public static function subjectType(?string $value): string
    {
        $shortName = $value ? class_basename($value) : '';

        return match ($shortName) {
            'FinanceCashRemittance' => 'Remise boutique',
            'FinanceExpenseRequest' => 'Demande de dépense',
            'FinanceCashMovement' => 'Mouvement de caisse',
            'FinanceCashClosure' => 'Clôture de caisse',
            'FinanceSupplierInvoice' => 'Facture fournisseur',
            'EmployeeProfile' => 'Dossier employé',
            'EmployeePayrollPeriod' => 'Mois de salaire',
            'EmployeePayrollPayment' => 'Versement de salaire',
            'FinanceTontine' => 'Tontine',
            'FinanceTontineMovement' => 'Mouvement de tontine',
            default => self::humanize($shortName),
        };
    }

    public static function auditValues(?array $values): array
    {
        if (! $values) {
            return [];
        }

        $labels = [
            'reference' => 'Référence du dossier',
            'supplier_name' => 'Fournisseur',
            'invoice_number' => 'Référence de la facture TENACOS',
            'supplier_invoice_number' => 'Numéro de facture fournisseur',
            'invoice_date' => 'Date de la facture',
            'due_date' => 'Date limite de paiement',
            'amount' => 'Montant',
            'paid_amount' => 'Montant déjà réglé',
            'status' => 'État du dossier',
            'payment_status' => 'État du règlement',
            'receipt_number' => 'Réception de stock associée',
            'description' => 'Motif',
            'beneficiary' => 'Bénéficiaire',
            'category_name' => 'Catégorie',
            'budget_reference' => 'Budget concerné',
            'requested_payment_date' => 'Date de paiement souhaitée',
            'period_start' => 'Début de la période',
            'period_end' => 'Fin de la période',
            'expected_amount' => 'Montant attendu',
            'counted_amount' => 'Montant compté',
            'handed_over_amount' => 'Montant remis au collecteur',
            'received_amount' => 'Montant reçu par le DG',
            'first_contribution_on' => 'Première cotisation prévue',
            'next_contribution_on' => 'Prochaine cotisation prévue',
            'expected_recovery_on' => 'Date prévue de récupération',
            'name' => 'Nom de la tontine',
            'cash_custody' => 'Caisse utilisée',
            'assigned_users' => 'Personnes chargées',
            'type' => 'Opération',
            'due_on' => 'Échéance de cotisation',
            'proof_attached' => 'Preuve jointe',
            'recorded_by' => 'Enregistré par',
            'theoretical_amount' => 'Solde théorique',
            'variance_amount' => 'Écart constaté',
            'destination' => 'Destination des fonds',
            'bank_slip_reference' => 'Référence du bordereau bancaire',
            'paper_voucher_reference' => 'Référence du bon signé',
            'count_note' => 'Note sur le comptage',
            'resolution_note' => 'Explication de la régularisation',
            'discrepancy_note' => 'Explication de l’écart',
            'justification_note' => 'Note justificative',
            'review_comment' => 'Commentaire du contrôle',
            'finance_comment' => 'Commentaire du contrôle financier',
            'director_comment' => 'Commentaire de la Direction',
            'amount_paid_now' => 'Montant versé',
            'custody' => 'Compte utilisé',
            'denominations' => 'Détail des coupures comptées',
        ];
        $moneyFields = [
            'amount', 'paid_amount', 'expected_amount', 'counted_amount', 'handed_over_amount',
            'received_amount', 'theoretical_amount', 'variance_amount', 'amount_paid_now',
        ];
        $dateFields = ['invoice_date', 'due_date', 'requested_payment_date', 'period_start', 'period_end', 'closing_date'];
        $labels['closing_date'] = 'Date de clôture';

        $display = [];
        foreach ($values as $key => $value) {
            if (! isset($labels[$key]) || $value === null || $value === '') {
                continue;
            }

            if (in_array($key, $moneyFields, true) && is_numeric($value)) {
                $value = number_format((float) $value, 0, ',', ' ').' F CFA';
            } elseif (in_array($key, $dateFields, true)) {
                try {
                    $value = \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
                } catch (\Throwable) {
                    // Keep the saved value readable if it is not a date.
                }
            } elseif ($key === 'status' || $key === 'payment_status') {
                $value = self::status((string) $value);
            } elseif ($key === 'custody') {
                $value = self::custody((string) $value);
            } elseif ($key === 'destination') {
                $value = match ($value) {
                    'bank_deposit' => 'Dépôt bancaire pour TENACOS',
                    'general_cash' => 'Caisse générale',
                    default => self::humanize((string) $value),
                };
            } elseif (is_array($value)) {
                if ($key !== 'denominations') {
                    continue;
                }
                $value = collect($value)->map(fn ($count, $denomination) => $denomination.' F × '.$count)->implode(' · ');
            }

            $display[] = ['label' => $labels[$key], 'value' => (string) $value];
        }

        return $display;
    }

    private static function humanize(string $value): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $value));
    }
}
