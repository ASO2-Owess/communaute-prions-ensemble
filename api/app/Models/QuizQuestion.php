<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Question du quiz general.
 *
 * `correct_index` n'est JAMAIS envoye au client avec la question : sinon la
 * bonne reponse voyage avec l'enonce et il suffit d'ouvrir la reponse reseau
 * pour tricher. Le client envoie sa reponse, le serveur tranche.
 */
class QuizQuestion extends Model
{
    protected $fillable = ['question', 'options', 'correct_index', 'theme', 'published'];

    protected $hidden = ['correct_index'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_index' => 'integer',
            'published' => 'boolean',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('published', true);
    }
}
