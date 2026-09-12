<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('business_name');

            $table->string('business_type')->nullable();

            $table->string('national_id')->nullable();

            $table->string('registration_number')->nullable();

            $table->string('tax_id')->nullable();

            $table->string('contact_name')->nullable();

            $table->string('phone')->nullable();

            $table->text('address')->nullable();

            $table->string('postal_code')->nullable();

            $table->string('status')->default('pending');

            $table->text('admin_note')->nullable();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->unique('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};