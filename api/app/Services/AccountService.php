<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Suppression et export de compte.
 *
 * Decision produit : quand un membre part, TOUT part — questions, reponses
 * recues, notes, progression. C'est le choix le plus respectueux, et le plus
 * simple a expliquer a quelqu'un d'inquiet de ce qu'il a confie au pasteur.
 */
class AccountService
{
    /**
     * Un pasteur ou un administrateur ne peut pas se supprimer lui-meme.
     *
     * Ses reponses sont rattachees a lui (answers.author_id) et sont lues par
     * d'autres membres : les effacer detruirait le travail pastoral d'autrui.
     * Le retrait d'un pasteur passe par la ligne de commande, apres avoir
     * decide du sort de ses reponses.
     */
    public function canDelete(User $user): bool
    {
        return ! $user->isPastor();
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->deleteAvatar($user);

            // Les cles etrangeres en cascade emportent lectures, meditations,
            // quiz, jours d'activite, notes, questions — et, par les
            // questions, les reponses recues. La suppression est donc
            // garantie par la BASE, pas par une liste d'oublis possibles
            // dans le code.
            $user->tokens()->delete();
            $user->delete();
        });
    }

    /**
     * Toutes les donnees du membre, en JSON.
     *
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        return [
            'exporte_le' => now()->toIso8601String(),

            'profil' => [
                'nom' => $user->name,
                'email' => $user->email,
                'membre_depuis' => $user->created_at?->toDateString(),
                'points_total' => $user->points_total,
                'cycle_de_lecture' => $user->reading_cycle,
            ],

            'lectures' => $user->readings()
                ->with('book:id,name')
                ->get()
                ->map(fn ($r) => [
                    'reference' => "{$r->book?->name} {$r->chapter}",
                    'cycle' => $r->cycle,
                    'lu_le' => $r->read_at?->toDateString(),
                    'nombre_de_lectures' => $r->read_count,
                ]),

            'meditations' => $user->meditationCompletions()
                ->with('book:id,name')
                ->get()
                ->map(fn ($m) => [
                    'reference' => "{$m->book?->name} {$m->chapter}",
                    'faite_le' => $m->completed_at?->toDateString(),
                ]),

            'notes' => $user->notes()
                ->with('book:id,name')
                ->get()
                ->map(fn ($n) => [
                    'reference' => $n->book ? "{$n->book->name} {$n->chapter}" : null,
                    'contenu' => $n->content,
                    'ecrite_le' => $n->created_at?->toDateString(),
                ]),

            'quiz' => $user->quizAttempts()->get()
                ->map(fn ($q) => [
                    'portee' => $q->scope,
                    'score' => "{$q->score}/{$q->total}",
                    'joue_le' => $q->played_at?->toDateString(),
                ]),

            'questions' => $user->questions()->with('answer')->get()
                ->map(fn ($q) => [
                    'question' => $q->body,
                    'posee_le' => $q->created_at?->toDateString(),
                    'statut' => $q->status,
                    'reponse' => $q->answer?->isPublished() ? $q->answer->body : null,
                ]),
        ];
    }

    private function deleteAvatar(User $user): void
    {
        if (! $user->avatar_path) {
            return;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk(config('filesystems.avatars', 'public'));

        if ($disk->exists($user->avatar_path)) {
            $disk->delete($user->avatar_path);
        }
    }
}
