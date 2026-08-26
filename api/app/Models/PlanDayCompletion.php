<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDayCompletion extends Model
{
    public $timestamps = false;

    protected $fillable = ['plan_subscription_id', 'day', 'completed_at'];

    protected function casts(): array
    {
        return ['day' => 'integer', 'completed_at' => 'datetime'];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(PlanSubscription::class, 'plan_subscription_id');
    }
}
