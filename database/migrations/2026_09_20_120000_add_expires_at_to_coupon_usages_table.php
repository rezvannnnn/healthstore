<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('reserved_at');
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropIndex(['status', 'expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};
