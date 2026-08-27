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
        Schema::create('next_cycle_kras', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('appraisalId');
            $table->text('objective');
            $table->double('weightage');
            $table->integer('displayOrder')->default(0);
            $table->timestamps();

            $table->foreign('appraisalId')->references('id')->on('appraisals')->onDelete('cascade');
            $table->index(['appraisalId', 'displayOrder']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('next_cycle_kras');
    }
};
