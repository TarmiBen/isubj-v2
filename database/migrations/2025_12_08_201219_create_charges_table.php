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
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();


            $table->nullableMorphs('chargeable');
            $table->string('description')->nullable();

            $table->decimal('original_amount', 10, 2);  // amount before discounts
            $table->decimal('discount_amount', 10, 2)->default(0); // total discounts applied
            $table->decimal('final_amount', 10, 2);     // original_amount - discount_amount

            // Dates
            $table->dateTime('generated_at')->nullable();       // when the charge was created/issued
            $table->dateTime('due_date')->nullable();           // limit to pay

            $table->enum('status', ['pending', 'paid', 'cancelled', 'partially_paid'])->default('pending');

            // automatic, manual, migration, etc.
            $table->enum('origin', ['automatic', 'manual', 'migration'])->default('manual');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Useful indexes
            $table->index(['student_id', 'status']);
            $table->index(['service_id', 'status']);
            $table->index(['due_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};
