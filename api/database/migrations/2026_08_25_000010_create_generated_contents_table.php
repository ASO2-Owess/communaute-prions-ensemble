<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cache mutualise des contenus generes par l'IA.
 *
 * AUCUN user_id, et c'est delibere : le contenu n'appartient a personne, il
 * appartient a la communaute. Une meditation sur Jean 3 est produite une fois
 * et servie a tous — avec 2 000 membres, le cout est divise par 2 000 (ADR-002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_contents', function (Blueprint $table) {
            $table->id();
            $table->enum('kind', ['meditation', 'biography', 'chapter_quiz']);

            // "1-3" (livre-chapitre) ou "abraham" selon le type.
            $table->string('reference', 80);

            $table->json('payload');

            // Seuls les contenus approuves sont servis. Sur un sujet religieux,
            // une approximation doctrinale coute la confiance de la communaute.
            $table->enum('status', ['pending_review', 'approved', 'rejected'])
                  ->default('pending_review');

            // Tracabilite : quel modele a produit ce contenu.
            $table->string('model', 40)->nullable();

            $table->foreignId('reviewed_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // Un contenu unique par (type, reference) : c'est la mutualisation.
            $table->unique(['kind', 'reference'], 'generated_uniq');
            $table->index(['status', 'kind'], 'generated_review_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_contents');
    }
};
