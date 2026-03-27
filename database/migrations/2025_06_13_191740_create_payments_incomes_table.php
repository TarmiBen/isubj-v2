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
        Schema::disableForeignKeyConstraints();

        Schema::create('payments_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained();
            $table->morphs('paymentable');
            $table->string('payment', 100);
            $table->decimal('amount', 5, 2);
            $table->decimal('discount', 5, 2);
            $table->enum('status', ["paid","pending", "condoned", "partial"])->default('pending');
            $table->json('meta');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments_incomes');
    }
};
