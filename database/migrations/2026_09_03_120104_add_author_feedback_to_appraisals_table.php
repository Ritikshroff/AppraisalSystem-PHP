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
        Schema::table('appraisals', function (Blueprint $table) {
            $table->tinyInteger('authorFeedbackRating')->nullable()->after('aiRiskSignals'); // 1 to 5 scale
            $table->text('authorFeedbackComments')->nullable()->after('authorFeedbackRating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropColumn(['authorFeedbackRating', 'authorFeedbackComments']);
        });
    }
};
