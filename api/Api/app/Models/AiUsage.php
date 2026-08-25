<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trace d'une generation IA demandee. Sert exclusivement aux quotas :
 * sans elle, un seul compte peut epuiser le budget IA du projet en une nuit.
 */
class AiUsage extends Model
{
    /** Seul created_at existe : une trace ne se modifie jamais. */
    public $timestamps = false;

    protected $fillable = ['user_id', 'kind', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
