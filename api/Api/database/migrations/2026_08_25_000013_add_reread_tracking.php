<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relectures libres (ADR-009).
 *
 * Regle produit : on doit pouvoir relire un chapitre autant de fois qu'on veut,
 * le decocher, et recommencer une lecture quand on veut — comme avec une Bible
 * papier. Seuls les POINTS restent limites.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            // Nombre de fois que ce chapitre a ete ouvert dans ce cycle.
            $table->unsignedSmallInteger('read_count')->default(1)->after('cycle');

            // read_at garde la PREMIERE lecture (celle qui a rapporte les
            // points) ; last_read_at suit la plus recente.
            $table->timestamp('last_read_at')->nullable()->after('read_at');

            // Decocher ne SUPPRIME pas la ligne : elle reste, avec is_read a
            // false. C'est elle qui garde la trace que les points ont deja ete
            // attribues — sinon decocher puis relire les redonnerait a chaque
            // fois, et le classement deviendrait truquable en trois clics.
            $table->boolean('is_read')->default(true)->after('last_read_at');

            $table->index(['user_id', 'cycle', 'is_read'], 'readings_state_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            // Sert au delai entre deux reinitialisations (voir ADR-009).
            $table->timestamp('last_reading_reset_at')->nullable()->after('reading_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('readings', function (Blueprint $table) {
            $table->dropIndex('readings_state_idx');
            $table->dropColumn(['read_count', 'last_read_at', 'is_read']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_reading_reset_at');
        });
    }
};
