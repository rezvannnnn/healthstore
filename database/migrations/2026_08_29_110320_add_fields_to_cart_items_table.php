<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('cart_id')
                ->after('id')
                ->constrained('carts')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->after('cart_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')
                ->after('product_id')
                ->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['cart_id']);
            $table->dropForeign(['product_id']);
            $table->dropColumn([
                'cart_id',
                'product_id',
                'quantity',
            ]);
        });
    }
};