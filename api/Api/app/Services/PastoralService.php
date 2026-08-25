<?php

namespace App\Services;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Accompagnement pastoral : poser une question, la traiter, y repondre.
 *
 * C'est la fonctionnalite la plus differenciante du produit — et celle qui
 * n'existait pas du tout dans le prototype, ou les questions restaient sur le
 * telephone de l'utilisateur sans jamais atteindre personne.
 */
class PastoralService
{
    /** Nombre de questions sans reponse pour ce membre. */
    public function openQuestionCount(User $user): int
    {
        return Question::where('user_id', $user->id)
            ->whereIn('status', [Question::STATUS_PENDING, Question::STATUS_ASSIGNED])
            ->count();
    }

    public function canAsk(User $user): bool
    {
        return $this->openQuestionCount($user) < config('pastoral.max_open_questions_per_user');
    }

    /** Enregistre une nouvelle question. */
    public function ask(User $user, string $body, ?string $topic = null): Question
    {
        return Question::create([
            'user_id' => $user->id,
            'body' => $body,
            'topic' => $topic,
            'status' => Question::STATUS_PENDING,
        ]);
    }

    /**
     * Date de reponse annoncee au membre.
     *
     * Une promesse tenable vaut mieux qu'une attente sans horizon.
     */
    public function expectedAnswerDate(Question $question): Carbon
    {
        return $question->created_at->copy()
            ->addDays(config('pastoral.response_delay_days'));
    }

    // ------------------------------------------------------------- cote pasteur

    /**
     * File d'attente, du plus ancien au plus recent.
     *
     * with('user') charge les auteurs en une seule requete supplementaire au
     * lieu d'une par question. Sans ca, afficher 50 questions en declencherait
     * 51 — le probleme dit "N+1", la premiere cause de lenteur d'une API.
     */
    public function queue(?string $topic = null, int $perPage = 25): LengthAwarePaginator
    {
        return Question::awaiting()
            ->when($topic, fn ($q) => $q->where('topic', $topic))
            ->with(['user:id,name,avatar_path'])
            ->paginate($perPage);
    }

    /** Le pasteur prend une question en charge. */
    public function claim(Question $question, User $pastor): Question
    {
        $question->update([
            'assigned_to' => $pastor->id,
            'status' => Question::STATUS_ASSIGNED,
        ]);

        return $question->refresh();
    }

    /**
     * Cree ou met a jour le brouillon de reponse.
     *
     * Tant que published_at est NULL, le membre ne voit rien. Le pasteur peut
     * ecrire, fermer, relire le lendemain et publier ensuite — sur des sujets
     * sensibles, ce n'est pas un confort.
     */
    public function draftAnswer(Question $question, User $pastor, string $body): Answer
    {
        return DB::transaction(function () use ($question, $pastor, $body) {
            $answer = Answer::updateOrCreate(
                ['question_id' => $question->id],
                ['author_id' => $pastor->id, 'body' => $body]
            );

            if ($question->status === Question::STATUS_PENDING) {
                $question->update([
                    'assigned_to' => $question->assigned_to ?? $pastor->id,
                    'status' => Question::STATUS_ASSIGNED,
                ]);
            }

            return $answer;
        });
    }

    /** Publie la reponse : c'est le seul moment ou le membre la voit. */
    public function publish(Answer $answer): Answer
    {
        return DB::transaction(function () use ($answer) {
            $answer->update(['published_at' => now()]);
            $answer->question->update(['status' => Question::STATUS_ANSWERED]);

            return $answer->refresh();
        });
    }

    /**
     * Etat de la file — le tableau de bord du pasteur.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $awaiting = Question::awaiting()->count();
        $oldest = Question::awaiting()->first();

        return [
            'awaiting' => $awaiting,
            'unassigned' => Question::where('status', Question::STATUS_PENDING)->count(),
            'answered_total' => Question::where('status', Question::STATUS_ANSWERED)->count(),
            'oldest_waiting_days' => $oldest
                ? (int) $oldest->created_at->diffInDays(now())
                : 0,
            'by_topic' => Question::awaiting()
                ->selectRaw('COALESCE(topic, \'autre\') as topic, COUNT(*) as total')
                ->groupBy('topic')
                ->pluck('total', 'topic'),
            'response_delay_days' => config('pastoral.response_delay_days'),
        ];
    }
}
