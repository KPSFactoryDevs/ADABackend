<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_statement_entries', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('bank_statement_id')->index();
            $t->date('date_operazione');
            $t->date('date_valuta')->nullable();
            $t->text('descrizione');
            $t->decimal('dare', 14, 2)->default(0);
            $t->decimal('avere', 14, 2)->default(0);
            $t->decimal('saldo', 14, 2)->nullable();
            $t->timestamps();

            $t->foreign('bank_statement_id')
                ->references('id')
                ->on('bank_statements')
                ->onDelete('cascade');

            $t->index(['bank_statement_id', 'date_operazione']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_entries');
    }
};
