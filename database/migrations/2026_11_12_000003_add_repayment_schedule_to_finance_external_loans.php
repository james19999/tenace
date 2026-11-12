<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_external_loans', function (Blueprint $table): void {
            $table->string('repayment_frequency', 16)->nullable()->after('received_on');
            $table->date('first_payment_on')->nullable()->after('repayment_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('finance_external_loans', function (Blueprint $table): void {
            $table->dropColumn(['repayment_frequency', 'first_payment_on']);
        });
    }
};
