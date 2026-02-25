<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documenti', function (Blueprint $table) {
            $table->id(); // Colonna ID autoincrementale
            $table->string('codice_documento')->nullable();
            $table->string('status')->nullable();
            $table->string('path')->nullable();
            $table->string('filename')->nullable();
            $table->string('type')->nullable();
            $table->unsignedBigInteger('company_id')->nullable(); // Foreign key per la tabella 'companies'
            $table->string('taxonomy')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Foreign key per la tabella 'users'
            $table->string('nome_azienda')->nullable();
            $table->year('anno_inizio')->nullable();
            $table->year('anno_fine')->nullable();
            $table->string('forma_giuridica')->nullable();
            $table->string('tipo_azienda')->nullable();
            $table->timestamps(); // Include created_at e updated_at

            // Se hai bisogno di creare relazioni con le tabelle 'companies' e 'users'
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documenti');
    }
}
