<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une ligne par partie jouee. Aucune contrainte d'unicite : rejouer un quiz
 * est legitime, et conserver chaque partie permet de montrer une progression.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('scope', ['general', 'chapter']);

            // NULL = "ne s'applique pas" : un quiz general ne porte sur aucun
            // chapitre. A ne pas confondre avec zero ou chaine vide.
            $table->unsignedTinyInteger('book_id')->nullable();
            $table->unsignedSmallInteger('chapter')->nullable();

            $table->unsignedTinyInteger('score');
            $table->unsignedTinyInteger('total');
            $table->timestamp('played_at');

            $table->foreign('book_id')->references('id')->on('books');
            $table->index(['user_id', 'played_at'], 'quiz_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};
