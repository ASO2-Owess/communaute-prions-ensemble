<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le texte biblique cote serveur — 31 170 versets (ADR-008).
 *
 * Duplication assumee : le texte vit aussi dans l'application, ou il assure le
 * hors-ligne. Le serveur en a besoin pour trois choses que l'application ne
 * peut pas faire a sa place :
 *   1. ancrer les prompts IA dans le texte reel (enjeu doctrinal) ;
 *   2. generer des quiz dont les reponses sont verifiables ;
 *   3. afficher le passage au pasteur pendant sa relecture.
 *
 * Le texte biblique ne changera jamais : dupliquer une donnee figee ne cree
 * aucun risque de desynchronisation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verses', function (Blueprint $table) {
            $table->unsignedTinyInteger('book_id');
            $table->unsignedSmallInteger('chapter');
            $table->unsignedSmallInteger('number');
            $table->text('text');

            // Cle primaire composee, contrairement a activity_days ou on avait
            // prefere un id auto-incremente. La raison est differente ici :
            // cette table est en LECTURE SEULE et toujours interrogee par
            // (book_id, chapter). Dans InnoDB, les lignes sont physiquement
            // rangees dans l'ordre de la cle primaire — les versets d'un
            // chapitre sont donc contigus sur le disque, et un chapitre se lit
            // d'un seul coup. Aucun index secondaire n'est necessaire.
            $table->primary(['book_id', 'chapter', 'number']);

            $table->foreign('book_id')->references('id')->on('books');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verses');
    }
};
