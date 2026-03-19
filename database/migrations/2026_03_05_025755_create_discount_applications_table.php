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
        Schema::create('discount_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_order_id')->constrained('payment_orders')->onDelete('cascade');
            $table->foreignId('discount_id')->constrained('discounts');
            $table->decimal('discount_amount', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->enum('applied_by_type', ['automatic', 'manual'])->default('manual');
            $table->foreignId('applied_by')->nullable()->constrained('users');
            $table->datetime('applied_at');
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_applications');
    }
};
