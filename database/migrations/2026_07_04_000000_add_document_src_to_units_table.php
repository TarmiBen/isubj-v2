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
        if (!Schema::hasColumn('units', 'document_src')) {
            Schema::table('units', function (Blueprint $table) {
                $table->string('document_src')->nullable()->after('assignment_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('units', 'document_src')) {
            Schema::table('units', function (Blueprint $table) {
                $table->dropColumn('document_src');
            });
        }
    }
};
