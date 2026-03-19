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
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();

            $table->enum('value_type', ['percentage', 'fixed']);
            $table->decimal('value', 8, 2);                // 10 = 10% o $10

            $table->string('applies_to_type')->nullable(); // null=todos | 'mensualidad' | etc.

            $table->enum('condition_type', [
                'manual', 'referral', 'scholarship', 'early_payment', 'promo'
            ]);
            $table->boolean('is_automatic')->default(false);   // aplica solo sin intervención
            $table->boolean('is_stackable')->default(false);   // combinable con otros
            $table->boolean('is_recurring')->default(false);   // aplica cada mes (referido)

            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedInteger('max_uses')->nullable();   // null = ilimitado
            $table->unsignedInteger('used_count')->default(0);

            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
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
