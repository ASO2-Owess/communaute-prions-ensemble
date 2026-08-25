<?php

namespace App\Services;

use App\Models\ActivityDay;
use App\Models\Book;
use App\Models\MeditationCompletion;
use App\Models\QuizAttempt;
use App\Models\Reading;
use App\Models\User;
use App\Support\Points;
use Database\Seeders\BookSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Toute la logique de progression : points, niveaux, serie, cycles de lecture.
 *
 * Principe directeur (ADR-009) : la LECTURE est libre — relire, decocher,
 * recommencer, dans n'importe quel ordre, comme avec une Bible papier. Seuls
 * les POINTS sont contraints, pour que le classement reste honnete.
 *
 * Cette classe existe pour que les controleurs restent minces. Un controleur
 * traduit une requete HTTP en appel metier et renvoie une reponse ; il ne
 * decide de rien.
 */
class ProgressService
{
    /**
     * Enregistre la lecture d'un chapitre dans le cycle en cours.
     *
     * Toujours acceptee. La premiere lecture du cycle rapporte des points ;
     * les relectures sont comptees mais n'en rapportent pas.
     *
     * @return array{first_read: bool, points_awarded: int, read_count: int}
     */
    public function recordReading(User $user, Book $book, int $chapter): array
    {
        return DB::transaction(function () use ($user, $book, $chapter) {
            $reading = Reading::firstOrNew([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'chapter' => $chapter,
                'cycle' => $user->reading_cycle,
            ]);

            $premiere = ! $reading->exists;

            if ($premiere) {
                $reading->fill([
                    'read_at' => now(),
                    'last_read_at' => now(),
                    'read_count' => 1,
                    'is_read' => true,
                ])->save();

                $this->awardPoints($user, Points::READ);
            } else {
                // Relecture : on incremente en base plutot qu'en PHP, pour que
                // deux requetes simultanees ne s'ecrasent pas.
                $reading->increment('read_count');
                $reading->forceFill([
                    'last_read_at' => now(),
                    'is_read' => true, // relire recoche automatiquement
                ])->save();
            }

            // Une relecture reste une activite : elle entretient la serie.
            $this->touchActivityDay($user);

            return [
                'first_read' => $premiere,
                'points_awarded' => $premiere ? Points::READ : 0,
                'read_count' => $reading->refresh()->read_count,
            ];
        });
    }

    /**
     * Retire la coche "lu" d'un chapitre.
     *
     * La ligne N'EST PAS supprimee : elle garde la trace que les points ont
     * deja ete attribues pour ce chapitre dans ce cycle. Sinon, decocher puis
     * relire redonnerait 5 points a chaque fois — le classement serait
     * truquable en trois clics.
     *
     * Les points deja gagnes restent acquis : on ne reprend pas ce qui a ete
     * merite.
     */
    public function unmarkReading(User $user, Book $book, int $chapter): bool
    {
        $reading = Reading::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('chapter', $chapter)
            ->where('cycle', $user->reading_cycle)
            ->first();

        if (! $reading) {
            return false;
        }

        $reading->forceFill(['is_read' => false])->save();

        return true;
    }

    /**
     * Enregistre une meditation achevee.
     *
     * @return array{first_read: bool, points_awarded: int}
     */
    public function recordMeditation(User $user, Book $book, int $chapter): array
    {
        return DB::transaction(function () use ($user, $book, $chapter) {
            $completion = MeditationCompletion::firstOrCreate(
                ['user_id' => $user->id, 'book_id' => $book->id, 'chapter' => $chapter],
                ['completed_at' => now()]
            );

            $premiere = $completion->wasRecentlyCreated;

            if ($premiere) {
                $this->awardPoints($user, Points::MEDITATION);
            }

            $this->touchActivityDay($user);

            return [
                'first_read' => $premiere,
                'points_awarded' => $premiere ? Points::MEDITATION : 0,
            ];
        });
    }

