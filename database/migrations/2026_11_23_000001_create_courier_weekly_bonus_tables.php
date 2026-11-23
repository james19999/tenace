<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'courier_departed_at')) {
            Schema::table('orders', fn (Blueprint $table) => $table->timestamp('courier_departed_at')->nullable()->after('assigned_at'));
        }
        Schema::create('courier_bonus_weeks', function (Blueprint $table): void {
            $table->id();
            $table->date('week_start')->unique();
            $table->date('week_end');
            $table->unsignedInteger('budget')->default(15000);
            $table->string('status', 24)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('manager_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_validated_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->json('result_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_bonus_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained('courier_bonus_weeks')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('users');
            $table->date('attendance_date');
            $table->string('status', 24);
            $table->string('reason', 500)->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['week_id', 'courier_id', 'attendance_date'], 'courier_bonus_attendance_unique');
        });

        Schema::create('courier_bonus_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained('courier_bonus_weeks')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('users');
            foreach (['politeness', 'presentation', 'instructions', 'money_handling', 'discipline'] as $criterion) {
                $table->unsignedTinyInteger($criterion)->default(0);
            }
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['week_id', 'courier_id'], 'courier_bonus_rating_unique');
        });

        Schema::create('courier_bonus_late_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained('courier_bonus_weeks')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->text('reason');
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique(['week_id', 'order_id'], 'courier_bonus_late_exception_unique');
        });

        Schema::create('courier_bonus_awards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained('courier_bonus_weeks')->cascadeOnDelete();
            $table->foreignId('courier_id')->constrained('users');
            $table->string('courier_name');
            $table->unsignedInteger('productivity_amount')->default(0);
            $table->unsignedInteger('speed_amount')->default(0);
            $table->unsignedInteger('presence_amount')->default(0);
            $table->unsignedInteger('professionalism_amount')->default(0);
            $table->unsignedInteger('total_amount')->default(0);
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->unique(['week_id', 'courier_id'], 'courier_bonus_award_unique');
        });

        Schema::create('courier_bonus_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('week_id')->constrained('courier_bonus_weeks')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);
            $table->text('reason')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_bonus_audits');
        Schema::dropIfExists('courier_bonus_awards');
        Schema::dropIfExists('courier_bonus_late_exceptions');
        Schema::dropIfExists('courier_bonus_ratings');
        Schema::dropIfExists('courier_bonus_attendances');
        Schema::dropIfExists('courier_bonus_weeks');
        if (Schema::hasColumn('orders', 'courier_departed_at')) {
            Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('courier_departed_at'));
        }
    }
};
