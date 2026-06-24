<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('company_id')->index();

            $table->string('type', 32);           // 'bilancio' | 'cr' | 'fattura_xml'
            $table->string('filename', 255);
            $table->string('original_filename', 255)->nullable();
            $table->string('path', 512);           // storage path
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size')->default(0); // bytes
            $table->json('extracted_data')->nullable(); // dati estratti dal parsing

            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
