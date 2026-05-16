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
        Schema::table('discounts', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('applies_to_type')->constrained('students')->nullOnDelete();
            $table->boolean('is_single_use')->default(false)->after('is_recurring');
            $table->timestamp('used_at')->nullable()->after('used_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'is_single_use', 'used_at']);
        });
    }
};

