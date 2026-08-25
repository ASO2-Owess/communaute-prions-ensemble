<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questions posees a l'equipe pastorale.
 *
 * DONNEES SENSIBLES : le contenu de `body` peut etre intime. Il ne doit jamais
 * apparaitre dans les journaux applicatifs, ni etre visible par un autre
 * membre, ni transiter vers un service tiers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');

            // Pre-tri par theme : absorbe une partie de la charge pastorale.
            $table->string('topic', 40)->nullable();

            // Cycle de vie explicite. Sans lui, impossible de distinguer
            // "pas encore lue" de "lue, reponse en cours de redaction".
            $table->enum('status', ['pending', 'assigned', 'answered', 'archived'])
                  ->default('pending');

            // Pointe vers un utilisateur, jamais vers une personne codee en dur.
            $table->foreignId('assigned_to')->nullable()
                  ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at'], 'questions_file_idx');
            $table->index(['user_id', 'created_at'], 'questions_user_idx');
            $table->index(['assigned_to', 'status'], 'questions_assignee_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
