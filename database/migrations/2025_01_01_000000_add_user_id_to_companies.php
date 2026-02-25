use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

// ... dentro up():

// 1) allinea tipo + nullable (scegli BIGINT o INT a seconda del tuo users.id)
Schema::table('companies', function (Blueprint $table) {
    // se users.id è BIGINT:
    $table->unsignedBigInteger('user_id')->nullable()->change();
    // se fosse INT:
    // $table->unsignedInteger('user_id')->nullable()->change();
});

// 2) owner di fallback
$ownerId = DB::table('users')->min('id');

// 3) backfill orfani (NULL, 0, o id inesistenti)
DB::statement('
    UPDATE companies c
    LEFT JOIN users u ON u.id = c.user_id
    SET c.user_id = ?
    WHERE c.user_id IS NULL OR c.user_id = 0 OR u.id IS NULL
', [$ownerId]);

// 4) indice + FK (se non già presenti)
Schema::table('companies', function (Blueprint $table) {
    // proteggi da doppioni
    try { $table->index('user_id', 'companies_user_id_index'); } catch (\Throwable $e) {}
    try {
        $table->foreign('user_id', 'companies_user_id_foreign')
              ->references('id')->on('users')->onDelete('cascade');
    } catch (\Throwable $e) {}
});
