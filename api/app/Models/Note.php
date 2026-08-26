<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Note personnelle, rattachee ou non a un passage. */
class Note extends Model
{
    protected $fillable = ['user_id', 'client_uuid', 'book_id', 'chapter', 'verse', 'content'];

    protected function casts(): array
    {
        return ['chapter' => 'integer', 'verse' => 'integer'];
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
