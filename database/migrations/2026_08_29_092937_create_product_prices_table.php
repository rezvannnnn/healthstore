<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('price_type')->default('retail');

            $table->decimal('price', 15, 2);

            $table->decimal('compare_at_price', 15, 2)->nullable();

            $table->unsignedInteger('min_quantity')->default(1);

            $table->boolean('is_active')->default(true);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            $table->index(['product_id', 'price_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
