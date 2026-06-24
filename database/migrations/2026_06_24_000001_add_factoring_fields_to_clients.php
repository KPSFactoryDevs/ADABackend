<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Score dei documenti (scala 0-10)
            $table->decimal('bilancio_score', 4, 2)->nullable()->after('status');
            $table->decimal('cr_score', 4, 2)->nullable()->after('bilancio_score');

            // Flag documenti caricati
            $table->boolean('has_invoices')->default(false)->after('cr_score');
            $table->boolean('has_bilancio')->default(false)->after('has_invoices');
            $table->boolean('has_cr')->default(false)->after('has_bilancio');

            // Stato valutazione factoring
            $table->string('evaluation_status', 32)->nullable()->after('has_cr');
            // null = mai inviato | 'pending' | 'approved' | 'rejected'
            $table->timestamp('evaluation_sent_at')->nullable()->after('evaluation_status');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'bilancio_score',
                'cr_score',
                'has_invoices',
                'has_bilancio',
                'has_cr',
                'evaluation_status',
                'evaluation_sent_at',
            ]);
        });
    }
};
