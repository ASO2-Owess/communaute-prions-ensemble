<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table de reference : les 66 livres de la Bible.
 * Contenu fige, charge par BookSeeder. Aucun utilisateur ne la modifie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            // Identifiant assigne a la main (1 a 66 = ordre canonique),
            // pas auto-incremente : l'ordre fait partie de la donnee.
            $table->unsignedTinyInteger('id')->primary();
            $table->string('name', 50)->unique();
            $table->string('slug', 50)->unique();
            $table->enum('testament', ['AT', 'NT']);
            // Permet au serveur de refuser "Genese 51".
            $table->unsignedSmallInteger('chapter_count');
            $table->unsignedTinyInteger('position');

            $table->index('testament', 'books_testament_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
