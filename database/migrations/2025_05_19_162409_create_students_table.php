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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number', 20)->unique();
            $table->string('name', 100);
            $table->string('last_name1', 100);
            $table->string('last_name2');
            $table->enum('gender', ["M","F","O"]);
            $table->date('date_of_birth');
            $table->string('curp', 18)->unique();
            $table->string('email', 150)->unique();
            $table->string('phone', 15);
            $table->string('street', 100);
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postal_code', 10);
            $table->string('country', 100);
            $table->date('enrollment_date');
            $table->enum('status', ["active","inactive","graduated","suspended", "pre-registration"])->default('active');
            $table->string('guardian_name', 150)->nullable();
            $table->string('guardian_phone', 15)->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 15)->nullable();
            $table->string('photo', 255)->nullable();
            $table->string('code', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
