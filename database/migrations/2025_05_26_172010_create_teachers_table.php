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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name1', 100);
            $table->string('last_name2', 100);
            $table->enum('gender', ["M","F","O"]);
            $table->date('date_of_birth');
            $table->string('curp', 18)->unique();
            $table->string('email', 150)->unique();
            $table->string('phone', 20);
            $table->string('mobile', 20);
            $table->date('hire_date');
            $table->enum('status', ["active","inactive","on_leave","retired"])->default('active');
            $table->string('street', 150);
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postal_code', 10);
            $table->string('country', 100);
            $table->string('title', 100);
            $table->string('specialization', 150);
            $table->string('photo', 255);
            $table->string('emergency_contact_name', 150);
            $table->string('emergency_contact_phone', 20);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
