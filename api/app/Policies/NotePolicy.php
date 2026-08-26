<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

/**
 * Une note personnelle n'appartient qu'a son auteur.
 *
 * Contrairement aux questions, l'equipe pastorale n'y a AUCUN acces : ce sont
 * des reflexions privees, jamais destinees a etre lues par quelqu'un d'autre.
 */
class NotePolicy
{
    public function view(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function update(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $note->user_id === $user->id;
    }
}
