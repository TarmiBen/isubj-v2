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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_student_id')->constrained('students'); // quien refirió
            $table->foreignId('referred_student_id')->constrained('students'); // a quien refirió
            $table->string('referral_code', 20);
            $table->foreignId('discount_id')->constrained('discounts');        // descuento que activa

            $table->enum('status', ['pending', 'active', 'paused', 'expired', 'cancelled'])
                ->default('pending');

            $table->boolean('requires_referred_enrolled')->default(true); // descuento válido mientras esté inscrito

            $table->datetime('activated_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['referrer_student_id', 'referred_student_id']);
            $table->index('referral_code');
            $table->index(['referrer_student_id', 'status']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
