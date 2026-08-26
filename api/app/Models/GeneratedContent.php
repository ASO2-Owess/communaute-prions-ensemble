<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Contenu genere par l'IA, mutualise entre tous les membres.
 * Aucun user_id : il appartient a la communaute, pas a une personne.
 */
class GeneratedContent extends Model
{
    public const KIND_MEDITATION = 'meditation';
    public const KIND_BIOGRAPHY = 'biography';
    public const KIND_CHAPTER_QUIZ = 'chapter_quiz';

    public const STATUS_PENDING = 'pending_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'kind', 'reference', 'payload', 'status', 'model', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** Seuls les contenus approuves sont servis aux membres. */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /** Reference canonique d'un chapitre : "1-3" pour Genese 3. */
    public static function chapterReference(int $bookId, int $chapter): string
    {
        return $bookId . '-' . $chapter;
    }
}
