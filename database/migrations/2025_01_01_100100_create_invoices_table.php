<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('company_id')->index();   // azienda selezionata (proprietaria)
            $t->unsignedBigInteger('client_id')->nullable()->index(); // cliente della fattura

            // Dati fattura
            $t->string('direction', 16);  // 'issued' | 'received'
            $t->string('external_id', 64)->nullable(); // id su Fatture in Cloud
            $t->date('date')->nullable();
            $t->date('due_date')->nullable();
            $t->string('number', 64)->nullable();
            $t->string('document_number', 64)->nullable();
            $t->string('client_name', 190)->nullable();
            $t->string('client_vat', 32)->nullable();

            $t->decimal('amount_net', 15, 2)->default(0);
            $t->decimal('amount_gross', 15, 2)->default(0);
            $t->string('payment_method', 64)->nullable();

            // Stato applicazione (mappa semplice per UI)
            $t->string('status', 32)->default('Esigibile'); // Esigibile | In Valutazione | Acquistata | Non Elegibile

            // link al pdf o xml se disponibile
            $t->string('remote_url', 255)->nullable();

            $t->timestamps();

            // Evita duplicati per stessa azienda e direzione
            $t->unique(['company_id', 'direction', 'external_id']);

            $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $t->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $t->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::dropIfExists('invoices');
    }
};
