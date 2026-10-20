<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('appointment_reschedulings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->restrictOnDelete();
            $table->foreignId('old_slot_id')->constrained('appointment_slots')->restrictOnDelete();
            $table->foreignId('new_slot_id')->constrained('appointment_slots')->restrictOnDelete();
            $table->foreignId('changed_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'created_at']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reschedulings');
    }
};
