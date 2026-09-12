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
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();

            /*
             * Phone number in normalized format.
             */
            $table->string('phone', 20)->index();

            /*
             * Never store the OTP itself.
             * Store only a secure hash of the generated code.
             */
            $table->string('code_hash');

            /*
             * OTP becomes invalid after this timestamp.
             */
            $table->timestamp('expires_at');

            /*
             * Number of incorrect verification attempts.
             */
            $table->unsignedTinyInteger('attempts')->default(0);

            /*
             * Set when the OTP is successfully verified.
             */
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            /*
             * Useful for quickly finding the latest OTP
             * for a specific phone number.
             */
            $table->index([
                'phone',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};