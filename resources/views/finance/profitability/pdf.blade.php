<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><title>Rentabilité TENACOS</title>
<style>body{font-family:DejaVu Sans,sans-serif;color:#293342;font-size:11px}h1{margin:0 0 5px;color:#7d1c1c;font-size:23px}.period{margin:0 0 20px;color:#758092}.kpis{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:18px}.kpi{padding:10px;border:1px solid #e4e7eb}.kpi small{display:block;color:#758092;font-size:9px}.kpi strong{display:block;margin-top:5px;font-size:14px}table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #e4e7eb;text-align:left}th{background:#f3f5f7;color:#657184}.value{text-align:right}.note{margin-top:16px;color:#758092;font-size:9px}</style></head>
<body>
<h1>Rentabilité &amp; performance</h1>
<p class="period">TENACOS · Période du {{ $filters['from']->format('d/m/Y') }} au {{ $filters['to']->format('d/m/Y') }} · FCFA</p>
<div class="kpis">
@foreach([['Chiffre d’affaires net','net_sales'],['Encaissements clients','cash_receipts'],['Créances clients','receivables'],['Coût des marchandises vendues','cost_of_goods_sold'],['Marge brute','gross_margin'],['Charges d’exploitation','operating_expenses'],['Résultat avant impôts','pre_tax_result'],['Résultat net','net_result'],['Taux marge brute','gross_margin_rate'],['Taux rentabilité nette','net_margin_rate'],['Valeur stock final','closing_stock'],['Trésorerie disponible','cash_available']] as [$label,$key])
<div class="kpi"><small>{{ $label }}</small><strong>{{ number_format((float)($metrics[$key]??0),2,',',' ') }}{{ str_contains($key,'rate')?' %':' F CFA' }}</strong></div>
@endforeach
</div>
<table><thead><tr><th>Indicateur</th><th class="value">Montant</th></tr></thead><tbody>@foreach($rows as [$label,$amount])<tr><td>{{ $label }}</td><td class="value">{{ number_format((float)$amount,2,',',' ') }}{{ str_contains($label,'Taux')?' %':' F CFA' }}</td></tr>@endforeach</tbody></table>
<p class="note">Le stock est valorisé avec le coût d’achat enregistré sur les produits. Les frais d’approvisionnement, remises, autres produits et impôts saisis manuellement sont détaillés dans le logiciel et audités.</p>
</body></html>
