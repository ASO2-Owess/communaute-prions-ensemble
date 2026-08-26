<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Meditation achevee. Reste acquise : aucun cycle. */
class MeditationCompletion extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'book_id', 'chapter', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime', 'chapter' => 'integer'];
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