    /**
     * Enregistre une partie de quiz. Toujours comptee : rejouer est legitime.
     *
     * @param  array{scope: string, book_id?: int|null, chapter?: int|null, score: int, total: int}  $data
     */
    public function recordQuizAttempt(User $user, array $data): QuizAttempt
    {
        return DB::transaction(function () use ($user, $data) {
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'scope' => $data['scope'],
                'book_id' => $data['book_id'] ?? null,
                'chapter' => $data['chapter'] ?? null,
                'score' => $data['score'],
                'total' => $data['total'],
                'played_at' => now(),
            ]);

            $this->awardPoints($user, Points::QUIZ);
            $this->touchActivityDay($user);

            return $attempt;
        });
    }

    /**
     * Ouvre un nouveau cycle de lecture.
     *
     * Autorise a tout moment — pas besoin d'avoir tout lu (ADR-009). Deux
     * garde-fous seulement :
     *   - au moins un chapitre lu dans le cycle en cours (sinon l'operation
     *     n'a aucun sens) ;
     *   - un delai entre deux reinitialisations, sans lequel la sequence
     *     "lire un chapitre / reinitialiser / relire" rapporterait des points
     *     a l'infini.
     *
     * Aucune ligne n'est supprimee : on incremente un compteur. Les lectures
     * des cycles precedents restent en base, ce qui garde points_total
     * recalculable.
     *
     * @return array{ok: bool, reason: string|null, next_allowed_at: string|null}
     */
    public function resetReadingCycle(User $user): array
    {
        return DB::transaction(function () use ($user) {
            if ($this->currentCycleReadCount($user) === 0) {
                return ['ok' => false, 'reason' => 'empty', 'next_allowed_at' => null];
            }

            $next = $this->resetAvailableAt($user);

            if ($next && $next->isFuture()) {
                return [
                    'ok' => false,
                    'reason' => 'cooldown',
                    'next_allowed_at' => $next->toIso8601String(),
                ];
            }

            // forceFill : reading_cycle et last_reading_reset_at ne sont pas
            // dans $fillable, justement pour qu'une requete entrante ne puisse
            // jamais les toucher. Seul le service en a le droit.
            $user->forceFill([
                'reading_cycle' => $user->reading_cycle + 1,
                'last_reading_reset_at' => now(),
            ])->save();

            // points_total n'est PAS touche : le score continue de grimper.
            return ['ok' => true, 'reason' => null, 'next_allowed_at' => null];
        });
    }

    public function resetAvailableAt(User $user): ?Carbon
    {
        if (! $user->last_reading_reset_at) {
            return null;
        }

        return $user->last_reading_reset_at
            ->copy()
            ->addHours(config('reading.reset_cooldown_hours'));
    }

    public function canReset(User $user): bool
    {
        if ($this->currentCycleReadCount($user) === 0) {
            return false;
        }

        $next = $this->resetAvailableAt($user);

        return $next === null || $next->isPast();
    }

    /** Chapitres actuellement coches "lu" dans le cycle en cours. */
    public function currentCycleReadCount(User $user): int
    {
        return Reading::where('user_id', $user->id)
            ->where('cycle', $user->reading_cycle)
            ->where('is_read', true)
            ->count();
    }

    public function hasCompletedCurrentCycle(User $user): bool
    {
        return $this->currentCycleReadCount($user) >= BookSeeder::TOTAL_CHAPTERS;
    }

    /**
     * Etat complet de la progression, tel que l'application l'affiche.
     *
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $points = $user->points_total;
        $level = Points::levelFor($points);
        $read = $this->currentCycleReadCount($user);
        $next = $this->resetAvailableAt($user);

        return [
            'points_total' => $points,
            'level' => $level['name'],
            'points_to_next_level' => Points::pointsToNextLevel($points),
            'chapters_read' => $read,
            'chapters_total' => BookSeeder::TOTAL_CHAPTERS,
            'bible_completed' => $read >= BookSeeder::TOTAL_CHAPTERS,
            'meditations_done' => MeditationCompletion::where('user_id', $user->id)->count(),
            'quizzes_played' => QuizAttempt::where('user_id', $user->id)->count(),
            'streak_days' => $this->currentStreak($user),
            'reading_cycle' => $user->reading_cycle,
            'bibles_completed' => $user->completedBibleCount(),
            'can_reset_reading' => $this->canReset($user),
            'reset_available_at' => $next?->toIso8601String(),
        ];
    }

    /**
     * Serie de jours consecutifs se terminant aujourd'hui ou hier.
     *
     * On tolere "hier" pour ne pas casser la serie de quelqu'un qui n'a pas
     * encore ouvert l'application aujourd'hui.
     */
    public function currentStreak(User $user): int
    {
        $days = ActivityDay::where('user_id', $user->id)
            ->orderByDesc('day')
            ->limit(400)
            ->pluck('day');

        if ($days->isEmpty()) {
            return 0;
        }

        $today = Carbon::today();
        $first = Carbon::parse($days->first())->startOfDay();

        if ($first->diffInDays($today) > 1) {
            return 0;
        }

        $streak = 1;
        $previous = $first;

        foreach ($days->slice(1) as $day) {
            $current = Carbon::parse($day)->startOfDay();

            if ($previous->copy()->subDay()->equalTo($current)) {
                $streak++;
                $previous = $current;

                continue;
            }

            break;
        }

        return $streak;
    }

    // ------------------------------------------------------------------ prive

    /**
     * Incremente le cache de points.
     *
     * increment() emet un UPDATE ... SET points_total = points_total + N :
     * l'addition se fait dans la base, pas en PHP. Deux requetes simultanees
     * ne peuvent donc pas s'ecraser l'une l'autre.
     */
    private function awardPoints(User $user, int $points): void
    {
        $user->increment('points_total', $points);
    }

    /** Marque aujourd'hui comme jour actif. Idempotent grace a l'unicite en base. */
    private function touchActivityDay(User $user): void
    {
        ActivityDay::firstOrCreate([
            'user_id' => $user->id,
            'day' => Carbon::today()->toDateString(),
        ]);
    }
}
