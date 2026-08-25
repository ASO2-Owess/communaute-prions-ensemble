<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reponse du pasteur. Table separee de questions : elle a ses propres
 * attributs (auteur, date de publication, etat de brouillon).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->id();

            // L'unicite fait la relation 1 -> 1 :
            // une question a au plus une reponse.
            $table->foreignId('question_id')->unique()
                  ->constrained()->cascadeOnDelete();

            $table->foreignId('author_id')->constrained('users');
            $table->text('body');

            // NULL = brouillon. Le pasteur peut ecrire, relire, publier plus
            // tard : sur des sujets sensibles ce n'est pas un luxe.
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['author_id', 'created_at'], 'answers_author_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answers');
    }
};
