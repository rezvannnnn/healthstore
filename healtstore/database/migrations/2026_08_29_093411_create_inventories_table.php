<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(0);

            $table->unsignedInteger('minimum_quantity')->default(0);

            $table->string('batch_number')->nullable();

            $table->date('expiry_date')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id', 'batch_number']);
            $table->index(['warehouse_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
