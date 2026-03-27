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
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['room', 'calendar'])->default('room');
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('available_days')->nullable(); // ej: [1,2,3,4,5]
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('requires_qr')->default(false);
            $table->char('qr_room_code', 36)->unique()->nullable(); // UUID fijo del aula
            $table->string('color', 7)->nullable(); // hex ej: #0070C0
            $table->string('icon')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
