<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un jour d'activite = une ligne. Evite de fusionner trois tables et de
 * dedoublonner par jour a chaque calcul de serie (streak).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_days', function (Blueprint $table) {
            // Cle technique auto-incrementee plutot qu'une cle primaire
            // composee : Eloquent gere mal les cles composees. L'unicite reelle
            // est garantie par l'index unique ci-dessous.
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('day');

            $table->unique(['user_id', 'day'], 'activity_days_uniq');
            $table->index(['user_id', 'day'], 'activity_days_streak_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_days');
    }
};
