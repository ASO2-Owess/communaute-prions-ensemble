<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Complete la table users livree par Laravel.
 *
 * On ajoute plutot que de modifier la migration d'origine : le projet reste
 * ainsi compatible avec les evolutions du squelette Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Un role, pas un booleen is_pastor : passer a plusieurs pasteurs
            // ne demandera aucune migration (ADR-007).
            $table->enum('role', ['member', 'pastor', 'admin'])
                  ->default('member')->after('password');

            // Chemin du fichier, jamais l'image elle-meme.
            $table->string('avatar_path', 255)->nullable()->after('role');

            // Cache denormalise : le classement doit trier 2 000 utilisateurs.
            // La verite reste dans readings / meditation_completions /
            // quiz_attempts ; ce total est recalculable a partir d'elles.
            // Ne diminue JAMAIS, meme apres une reinitialisation de lecture.
            $table->unsignedInteger('points_total')->default(0)->after('avatar_path');

            // Cycle de lecture en cours. Reinitialiser = incrementer.
            $table->unsignedSmallInteger('reading_cycle')->default(1)->after('points_total');

            $table->index('points_total', 'users_points_idx'); // classement
            $table->index('role', 'users_role_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_points_idx');
            $table->dropIndex('users_role_idx');
            $table->dropColumn(['role', 'avatar_path', 'points_total', 'reading_cycle']);
        });
    }
};
