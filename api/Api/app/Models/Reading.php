<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un chapitre lu, dans un cycle donne.
 *
 * La ligne existe des la premiere lecture et ne disparait jamais ensuite :
 * c'est elle qui garde la trace que les points ont deja ete attribues.
 * Decocher met is_read a false, ne supprime pas la ligne (ADR-009).
 */
class Reading extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'book_id', 'chapter', 'cycle',
        'read_at', 'last_read_at', 'read_count', 'is_read',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'last_read_at' => 'datetime',
            'chapter' => 'integer',
            'cycle' => 'integer',
            'read_count' => 'integer',
            'is_read' => 'boolean',
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
