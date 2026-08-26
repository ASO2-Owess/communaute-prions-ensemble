<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BibleFigure extends Model
{
    public $timestamps = false;

    protected $fillable = ['category_id', 'name', 'slug', 'position'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FigureCategory::class, 'category_id');
    }
}
