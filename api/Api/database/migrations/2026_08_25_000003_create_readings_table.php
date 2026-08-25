<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une ligne par chapitre lu, par utilisateur, par cycle.
 * Relation N -> N entre users et chapitres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('book_id');
            $table->unsignedSmallInteger('chapter');
            $table->unsignedSmallInteger('cycle')->default(1);
            $table->timestamp('read_at');

            $table->foreign('book_id')->references('id')->on('books');

            // LA contrainte du projet : impossible de compter deux fois le meme
            // chapitre dans le meme cycle, donc impossible de farmer des points.
            // C'est la base qui l'empeche, pas le code applicatif.
            $table->unique(['user_id', 'book_id', 'chapter', 'cycle'], 'readings_uniq');

            // Affichage des coches "lu" d'un livre pour le cycle en cours.
            $table->index(['user_id', 'cycle', 'book_id'], 'readings_lookup_idx');
            // Statistiques communautaires : chapitres les plus lus.
            $table->index(['book_id', 'chapter'], 'readings_stats_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
