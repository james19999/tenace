<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceCashMovement;
use App\Models\FinanceCashRemittance;
use App\Models\FinanceExpenseRequest;
use App\Models\FinanceReportExport;
use App\Models\FinanceSupplierInvoice;
use App\Models\StockLocation;
use App\Models\User;
use App\Support\FinanceDisplayLabels;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isAccountant() || $user->hasRole(User::ROLE_CASHIER), 403);
        $allRows = $this->rows($filters, $user);

        return view('finance.reports.index', [
            'filters' => $filters,
            'rows' => $this->query($filters, $user)->paginate(30)->withQueryString(),
            'total' => $this->total($filters, $user),
            'overviewTotals' => $this->overviewTotals($filters, $user),
            'locationTotals' => $this->locationTotals($filters, $allRows),
            'locations' => StockLocation::query()->where('type', 'shop')->orderBy('name')->get(['id', 'name']),
            'recentExports' => FinanceReportExport::query()->with('exporter')
                ->when(! $user->isAdmin() && ! $user->isAccountant(), fn ($query) => $query->where('exported_by', $user->id))
                ->latest('exported_at')->limit(20)->get(),
        ]);
    }

    public function export(Request $request): Response|StreamedResponse
    {
        $request->validate(['format' => ['nullable', Rule::in(['csv', 'xlsx', 'pdf'])]]);
        $format = $request->input('format', 'csv');
        $filters = $this->filters($request);
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isAccountant() || $user->hasRole(User::ROLE_CASHIER), 403);
        $rows = $this->rows($filters, $user);
        $filters['location_name'] = $filters['stock_location_id']
            ? StockLocation::query()->whereKey($filters['stock_location_id'])->value('name')
            : null;
        $exportLog = FinanceReportExport::query()->create([
            'reference' => 'TMP-'.Str::uuid(),
            'report_type' => $filters['type'],
            'filters' => ['from' => $filters['from'], 'to' => $filters['to'], 'stock_location_id' => $filters['stock_location_id'], 'location_name' => $filters['location_name']],
            'row_count' => $rows->count(),
            'amount_total' => $this->total($filters, $user),
            'exported_by' => $user->id,
            'exported_at' => now(),
        ]);
        $exportLog->update(['reference' => sprintf('RPT-%s-%08d', now()->format('Y'), $exportLog->id)]);
        $headers = match ($filters['type']) {
            'remittances' => ['Référence', 'Boutique', 'Période', 'Responsable', 'Collecteur', 'Déclaré', 'Reçu caisse générale', 'Statut', 'Reçu le'],
            'expenses' => ['Référence', 'Date demande', 'Catégorie', 'Description', 'Bénéficiaire', 'Montant autorisé', 'Payé', 'Statut', 'Point de vente'],
            'suppliers' => ['Fournisseur', 'Référence TENACOS', 'N° facture fournisseur', 'Date facture', 'Échéance', 'Montant', 'Payé', 'Reste dû', 'Statut', 'Boutique de réception', 'Réception stock'],
            default => ['N° écriture', 'Date', 'Opération', 'Référence source', 'Point de vente', 'Origine', 'Destination', 'Montant', 'Description'],
        };
        $baseName = 'tenacos-'.str_replace('_', '-', $filters['type']).'-'.$filters['from'].'-'.$filters['to'];
        $reportTitle = FinanceDisplayLabels::reportType($filters['type']);

        if ($format === 'pdf') {
            return Pdf::loadView('finance.reports.pdf', [
                'title' => $reportTitle,
                'filters' => $filters,
                'headers' => $headers,
                'rows' => $rows->map(fn ($row) => $this->csvRow($filters['type'], $row)),
                'total' => $this->total($filters, $user),
                'overviewTotals' => $this->overviewTotals($filters, $user),
                'locationTotals' => $this->locationTotals($filters, $rows),
                'generatedAt' => now(),
            ])->setPaper('a4', 'landscape')->download($baseName.'.pdf');
        }

        if ($format === 'xlsx') {
            return $this->downloadExcel($baseName.'.xlsx', $reportTitle, $filters, $headers, $rows);
        }

        return response()->streamDownload(function () use ($headers, $rows, $filters, $reportTitle): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['TENACOS · '.$reportTitle, 'Période '.$filters['from'].' au '.$filters['to'], 'Généré le '.now()->format('d/m/Y H:i')], ';');
            fputcsv($output, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($output, array_map($this->safeCsvCell(...), $this->csvRow($filters['type'], $row)), ';');
            }
            fclose($output);
        }, $baseName.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function downloadExcel(string $filename, string $title, array $filters, array $headers, $rows): Response
    {
        abort_unless(class_exists(\ZipArchive::class), 500, 'L’extension PHP ZipArchive est requise pour créer le fichier Excel.');
        $path = tempnam(sys_get_temp_dir(), 'tenacos-xlsx-');
        if ($path === false) {
            abort(500, 'Impossible de préparer le fichier Excel.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path, \ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            abort(500, 'Impossible de créer le fichier Excel.');
        }

        $tableRows = [
            ['TENACOS · '.$title],
            ['Période', $filters['from'].' au '.$filters['to']],
            ['Généré le', now()->format('d/m/Y H:i')],
            [],
            $headers,
        ];
        foreach ($rows as $row) {
            $tableRows[] = array_map($this->safeCsvCell(...), $this->csvRow($filters['type'], $row));
        }
        $lastColumn = $this->excelColumn(max(1, count($headers)));
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'.$lastColumn.count($tableRows).'"/><sheetData>';
        foreach ($tableRows as $rowIndex => $values) {
            $excelRow = $rowIndex + 1;
            $sheet .= '<row r="'.$excelRow.'">';
            foreach (array_values($values) as $columnIndex => $value) {
                $cell = $this->excelColumn($columnIndex + 1).$excelRow;
                if (is_int($value) || (is_float($value) && is_finite($value))) {
                    $sheet .= '<c r="'.$cell.'"><v>'.$value.'</v></c>';
                    continue;
                }

                $text = htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $sheet .= '<c r="'.$cell.'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData></worksheet>';

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Rapport" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function excelColumn(int $number): string
    {
        $column = '';
        while ($number > 0) {
            $number--;
            $column = chr(65 + ($number % 26)).$column;
            $number = intdiv($number, 26);
        }

        return $column;
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'type' => ['nullable', Rule::in(['cash', 'remittances', 'expenses', 'suppliers'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'stock_location_id' => ['nullable', 'integer', Rule::exists('stock_locations', 'id')->where(fn ($query) => $query->where('type', 'shop'))],
        ]);
        $from = Carbon::parse($data['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay();

        return [
            'type' => $data['type'] ?? 'cash',
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'from_at' => $from,
            'to_at' => $to,
            'stock_location_id' => $data['stock_location_id'] ?? null,
            'location_name' => ! empty($data['stock_location_id'])
                ? StockLocation::query()->whereKey($data['stock_location_id'])->value('name')
                : null,
        ];
    }

    private function rows(array $filters, User $user)
    {
        return $this->query($filters, $user)->get();
    }

    private function query(array $filters, User $user)
    {
        $query = match ($filters['type']) {
            'remittances' => FinanceCashRemittance::query()->with(['location', 'shopManager', 'collector', 'receiver'])
                ->whereBetween('created_at', [$filters['from_at'], $filters['to_at']]),
            'expenses' => FinanceExpenseRequest::query()->with(['location', 'requester'])
                ->whereBetween('created_at', [$filters['from_at'], $filters['to_at']]),
            'suppliers' => FinanceSupplierInvoice::query()->with(['receipt.location'])
                ->whereBetween('invoice_date', [$filters['from'], $filters['to']]),
            default => FinanceCashMovement::query()->with('location')
                ->whereBetween('occurred_at', [$filters['from_at'], $filters['to_at']]),
        };
        if ($filters['stock_location_id']) {
            if ($filters['type'] === 'suppliers') {
                $query->whereHas('receipt', fn ($receipt) => $receipt->where('location_id', $filters['stock_location_id']));
            } else {
                $query->where('stock_location_id', $filters['stock_location_id']);
            }
        }
        if (! $user->isAdmin() && ! $user->isAccountant()) {
            if ($user->hasRole(User::ROLE_CASHIER)) {
                if ($filters['type'] === 'cash') {
                    $query->where(fn ($scope) => $scope->where('from_custody', 'general_cash')->orWhere('to_custody', 'general_cash'));
                } elseif ($filters['type'] === 'remittances') {
                    $query->whereIn('status', ['received', 'discrepancy_open', 'closed']);
                } else {
                    $query->whereRaw('1 = 0');
                }
            } else {
                $ids = StockLocation::query()->where('responsible_user_id', $user->id)->pluck('id');
                if (in_array($filters['type'], ['cash', 'remittances', 'expenses'], true)) {
                    $query->whereIn('stock_location_id', $ids);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        return $query->latest($filters['type'] === 'suppliers' ? 'invoice_date' : ($filters['type'] === 'cash' ? 'occurred_at' : 'created_at'));
    }

    private function total(array $filters, User $user): int
    {
        return (int) $this->query($filters, $user)->sum(match ($filters['type']) {
            'remittances' => 'received_amount',
            'expenses' => 'paid_amount',
            'suppliers' => 'amount',
            default => 'amount',
        });
    }

    private function locationTotals(array $filters, Collection $rows): Collection
    {
        return $rows->groupBy(function ($row) use ($filters): string {
            $location = $filters['type'] === 'suppliers' ? $row->receipt?->location : $row->location;

            return $location?->name ?? 'Non rattaché à une boutique';
        })->map(function (Collection $group, string $name) use ($filters): array {
            return [
                'name' => $name,
                'count' => $group->count(),
                'total' => (int) $group->sum(match ($filters['type']) {
                    'remittances' => 'received_amount',
                    'expenses' => 'paid_amount',
                    default => 'amount',
                }),
                'requested' => $filters['type'] === 'expenses' ? (int) $group->sum('amount') : null,
            ];
        })->sortBy('name')->values();
    }

    private function overviewTotals(array $filters, User $user): array
    {
        $totals = [];
        foreach (['cash', 'remittances', 'expenses', 'suppliers'] as $type) {
            $scope = [...$filters, 'type' => $type];
            $query = $this->query($scope, $user);
            $totals[$type] = match ($type) {
                'expenses' => [
                    'amount' => (int) (clone $query)->sum('amount'),
                    'paid' => (int) (clone $query)->sum('paid_amount'),
                ],
                'suppliers' => [
                    'amount' => (int) (clone $query)->sum('amount'),
                    'paid' => (int) (clone $query)->sum('paid_amount'),
                ],
                'remittances' => ['amount' => (int) (clone $query)->sum('received_amount')],
                default => ['amount' => (int) (clone $query)->sum('amount')],
            };
        }

        return $totals;
    }

    private function csvRow(string $type, $row): array
    {
        return match ($type) {
            'remittances' => [$row->reference, $row->location?->name, $row->period_start?->format('d/m/Y').' - '.$row->period_end?->format('d/m/Y'), $row->shopManager?->name, $row->collector?->name, $row->handed_over_amount, $row->received_amount, FinanceDisplayLabels::status($row->status), $row->received_at?->format('d/m/Y H:i')],
            'expenses' => [$row->reference, $row->created_at?->format('d/m/Y H:i'), $row->category_name, $row->description, $row->beneficiary, $row->amount, $row->paid_amount, FinanceDisplayLabels::status($row->status), $row->location?->name],
            'suppliers' => [$row->supplier_name, $row->invoice_number, $row->supplier_invoice_number, $row->invoice_date?->format('d/m/Y'), $row->due_date?->format('d/m/Y'), $row->amount, $row->paid_amount, max(0, $row->amount - $row->paid_amount), FinanceDisplayLabels::status($row->status).' / '.FinanceDisplayLabels::status($row->payment_status), $row->receipt?->location?->name, $row->receipt?->receipt_number],
            default => [$row->entry_number, $row->occurred_at?->format('d/m/Y H:i'), FinanceDisplayLabels::movement($row->movement_type), $row->source_reference, $row->location?->name, FinanceDisplayLabels::custody($row->from_custody), FinanceDisplayLabels::custody($row->to_custody), $row->amount, $row->description],
        };
    }

    private function safeCsvCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }
        $trimmed = ltrim($value);

        return preg_match('/^[=+@\-]/', $trimmed) ? "'".$value : $value;
    }
}
