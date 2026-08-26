<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meditations achevees. Table distincte de readings : lire et mediter sont
 * deux actes differents, qui ne rapportent pas le meme nombre de points et
 * evolueront separement.
 *
 * Pas de colonne cycle : une meditation reste acquise (voir MODELE-DONNEES 3 bis).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meditation_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('book_id');
            $table->unsignedSmallInteger('chapter');
            $table->timestamp('completed_at');

            $table->foreign('book_id')->references('id')->on('books');
            $table->unique(['user_id', 'book_id', 'chapter'], 'meditations_uniq');
            $table->index(['user_id', 'book_id'], 'meditations_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meditation_completions');
    }
};
