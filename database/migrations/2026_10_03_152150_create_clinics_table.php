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

        Schema::create('clinics', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('phone', 30)->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('city', 100);
            $table->string('state', 100)->nullable();
            $table->string('country', 100);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 100)->nullable();

            $table->enum('booking_confirmation_mode', [
                'auto_confirm',
                'manual_confirmation',
            ])->default('auto_confirm');

            $table->enum('payment_policy', [
                'pay_at_clinic',
                'online_only',
                'online_or_clinic',
            ])->default('online_or_clinic');

            $table->unsignedSmallInteger('reschedule_cutoff_hours')->default(2);
            $table->unsignedSmallInteger('confirmation_timeout_minutes')->default(30);
            $table->unsignedSmallInteger('payment_timeout_minutes')->default(15);

            $table->boolean('is_active')->default(true)->comment('Whether the clinic is available in the system.');
            $table->timestamps();

        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};
