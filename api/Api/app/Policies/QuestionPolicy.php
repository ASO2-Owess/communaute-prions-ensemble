<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

/**
 * Qui a le droit de voir quoi.
 *
 * Laravel decouvre cette classe automatiquement : App\Models\Question ->
 * App\Policies\QuestionPolicy. Aucune declaration n'est necessaire.
 *
 * Regle absolue : un membre ne voit JAMAIS la question d'un autre membre.
 * Ces textes touchent a la vie spirituelle et parfois intime des gens.
 */
class QuestionPolicy
{
    public function view(User $user, Question $question): bool
    {
        return $question->user_id === $user->id || $user->isPastor();
    }

    public function update(User $user, Question $question): bool
    {
        // Seule l'equipe pastorale modifie le statut ou l'attribution.
        return $user->isPastor();
    }

    public function answer(User $user, Question $question): bool
    {
        return $user->isPastor();
    }
}
