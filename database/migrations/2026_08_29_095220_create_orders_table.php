<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->string('order_number')->unique();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('customer_type')->default('b2c');

            $table->foreignId('business_profile_id')
                ->nullable()
                ->constrained('business_profiles')
                ->nullOnDelete();

            $table->string('status')->default('pending');

            $table->string('payment_status')->default('pending');

            $table->decimal('subtotal', 15, 2)->default(0);

            $table->decimal('discount_amount', 15, 2)->default(0);

            $table->decimal('shipping_amount', 15, 2)->default(0);

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->string('currency')->default('IRR');

            $table->string('recipient_name')->nullable();

            $table->string('recipient_phone')->nullable();

            $table->string('province')->nullable();

            $table->string('city')->nullable();

            $table->text('shipping_address')->nullable();

            $table->string('postal_code')->nullable();

            $table->text('customer_note')->nullable();

            $table->text('admin_note')->nullable();

            $table->timestamp('confirmed_at')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamp('shipped_at')->nullable();

            $table->timestamp('delivered_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['customer_type', 'status']);
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
