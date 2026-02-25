<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bank_transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bank_account_id')->index();
            $t->unsignedBigInteger('company_id')->index();
            $t->date('date');
            $t->string('method')->nullable();                // Bonifico, Pagamento Carta, ...
            $t->string('title');                             // contropartita / descrizione corta
            $t->string('subtitle')->nullable();              // descrizione lunga/note
            $t->decimal('amount', 14, 2);                    // + entrata / - uscita
            $t->string('category_name')->nullable();         // "Servizi", "Fornitori / CAFFÈ", ...
            $t->boolean('verified')->nullable();             // null/true/false
            $t->json('meta')->nullable();
            $t->timestamps();

            $t->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
            $t->index(['bank_account_id','date']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('bank_transactions');
    }
};
