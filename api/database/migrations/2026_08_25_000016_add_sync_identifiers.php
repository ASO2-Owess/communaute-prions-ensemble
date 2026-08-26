<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Synchronisation hors ligne (lot 4).
 *
 * Deux problèmes à résoudre, tous deux invisibles tant qu'on teste avec du
 * réseau :
 *
 * 1. **L'idempotence.** Un lot renvoyé après une coupure ne doit rien compter
 *    deux fois. Pour les lectures et les méditations, la contrainte d'unicité
 *    existante suffit. Pour les quiz et les notes, non : rejouer un quiz est
 *    légitime, donc rien ne distingue « deuxième partie » de « même partie
 *    renvoyée ». D'où un identifiant généré par le CLIENT.
 *
 * 2. **L'horodatage.** Une lecture faite mardi en mode avion doit compter
 *    mardi, pas le jour de la synchronisation — sinon les séries de jours
 *    consécutifs sont fausses pour tous ceux qui lisent hors ligne, c'est-à-
 *    dire précisément le public visé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('user_id');
            $table->unique(['user_id', 'client_uuid'], 'quiz_client_uuid_uniq');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->after('user_id');
            $table->unique(['user_id', 'client_uuid'], 'notes_client_uuid_uniq');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropUnique('quiz_client_uuid_uniq');
            $table->dropColumn('client_uuid');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropUnique('notes_client_uuid_uniq');
            $table->dropColumn('client_uuid');
        });
    }
};
