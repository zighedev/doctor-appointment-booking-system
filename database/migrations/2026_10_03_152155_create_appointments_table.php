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
        Schema::disableForeignKeyConstraints();

        Schema::create('appointments', function (Blueprint $table) {         
            $table->id();
            $table->foreignId('appointment_slot_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();

            $table->enum('status', [
                'pending',
                'awaiting_payment',
                'confirmed',
                'checked_in',
                'in_progress',
                'completed',
                'cancelled',
                'rejected',
                'expired',
                'no_show',
            ])->default('pending');

            $table->text('reason')->nullable();
            $table->text('patient_notes')->nullable();
            $table->text('doctor_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->foreignId('cancelled_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_in_by_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['appointment_slot_id', 'status']);
            $table->index(['status', 'expires_at']);
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
