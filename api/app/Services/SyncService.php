<?php

namespace App\Services;

use App\Models\Book;
use App\Models\MeditationCompletion;
use App\Models\Note;
use App\Models\QuizAttempt;
use App\Models\Reading;
use App\Models\User;
use App\Support\Points;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Synchronisation hors ligne (lot 4).
 *
 * Le hors-ligne est LA contrainte structurante du projet. Une requête par
 * action ne tient pas : quelqu'un qui lit quarante chapitres dans l'avion
 * enverrait quarante requêtes à la reconnexion, sur une connexion faible et
 * facturée à la donnée.
 *
 * Trois exigences, chacune invisible tant qu'on teste avec du réseau :
 *
 *   1. **Idempotence** — un lot renvoyé après une coupure ne compte rien deux
 *      fois. Les lectures et méditations sont protégées par leur contrainte
 *      d'unicité ; les quiz et les notes portent un identifiant généré par le
 *      client.
 *   2. **Horodatage client** — une lecture faite mardi en mode avion compte
 *      mardi, pas le jour de la synchronisation. Sinon les séries de jours
 *      consécutifs sont fausses pour tous ceux qui lisent hors ligne.
 *   3. **Tolérance aux erreurs partielles** — un élément invalide dans le lot
 *      ne doit pas faire échouer les trente-neuf autres.
 */
class SyncService
{
    /** Au-delà, on refuse : un lot énorme signale un bug côté client. */
    public const MAX_ITEMS = 500;

    /**
     * Applique un lot d'actions faites hors ligne.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    public function push(User $user, array $items): array
    {
        $applies = 0;
        $ignores = 0;
        $erreurs = [];
        $points = 0;
        $jours = [];

        foreach ($items as $index => $item) {
            try {
                $resultat = $this->applyOne($user, $item, $jours);

                $resultat['applied'] ? $applies++ : $ignores++;
                $points += $resultat['points'];
            } catch (\Throwable $e) {
                // Un élément fautif ne fait pas tomber le lot entier.
                $erreurs[] = [
                    'index' => $index,
                    'type' => $item['type'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        // Les jours d'activité sont enregistrés une seule fois à la fin :
        // quarante lectures du même jour ne doivent pas déclencher quarante
        // écritures identiques.
        $this->recordActivityDays($user, $jours);

        return [
            'applied' => $applies,
            'ignored' => $ignores,      // déjà connus : c'est l'idempotence
            'errors' => $erreurs,
            'points_awarded' => $points,
        ];
    }

    /**
     * Ce qui a changé côté serveur depuis une date : réponses du pasteur,
     * contenus approuvés, progression modifiée sur un autre appareil.
     *
     * @return array<string, mixed>
     */
    public function pull(User $user, ?Carbon $since): array
    {
        $depuis = $since ?? now()->subYear();

        return [
            'server_time' => now()->toIso8601String(),

            'answers' => $user->questions()
                ->with('answer')
                ->get()
                ->filter(fn ($q) => $q->answer?->isPublished()
                    && $q->answer->published_at->greaterThan($depuis))
                ->map(fn ($q) => [
                    'question_id' => $q->id,
                    'answer' => $q->answer->body,
                    'published_at' => $q->answer->published_at->toIso8601String(),
                ])->values(),

            'readings' => Reading::where('user_id', $user->id)
                ->where('cycle', $user->reading_cycle)
                ->where('is_read', true)
                ->where('last_read_at', '>=', $depuis)
                ->get(['book_id', 'chapter'])
                ->map(fn ($r) => ['book_id' => $r->book_id, 'chapter' => $r->chapter]),

            'notes' => Note::where('user_id', $user->id)
                ->where('updated_at', '>=', $depuis)
                ->get(['id', 'client_uuid', 'book_id', 'chapter', 'verse', 'content', 'updated_at']),
        ];
    }

    // ------------------------------------------------------------------ privé

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, string>  $jours  collecte des dates d'activité
     * @return array{applied: bool, points: int}
     */
    private function applyOne(User $user, array $item, array &$jours): array
    {
        $quand = $this->parseDate($item['occurred_at'] ?? null);
        $jours[] = $quand->toDateString();

        return match ($item['type'] ?? null) {
            'reading' => $this->reading($user, $item, $quand),
            'meditation' => $this->meditation($user, $item, $quand),
            'quiz_attempt' => $this->quiz($user, $item, $quand),
            'note' => $this->note($user, $item),
            default => throw new \InvalidArgumentException('Type inconnu : ' . ($item['type'] ?? 'aucun')),
        };
    }

