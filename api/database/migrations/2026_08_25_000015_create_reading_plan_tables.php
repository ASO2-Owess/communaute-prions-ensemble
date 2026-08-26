<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plans de lecture (lot 3.1).
 *
 * Le prototype avait un plan unique de 12 jours, figé dans le code. Décision :
 * plusieurs plans, dont un annuel — le pasteur peut en créer d'autres (Carême,
 * Avent, Évangiles en 30 jours) sans qu'on republie l'application.
 *
 * Un jour de plan couvre une PLAGE de chapitres (`chapter_from` à
 * `chapter_to`) : « la Bible en un an » demande environ 3,3 chapitres par
 * jour. Stocker une ligne par chapitre aurait quadruplé la table pour rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reading_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('days_count');
            $table->boolean('published')->default(true);
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('reading_plan_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reading_plan_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day');
            $table->unsignedTinyInteger('book_id');
            $table->unsignedSmallInteger('chapter_from');
            $table->unsignedSmallInteger('chapter_to');
            $table->string('label', 120)->nullable();
            $table->unsignedTinyInteger('position')->default(1);

            $table->foreign('book_id')->references('id')->on('books');

            // Un jour peut comporter plusieurs entrées (fin d'un livre +
            // début du suivant), d'où `position` dans la clé.
            $table->unique(['reading_plan_id', 'day', 'position'], 'plan_days_uniq');
            $table->index(['reading_plan_id', 'day'], 'plan_days_lookup_idx');
        });

        Schema::create('plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reading_plan_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();
            $table->timestamps();

            // Un seul suivi actif par plan et par membre. On garde les
            // abandons : recommencer un plan trois fois est une information.
            $table->index(['user_id', 'reading_plan_id'], 'plan_subs_user_idx');
            $table->index(['user_id', 'completed_at', 'abandoned_at'], 'plan_subs_active_idx');
        });

        Schema::create('plan_day_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_subscription_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day');
            $table->timestamp('completed_at');

            $table->unique(['plan_subscription_id', 'day'], 'plan_day_uniq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_day_completions');
        Schema::dropIfExists('plan_subscriptions');
        Schema::dropIfExists('reading_plan_days');
        Schema::dropIfExists('reading_plans');
    }
};
