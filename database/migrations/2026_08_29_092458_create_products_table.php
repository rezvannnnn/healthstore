<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();

            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->index();

            $table->string('product_type')->nullable();

            $table->string('unit')->nullable();
            $table->unsignedInteger('quantity_per_unit')->nullable();

            $table->string('short_description')->nullable();
            $table->longText('description')->nullable();

            $table->json('specifications')->nullable();

            $table->date('expiry_date')->nullable();

            $table->string('main_image')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
