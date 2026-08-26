<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Suivi d'un plan par un membre. */
class PlanSubscription extends Model
{
    protected $fillable = [
        'user_id', 'reading_plan_id', 'started_at', 'completed_at', 'abandoned_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'abandoned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class, 'reading_plan_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(PlanDayCompletion::class);
    }

    public function isActive(): bool
    {
        return $this->completed_at === null && $this->abandoned_at === null;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('completed_at')->whereNull('abandoned_at');
    }
}
