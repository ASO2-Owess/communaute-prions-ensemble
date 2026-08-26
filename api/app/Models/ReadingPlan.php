<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReadingPlan extends Model
{
    protected $fillable = ['slug', 'name', 'description', 'days_count', 'published', 'position'];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'days_count' => 'integer'];
    }

    public function days(): HasMany
    {
        return $this->hasMany(ReadingPlanDay::class)->orderBy('day')->orderBy('position');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(PlanSubscription::class);
    }

    public function scopePublished($query)
    {
        return $query->where('published', true)->orderBy('position');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
