<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sessions_caisse')) if (Schema::hasTable('sessions_caisse')) Schema::table('sessions_caisse', function (Blueprint $table) {
            $table->string('devise', 3)
                ->after('caisse_id')
                ->comment('Devise utilisée pour toute la session (ex: XOF, CDF, USD)');
            $table->timestamp('validated_at')->nullable(); // Correction ici
            $table->foreignId('validated_by')
                ->nullable() // Rendre la clé étrangère nullable si besoin
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('validation_commentaire',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sessions_caisse')) if (Schema::hasTable('sessions_caisse')) Schema::table('sessions_caisse', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn('validation_commentaire');
            $table->dropColumn('validated_by');
            $table->dropColumn('validated_at');
            $table->dropColumn('devise');
        });
    }
};
