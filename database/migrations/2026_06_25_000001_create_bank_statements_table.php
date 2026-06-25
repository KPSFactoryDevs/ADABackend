<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_statements', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('filename');
            $t->string('filepath');
            $t->string('bank_name')->nullable();
            $t->string('iban')->nullable();
            $t->decimal('fido_accordato', 14, 2)->nullable();
            $t->date('date_from')->nullable();
            $t->date('date_to')->nullable();
            $t->string('status')->default('uploaded'); // uploaded, analyzing, completed, error
            $t->json('analysis_data')->nullable();
            $t->text('error_message')->nullable();
            $t->timestamps();

            $t->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
