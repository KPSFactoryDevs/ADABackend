<?php
// database/migrations/2025_08_21_000000_create_clients_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clients', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('company_id')->index();
            $t->unsignedBigInteger('user_id')->index();

            $t->string('nome', 150);
            $t->string('piva', 32);
            $t->string('regione', 80)->nullable();
            $t->string('citta', 120)->nullable();
            $t->string('email', 150)->nullable();
            $t->decimal('fatturato', 18, 2)->nullable();
            $t->tinyInteger('rating')->nullable(); // 0..5
            $t->string('status', 32)->default('non_approvato'); // "approvato" | "non_approvato"

            $t->timestamps();

            $t->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $t->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $t->unique(['company_id','piva']); // stessa PIVA non duplicabile nella stessa azienda
        });
    }
    public function down(): void { Schema::dropIfExists('clients'); }
};
