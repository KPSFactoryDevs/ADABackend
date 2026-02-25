<?php

// database/migrations/2025_08_28_000001_add_role_flags_to_clients_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('is_customer')->default(false)->after('rating')->index();
            $table->boolean('is_supplier')->default(false)->after('is_customer')->index();
        });
    }

    public function down(): void {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['is_customer', 'is_supplier']);
        });
    }
};
