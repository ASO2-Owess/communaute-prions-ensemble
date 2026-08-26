<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenus éditoriaux servis par le serveur (lot 3).
 *
 * Ces données vivaient jusqu'ici en dur dans l'application. Conséquence :
 * corriger une faute dans une question de quiz obligeait à republier l'app —
 * et sur les magasins, une republication prend des jours.
 *
 * Règle : tout contenu susceptible de changer vient du serveur. Ce qui reste
 * embarqué doit être ce qui ne change jamais — le texte biblique, et rien
 * d'autre.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------- encouragements
        Schema::create('encouragements', function (Blueprint $table) {
            $table->id();
            $table->text('text');
            $table->string('reference', 60);
            $table->string('theme', 40)->nullable();
            $table->boolean('published')->default(true);
            $table->timestamps();

            $table->index('published', 'encouragements_published_idx');
        });

        // ------------------------------------------------ figures bibliques
        Schema::create('figure_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('label', 60);
            $table->unsignedTinyInteger('position');
        });

        Schema::create('bible_figures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('figure_categories')->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 80);
            $table->unsignedSmallInteger('position');

            // Un même personnage peut appartenir à deux catégories (Ève est
            // patriarche et femme de la Bible) : l'unicité porte sur le
            // couple, pas sur le seul slug.
            $table->unique(['category_id', 'slug'], 'figures_uniq');
            $table->index('slug', 'figures_slug_idx');
        });

        // ------------------------------------------------------ quiz général
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->json('options');            // exactement 4 propositions
            $table->unsignedTinyInteger('correct_index'); // 0 à 3
            $table->string('theme', 40)->nullable();
            $table->boolean('published')->default(true);
            $table->timestamps();

            $table->index('published', 'quiz_questions_published_idx');
        });

        // ------------------------------------------------------------- dons
        Schema::create('donation_methods', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);     // Wave, Orange Money...
            $table->string('label', 60);
            $table->string('phone', 30);
            $table->string('note', 200)->nullable();
            $table->unsignedTinyInteger('position')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // -------------------------------------------------- FAQ pastorale
        Schema::create('faq_entries', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->text('answer');
            $table->string('topic', 40)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('published')->default(false);

            // Trace de la question réelle qui a donné naissance à l'entrée.
            // nullOnDelete : si le membre supprime son compte, sa question
            // disparaît mais la FAQ reste — elle ne contient plus rien de lui.
            $table->foreignId('source_question_id')->nullable()
                  ->constrained('questions')->nullOnDelete();

            $table->foreignId('author_id')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['published', 'position'], 'faq_public_idx');
            $table->index('topic', 'faq_topic_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_entries');
        Schema::dropIfExists('donation_methods');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('bible_figures');
        Schema::dropIfExists('figure_categories');
        Schema::dropIfExists('encouragements');
    }
};
