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
        Schema::create('charge_discounts', function (Blueprint $table) {
            $table->id();
            $table->id();

            // Charge receiving the discount
            $table->foreignId('charge_id')
                ->constrained('charges')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Discount applied
            $table->foreignId('discount_id')
                ->constrained('discounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->text('reason')->nullable();


            $table->foreignId('authorized_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamps();


            $table->index(['charge_id']);
            $table->index(['discount_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charge_discounts');
    }
};
