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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('designation');
            $table->string('dob')->nullable()->after('doj');
            $table->double('companyExperienceYears')->nullable()->after('doj');
            $table->double('totalExperienceYears')->nullable()->after('companyExperienceYears');
            $table->string('lastPromotionDate')->nullable()->after('lastHike');
        });

        Schema::table('appraisals', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('type');
            $table->text('justification')->nullable()->after('ceoReview');
            $table->boolean('promotionRecommended')->default(false)->after('hikePercentage');
            $table->double('adjustments')->nullable()->after('hikePercentage');
            $table->double('incrementAmount')->nullable()->after('hikePercentage');
            $table->double('newCtc')->nullable()->after('hikePercentage');
            $table->boolean('specialAppeal')->default(false)->after('analyzedAt');
            $table->string('specialAppealStatus')->default('NONE')->after('specialAppeal');
            $table->text('specialAppealComments')->nullable()->after('specialAppealStatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropColumn([
                'grade',
                'justification',
                'promotionRecommended',
                'adjustments',
                'incrementAmount',
                'newCtc',
                'specialAppeal',
                'specialAppealStatus',
                'specialAppealComments',
            ]);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'grade',
                'dob',
                'companyExperienceYears',
                'totalExperienceYears',
                'lastPromotionDate',
            ]);
        });
    }
};
