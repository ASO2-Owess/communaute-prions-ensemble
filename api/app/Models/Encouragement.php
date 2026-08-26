<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Verset d'encouragement — le « verset du jour » de l'accueil. */
class Encouragement extends Model
{
    protected $fillable = ['text', 'reference', 'theme', 'published'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
}
