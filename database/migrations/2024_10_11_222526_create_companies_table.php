<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id(); // Colonna ID autoincrementale
            $table->string('partita_iva')->nullable();
            $table->string('ragione_sociale')->nullable();
            $table->string('indirizzo')->nullable();
            $table->string('provincia')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap')->nullable();
            $table->string('ateco')->nullable();
            $table->decimal('capitale_sociale', 15, 2)->nullable();
            $table->string('pec')->nullable();
            $table->string('ultimo_bilancio')->nullable();
            $table->decimal('fatturato', 15, 2)->nullable();
            $table->string('settore')->nullable();
            $table->string('telefono')->nullable();
            $table->timestamps(); // Include created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
