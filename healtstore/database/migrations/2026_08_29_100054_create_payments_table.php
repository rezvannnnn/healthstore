<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->decimal('amount', 15, 2);

            $table->string('gateway')->nullable();

            $table->string('status')->default('pending');

            $table->string('authority')->nullable();

            $table->string('transaction_id')->nullable();

            $table->string('reference_number')->nullable();

            // Only the last 4 digits of the card number
            $table->string('card_last_four', 4)->nullable();

            // Token or card identifier returned by the payment gateway
            // and usable for future payment/refund operations if supported.
            $table->string('card_token')->nullable();

            $table->text('gateway_response')->nullable();

            $table->timestamp('paid_at')->nullable();

            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('transaction_id');
            $table->index('reference_number');
            $table->index('card_last_four');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
