<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fic_tokens', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->index();
            $t->unsignedBigInteger('company_id')->nullable()->index();
            $t->string('scope')->nullable();
            $t->text('access_token');
            $t->text('refresh_token');
            $t->timestamp('expires_at');
            $t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('fic_tokens');
    }
};
