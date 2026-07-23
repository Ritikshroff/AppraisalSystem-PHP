<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Teams Table (without foreign key to Employee first to avoid circular dependency)
        Schema::create('teams', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->unique();
            $table->string('managerId')->nullable()->unique();
            $table->timestamps();
        });

        // 2. Employees Table
        Schema::create('employees', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employeeCode')->unique();
            $table->string('fullName');
            $table->string('email')->unique();
            $table->string('department');
            $table->string('designation');
            $table->string('role')->default('EMPLOYEE'); // EMPLOYEE, MANAGER, CEO, HR
            $table->string('teamId')->nullable();
            $table->string('managerId')->nullable();
            $table->dateTime('doj')->nullable();
            $table->double('salary')->nullable();
            $table->double('lastHike')->nullable();
            $table->timestamps();

            $table->foreign('teamId')->references('id')->on('teams')->onDelete('set null');
            $table->foreign('managerId')->references('id')->on('employees')->onDelete('set null');

            $table->index('managerId');
            $table->index('teamId');
            $table->index('department');
        });

        // Add foreign key constraint to teams.managerId now that employees table exists
        Schema::table('teams', function (Blueprint $table) {
            $table->foreign('managerId')->references('id')->on('employees')->onDelete('set null');
        });

        // 3. Users Table (inherits password reset etc. from default Laravel structure but matches Prisma)
        Schema::create('users', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('email')->unique();
            $table->string('passwordHash');
            $table->string('name');
            $table->string('role')->default('EMPLOYEE');
            $table->string('teamId')->nullable();
            $table->string('employeeId')->nullable()->unique();
            $table->timestamps();

            $table->foreign('teamId')->references('id')->on('teams')->onDelete('set null');
            $table->foreign('employeeId')->references('id')->on('employees')->onDelete('cascade');

            $table->index('role');
            $table->index('teamId');
        });

        // Laravel standard support tables
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // 4. Appraisal Cycles Table
        Schema::create('appraisal_cycles', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('appraisalType'); // WORK, SALARY
            $table->string('periodLabel');
            $table->dateTime('startDate');
            $table->dateTime('endDate');
            $table->boolean('isActive')->default(false);
            $table->timestamps();

            $table->index(['isActive', 'appraisalType']);
        });

        // 5. Appraisals Table
        Schema::create('appraisals', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employeeId');
            $table->string('teamId');
            $table->string('cycleId');
            $table->string('managerId')->nullable();
            $table->string('ceoId')->nullable();
            $table->string('type'); // WORK, SALARY
            $table->string('appraisalPeriod');
            $table->string('status')->default('DRAFT'); // DRAFT, SUBMITTED, MANAGER_REVIEW, COMPLETED
            $table->text('sectionOneAnswers')->nullable();
            $table->text('managerReview')->nullable();
            $table->text('ceoReview')->nullable();
            $table->double('managerOverallRating')->nullable();
            $table->double('finalRating')->nullable();
            $table->double('hikePercentage')->nullable();
            $table->text('aiPerformanceSummary')->nullable();
            $table->string('sentimentLabel')->nullable(); // POSITIVE, NEUTRAL, MIXED, CONCERNING
            $table->double('sentimentScore')->nullable();
            $table->text('aiStrengths')->nullable();
            $table->text('aiWeaknesses')->nullable();
            $table->text('aiRiskSignals')->nullable();
            $table->dateTime('employeeSubmittedAt')->nullable();
            $table->dateTime('managerSubmittedAt')->nullable();
            $table->dateTime('ceoSubmittedAt')->nullable();
            $table->dateTime('analyzedAt')->nullable();
            $table->dateTime('deadlineAt')->nullable();
            $table->timestamps();

            $table->unique(['employeeId', 'cycleId']);
            $table->foreign('employeeId')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('teamId')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('cycleId')->references('id')->on('appraisal_cycles')->onDelete('cascade');
            $table->foreign('managerId')->references('id')->on('employees')->onDelete('set null');
            $table->foreign('ceoId')->references('id')->on('employees')->onDelete('set null');

            $table->index(['cycleId', 'status']);
            $table->index(['teamId', 'status']);
            $table->index(['employeeId', 'status']);
            $table->index(['managerId', 'status']);
        });

        // 6. KRAs Table
        Schema::create('kras', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('appraisalId');
            $table->text('objective');
            $table->double('weightage');
            $table->double('appraiseeRating')->nullable();
            $table->double('appraiserRating')->nullable();
            $table->text('comments')->nullable();
            $table->integer('displayOrder')->default(0);
            $table->timestamps();

            $table->foreign('appraisalId')->references('id')->on('appraisals')->onDelete('cascade');
            $table->index(['appraisalId', 'displayOrder']);
        });

        // 7. Skill Ratings Table
        Schema::create('skill_ratings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('appraisalId');
            $table->string('skillName');
            $table->integer('employeeRating')->nullable();
            $table->integer('managerRating')->nullable();
            $table->integer('displayOrder')->default(0);
            $table->timestamps();

            $table->foreign('appraisalId')->references('id')->on('appraisals')->onDelete('cascade');
            $table->index(['appraisalId', 'displayOrder']);
        });

        // 8. System Settings Table
        Schema::create('system_settings', function (Blueprint $table) {
            $table->string('id')->primary()->default('GLOBAL');
            $table->dateTime('globalDeadlineStart');
            $table->dateTime('globalDeadlineEnd');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('skill_ratings');
        Schema::dropIfExists('kras');
        Schema::dropIfExists('appraisals');
        Schema::dropIfExists('appraisal_cycles');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        
        // Remove foreign key before dropping tables
        if (Schema::hasTable('teams')) {
            Schema::table('teams', function (Blueprint $table) {
                $table->dropForeign(['managerId']);
            });
        }
        
        Schema::dropIfExists('employees');
        Schema::dropIfExists('teams');
    }
};
