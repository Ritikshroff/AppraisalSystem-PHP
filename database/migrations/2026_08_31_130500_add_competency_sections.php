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
        // 1. Create competency_ratings table for Section 4
        Schema::create('competency_ratings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('appraisalId');
            $table->string('competencyName');
            $table->tinyInteger('employeeScore')->nullable()->unsigned();  // 1–10, filled by Employee
            $table->tinyInteger('appraiserScore')->nullable()->unsigned(); // 1–10, filled by Appraiser
            $table->integer('displayOrder')->default(0);
            $table->timestamps();

            $table->foreign('appraisalId')->references('id')->on('appraisals')->onDelete('cascade');
            $table->index(['appraisalId', 'displayOrder']);
        });

        // 2. Add new columns to appraisals table for Sections 5 & 6
        Schema::table('appraisals', function (Blueprint $table) {
            // Section 5 — Appraiser fields (Manager fills these)
            $table->double('appraiserOverallRating')->nullable()->after('managerOverallRating');
            $table->text('appraiserRecommendation')->nullable()->after('appraiserOverallRating');
            $table->text('appraiserNewKraNotes')->nullable()->after('appraiserRecommendation');

            // Section 6 — Reviewer fields (BU Head fills these)
            $table->text('reviewerComments')->nullable()->after('appraiserNewKraNotes');
            $table->double('reviewerRating')->nullable()->after('reviewerComments');

            // Signature timestamps (auto-set on submission)
            $table->dateTime('appraiserSignedAt')->nullable()->after('managerSubmittedAt');
            $table->dateTime('reviewerSignedAt')->nullable()->after('buHeadSubmittedAt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competency_ratings');

        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropColumn([
                'appraiserOverallRating',
                'appraiserRecommendation',
                'appraiserNewKraNotes',
                'reviewerComments',
                'reviewerRating',
                'appraiserSignedAt',
                'reviewerSignedAt',
            ]);
        });
    }
};
