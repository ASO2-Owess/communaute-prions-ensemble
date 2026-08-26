<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un verset. Table en lecture seule, alimentee par VerseSeeder.
 *
 * Cle primaire composee (book_id, chapter, number) : Verse::find() n'a donc
 * aucun sens ici, et on ne s'en sert pas. Toutes les lectures passent par
 * where(...) — voir BibleTextService.
 */
class Verse extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = null;

    protected $fillable = ['book_id', 'chapter', 'number', 'text'];

    protected function casts(): array
    {
        return [
            'chapter' => 'integer',
            'number' => 'integer',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
