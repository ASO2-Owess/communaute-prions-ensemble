<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FigureCategory extends Model
{
    public $timestamps = false;

    protected $fillable = ['slug', 'label', 'position'];

    public function figures(): HasMany
    {
        return $this->hasMany(BibleFigure::class, 'category_id')->orderBy('position');
    }
}
