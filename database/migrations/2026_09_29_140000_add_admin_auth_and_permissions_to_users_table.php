<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('admin_username')->nullable()->unique()->after('role');
            $table->string('admin_title')->nullable()->after('admin_username');
            $table->boolean('admin_active')->default(true)->after('admin_title')->index();
            $table->json('admin_permissions')->nullable()->after('admin_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'admin_username',
                'admin_title',
                'admin_active',
                'admin_permissions',
            ]);
        });
    }
};
