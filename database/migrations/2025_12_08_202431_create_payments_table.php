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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('folio')->unique();
            $table->decimal('amount', 10, 2);
            $table->dateTime('payment_date');     // when the payment was made
            $table->foreignId('payment_method_id')
                ->nullable()
                ->constrained('payment_methods')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('reference')->nullable();
            $table->enum('status', ['pending', 'approved', 'cancelled'])->default('approved');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->dateTime('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Useful indexes
            $table->index(['student_id', 'status']);
            $table->index(['charge_id', 'status']);
            $table->index(['payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
