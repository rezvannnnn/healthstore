<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * The original cart_items migration was incomplete.
         *
         * The table may already contain these columns in an existing
         * development database, so add them only when they do not exist.
         */

        if (! Schema::hasColumn('cart_items', 'cart_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('cart_id')
                    ->nullable()
                    ->constrained('carts')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('cart_items', 'product_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->constrained('products')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('cart_items', 'quantity')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedInteger('quantity')
                    ->default(1);
            });
        }

        if (! Schema::hasColumn('cart_items', 'unit_price')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->decimal('unit_price', 15, 2)
                    ->default(0);
            });
        }
    }

    public function down(): void
    {
        /*
         * Do not drop the columns automatically here.
         *
         * The original migration history may already have created
         * these columns through a previous repair migration.
         */
    }
};
