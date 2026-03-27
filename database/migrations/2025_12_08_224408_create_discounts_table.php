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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 150);
            $table->string('type', 30);
            // value = in percentage (0–100) or fixed amount depending on type
            $table->decimal('value', 10, 2);

            $table->string('applies_to', 100)
                ->nullable();  // e.g. tuition, inscription, all, documents, exams

            $table->boolean('active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            // Useful indexes
            $table->index(['active']);
            $table->index(['type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
