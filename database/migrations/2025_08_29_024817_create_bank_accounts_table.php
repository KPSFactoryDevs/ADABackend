<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bank_accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->string('name');
            $t->string('bank_code')->nullable();    // es. "MPS", "BPER"
            $t->string('iban')->nullable();
            $t->string('currency', 3)->default('EUR');
            $t->decimal('balance_cached', 14, 2)->default(0);
            $t->string('provider')->nullable();     // "gocardless", "manual", "paypal"
            $t->string('provider_account_id')->nullable();
            $t->timestamps();

            $t->index(['company_id','name']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('bank_accounts');
    }
};
