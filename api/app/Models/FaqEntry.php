<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entree de FAQ pastorale (ADR-007).
 *
 * C'est LA parade au risque n 1 du projet : un seul pasteur pour plus de
 * 2 000 membres. Les questions se repetent ; y repondre une fois doit servir
 * a tous. Chaque entree peut naitre d'une vraie question, reformulee et
 * anonymisee par le pasteur.
 */
class FaqEntry extends Model
{
    protected $fillable = [
        'question', 'answer', 'topic', 'position',
        'published', 'source_question_id', 'author_id',
    ];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function sourceQuestion(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'source_question_id');
    }

    public function scopePublished($query)
    {
        return $query->where('published', true)->orderBy('position');
    }
}
