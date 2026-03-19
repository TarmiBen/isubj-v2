<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // monthly_fees actúa como el "chargeable" polimórfico de payment_orders
        Schema::create('monthly_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('monthly_fee_config_id')->constrained('monthly_fee_configs');
            $table->foreignId('inscription_id')->nullable()->constrained('inscriptions');

            // Período que cubre
            $table->unsignedTinyInteger('month');          // 1-12
            $table->unsignedSmallInteger('year');
            $table->date('period_start');
            $table->date('period_end');

            // payment_order_id se agrega via alter después de crear payment_orders
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'month', 'year', 'monthly_fee_config_id']);
            $table->index(['student_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_fees');
    }
};