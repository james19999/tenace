<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_service_cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number', 32)->unique();
            $table->foreignId('costumer_id')->constrained('costumers')->restrictOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('case_type', 40);
            $table->date('purchase_date')->nullable();
            $table->text('description');
            $table->string('priority', 16)->default('normal');
            $table->string('status', 40)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('next_follow_up_at')->nullable();
            $table->text('resolution')->nullable();
            $table->text('resolution_result')->nullable();
            $table->unsignedTinyInteger('customer_satisfaction')->nullable();
            $table->text('satisfaction_comment')->nullable();
            $table->text('closure_reason')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_follow_up_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['priority', 'created_at']);
            $table->index(['costumer_id', 'created_at']);
        });

        Schema::create('customer_service_case_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('customer_service_cases')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('activity_type', 32);
            $table->string('channel', 32)->nullable();
            $table->string('old_status', 40)->nullable();
            $table->string('new_status', 40)->nullable();
            $table->text('body')->nullable();
            $table->boolean('internal')->default(true);
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['case_id', 'occurred_at']);
            $table->index(['activity_type', 'occurred_at']);
        });

        Schema::create('customer_service_case_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('customer_service_cases')->restrictOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('customer_service_case_activities')->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
            $table->index(['case_id', 'created_at']);
        });

        Schema::create('customer_service_support_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->unique()->constrained('customer_service_cases')->cascadeOnDelete();
            $table->text('customer_need');
            $table->text('objectives')->nullable();
            $table->date('started_at');
            $table->text('final_review')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_service_support_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('customer_service_support_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('day_offset')->nullable();
            $table->dateTime('due_at');
            $table->dateTime('completed_at')->nullable();
            $table->text('observation')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['due_at', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_service_support_milestones');
        Schema::dropIfExists('customer_service_support_plans');
        Schema::dropIfExists('customer_service_case_attachments');
        Schema::dropIfExists('customer_service_case_activities');
        Schema::dropIfExists('customer_service_cases');
    }
};
