<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Une partie de quiz jouee. Rejouer est legitime : aucune contrainte d'unicite. */
class QuizAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'scope', 'book_id', 'chapter', 'score', 'total', 'played_at'];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
            'score' => 'integer',
            'total' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
