<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_supplier_invoices', function (Blueprint $table): void {
            $table->string('supplier_invoice_number', 120)->nullable()->after('invoice_number');
        });

        DB::table('finance_supplier_invoices')->orderBy('id')->chunkById(200, function ($invoices): void {
            foreach ($invoices as $invoice) {
                DB::table('finance_supplier_invoices')->where('id', $invoice->id)->update([
                    'supplier_invoice_number' => $invoice->invoice_number,
                    'invoice_number' => sprintf('TEN-FAC-%s-%06d', date('Y', strtotime($invoice->invoice_date)), $invoice->id),
                ]);
            }
        });

        Schema::table('finance_supplier_invoices', function (Blueprint $table): void {
            $table->unique(['supplier_name', 'supplier_invoice_number'], 'fin_supplier_original_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::table('finance_supplier_invoices', function (Blueprint $table): void {
            $table->dropUnique('fin_supplier_original_ref_unique');
        });
        DB::table('finance_supplier_invoices')->whereNotNull('supplier_invoice_number')->orderBy('id')->chunkById(200, function ($invoices): void {
            foreach ($invoices as $invoice) {
                DB::table('finance_supplier_invoices')->where('id', $invoice->id)->update([
                    'invoice_number' => $invoice->supplier_invoice_number,
                ]);
            }
        });
        Schema::table('finance_supplier_invoices', function (Blueprint $table): void {
            $table->dropColumn('supplier_invoice_number');
        });
    }
};