    /** @return array{applied: bool, points: int} */
    private function reading(User $user, array $item, Carbon $quand): array
    {
        $book = $this->book($item);
        $chapitre = (int) ($item['chapter'] ?? 0);

        if (! $book->hasChapter($chapitre)) {
            throw new \InvalidArgumentException("{$book->name} n'a pas de chapitre {$chapitre}.");
        }

        return DB::transaction(function () use ($user, $book, $chapitre, $quand) {
            $reading = Reading::firstOrNew([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'chapter' => $chapitre,
                'cycle' => $user->reading_cycle,
            ]);

            if (! $reading->exists) {
                $reading->fill([
                    'read_at' => $quand,
                    'last_read_at' => $quand,
                    'read_count' => 1,
                    'is_read' => true,
                ])->save();

                $user->increment('points_total', Points::READ);

                return ['applied' => true, 'points' => Points::READ];
            }

            $reading->increment('read_count');
            $reading->forceFill(['last_read_at' => $quand, 'is_read' => true])->save();

            return ['applied' => false, 'points' => 0];
        });
    }

    /** @return array{applied: bool, points: int} */
    private function meditation(User $user, array $item, Carbon $quand): array
    {
        $book = $this->book($item);
        $chapitre = (int) ($item['chapter'] ?? 0);

        if (! $book->hasChapter($chapitre)) {
            throw new \InvalidArgumentException("{$book->name} n'a pas de chapitre {$chapitre}.");
        }

        return DB::transaction(function () use ($user, $book, $chapitre, $quand) {
            $c = MeditationCompletion::firstOrCreate(
                ['user_id' => $user->id, 'book_id' => $book->id, 'chapter' => $chapitre],
                ['completed_at' => $quand]
            );

            if (! $c->wasRecentlyCreated) {
                return ['applied' => false, 'points' => 0];
            }

            $user->increment('points_total', Points::MEDITATION);

            return ['applied' => true, 'points' => Points::MEDITATION];
        });
    }

    /**
     * Le quiz est le cas qui EXIGE un identifiant client : rejouer est
     * légitime, donc rien ne distingue « deuxième partie » de « même partie
     * renvoyée après une coupure ».
     *
     * @return array{applied: bool, points: int}
     */
    private function quiz(User $user, array $item, Carbon $quand): array
    {
        $uuid = $item['client_uuid'] ?? null;

        if (! $uuid) {
            throw new \InvalidArgumentException('client_uuid requis pour un quiz.');
        }

        if (QuizAttempt::where('user_id', $user->id)->where('client_uuid', $uuid)->exists()) {
            return ['applied' => false, 'points' => 0];
        }

        $chapitre = ($item['scope'] ?? 'general') === 'chapter';

        return DB::transaction(function () use ($user, $item, $uuid, $quand, $chapitre) {
            QuizAttempt::create([
                'user_id' => $user->id,
                'client_uuid' => $uuid,
                'scope' => $chapitre ? 'chapter' : 'general',
                'book_id' => $chapitre ? ($item['book_id'] ?? null) : null,
                'chapter' => $chapitre ? ($item['chapter'] ?? null) : null,
                'score' => (int) ($item['score'] ?? 0),
                'total' => (int) ($item['total'] ?? 0),
                'played_at' => $quand,
            ]);

            $user->increment('points_total', Points::QUIZ);

            return ['applied' => true, 'points' => Points::QUIZ];
        });
    }

    /** @return array{applied: bool, points: int} */
    private function note(User $user, array $item): array
    {
        $uuid = $item['client_uuid'] ?? null;

        if (! $uuid) {
            throw new \InvalidArgumentException('client_uuid requis pour une note.');
        }

        // updateOrCreate : une note modifiée hors ligne écrase sa version
        // précédente. Deux appareils qui modifient la même note gardent la
        // dernière reçue — acceptable pour une note personnelle, contrairement
        // à une donnée partagée.
        Note::updateOrCreate(
            ['user_id' => $user->id, 'client_uuid' => $uuid],
            [
                'content' => (string) ($item['content'] ?? ''),
                'book_id' => $item['book_id'] ?? null,
                'chapter' => $item['chapter'] ?? null,
                'verse' => $item['verse'] ?? null,
            ]
        );

        return ['applied' => true, 'points' => 0];
    }

    private function book(array $item): Book
    {
        $book = Book::find($item['book_id'] ?? 0);

        if (! $book) {
            throw new \InvalidArgumentException('Livre inconnu : ' . ($item['book_id'] ?? 'aucun'));
        }

        return $book;
    }

    /**
     * L'horodatage vient du client, mais on ne lui fait pas aveuglément
     * confiance : une date dans le futur permettrait de fabriquer des séries
     * de jours consécutifs. On plafonne à maintenant.
     */
    private function parseDate(?string $valeur): Carbon
    {
        if (! $valeur) {
            return now();
        }

        try {
            $date = Carbon::parse($valeur);
        } catch (\Throwable) {
            return now();
        }

        return $date->isFuture() ? now() : $date;
    }

    /** @param array<int, string> $jours */
    private function recordActivityDays(User $user, array $jours): void
    {
        foreach (array_unique($jours) as $jour) {
            DB::table('activity_days')->insertOrIgnore([
                'user_id' => $user->id,
                'day' => $jour,
            ]);
        }
    }
}
