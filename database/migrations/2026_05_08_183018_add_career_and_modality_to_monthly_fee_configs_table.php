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
        Schema::table('monthly_fee_configs', function (Blueprint $table) {
            $table->foreignId('career_id')->nullable()->after('generation_id')->constrained('careers')->nullOnDelete();
            $table->foreignId('modality_id')->nullable()->after('career_id')->constrained('modalities')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_fee_configs', function (Blueprint $table) {
            $table->dropForeign(['career_id']);
            $table->dropForeign(['modality_id']);
            $table->dropColumn(['career_id', 'modality_id']);
        });
    }
};
