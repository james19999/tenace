<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('job_title', 120)->nullable();
            $table->string('department', 120)->nullable();
            $table->date('hired_on')->nullable();
            $table->string('work_site', 160)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['department', 'job_title'], 'employee_profile_department_title_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
