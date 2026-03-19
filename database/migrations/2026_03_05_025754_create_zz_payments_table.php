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
            $table->string('folio')->unique();              // PAY-2024-000001
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('payment_method_id')->constrained('payment_methods');

            $table->decimal('amount_received', 10, 2);     // lo que entregó el alumno
            $table->decimal('amount_applied', 10, 2);      // lo que se aplicó a adeudos
            $table->decimal('change_amount', 10, 2)->default(0);  // cambio/vuelto

            $table->datetime('payment_date');
            $table->string('receipt_number')->nullable();

            $table->enum('status', ['applied', 'partial', 'pending', 'cancelled', 'refunded'])
                ->default('applied');

            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users');  // cajero
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['student_id', 'payment_date']);
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
