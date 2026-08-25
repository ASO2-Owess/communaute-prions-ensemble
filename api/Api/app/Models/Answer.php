<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Reponse du pasteur a une question. Relation 1 -> 1 avec Question,
 * garantie par l'unicite de `question_id` en base.
 */
class Answer extends Model
{
    protected $fillable = ['question_id', 'author_id', 'body', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** Tant que published_at est NULL, la reponse est un brouillon invisible. */
    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }
}
