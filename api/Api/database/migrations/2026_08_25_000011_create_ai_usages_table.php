<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une ligne par generation demandee. Compter celles du jour donne le quota
 * consomme. Sans cette table, un seul compte peut epuiser le budget IA du
 * projet en une nuit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at'], 'ai_usages_quota_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
    }
};
