<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un jour ou l'utilisateur a fait quelque chose. Sert au calcul de la serie. */
class ActivityDay extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'day'];

    protected function casts(): array
    {
        return ['day' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
