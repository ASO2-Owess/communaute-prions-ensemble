<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc-notes personnel. Les references bibliques sont nullables :
 * une note libre n'est rattachee a aucun passage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('book_id')->nullable();
            $table->unsignedSmallInteger('chapter')->nullable();
            $table->unsignedSmallInteger('verse')->nullable();
            $table->text('content');
            $table->timestamps();

            $table->foreign('book_id')->references('id')->on('books');
            $table->index(['user_id', 'created_at'], 'notes_user_idx');
            $table->index(['user_id', 'book_id', 'chapter'], 'notes_passage_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
