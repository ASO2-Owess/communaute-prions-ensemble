<?php

namespace App\Services;

use App\Models\PlanDayCompletion;
use App\Models\PlanSubscription;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Plans de lecture (lot 3.1).
 *
 * Un plan est un parcours proposé ; le suivre est un engagement du membre.
 * On garde les abandons : recommencer trois fois « la Bible en un an » est une
 * information, pas un échec à effacer.
 */
class ReadingPlanService
{
    /** Le suivi en cours d'un membre pour un plan donné, s'il existe. */
    public function activeSubscription(User $user, ReadingPlan $plan): ?PlanSubscription
    {
        return PlanSubscription::where('user_id', $user->id)
            ->where('reading_plan_id', $plan->id)
            ->active()
            ->first();
    }

    public function subscribe(User $user, ReadingPlan $plan): PlanSubscription
    {
        $existant = $this->activeSubscription($user, $plan);

        if ($existant) {
            return $existant;
        }

        return PlanSubscription::create([
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'started_at' => now(),
        ]);
    }

    public function abandon(PlanSubscription $subscription): void
    {
        $subscription->update(['abandoned_at' => now()]);
    }

    /**
     * Marque un jour comme fait.
     *
     * Idempotent : refaire le même jour ne crée pas de doublon (unicité en
     * base sur (souscription, jour)).
     *
     * @return array{recorded: bool, completed_days: int, plan_completed: bool}
     */
    public function completeDay(PlanSubscription $subscription, int $day): array
    {
        return DB::transaction(function () use ($subscription, $day) {
            $completion = PlanDayCompletion::firstOrCreate(
                ['plan_subscription_id' => $subscription->id, 'day' => $day],
                ['completed_at' => now()]
            );

            $faits = $subscription->completions()->count();
            $total = $subscription->plan->days_count;

            // Le plan s'achève quand tous ses jours sont faits — pas quand on
            // arrive au dernier : on peut sauter un jour et y revenir.
            if ($faits >= $total && ! $subscription->completed_at) {
                $subscription->update(['completed_at' => now()]);
            }

            return [
                'recorded' => $completion->wasRecentlyCreated,
                'completed_days' => $faits,
                'plan_completed' => $faits >= $total,
            ];
        });
    }

    public function uncompleteDay(PlanSubscription $subscription, int $day): bool
    {
        $supprime = PlanDayCompletion::where('plan_subscription_id', $subscription->id)
            ->where('day', $day)
            ->delete();

        if ($supprime && $subscription->completed_at) {
            $subscription->update(['completed_at' => null]);
        }

        return (bool) $supprime;
    }

    /**
     * Le plan avec ses jours, et l'avancement du membre s'il le suit.
     *
     * @return array<string, mixed>
     */
    public function detail(ReadingPlan $plan, ?PlanSubscription $subscription): array
    {
        $faits = $subscription
            ? $subscription->completions()->pluck('day')->all()
            : [];

        $jours = $plan->days()->with('book:id,name,slug')->get()
            ->groupBy('day')
            ->map(fn ($entrees, $jour) => [
                'day' => (int) $jour,
                'label' => $entrees->firstWhere('label', '!=', null)?->label,
                'readings' => $entrees->map(fn ($e) => [
                    'book_id' => $e->book_id,
                    'book' => $e->book?->name,
                    'chapter_from' => $e->chapter_from,
                    'chapter_to' => $e->chapter_to,
                    'reference' => $e->reference(),
                ])->values(),
                'chapters' => $entrees->sum(fn ($e) => $e->chapterCount()),
                'completed' => in_array((int) $jour, $faits, true),
            ])
            ->values();

        return [
            'slug' => $plan->slug,
            'name' => $plan->name,
            'description' => $plan->description,
            'days_count' => $plan->days_count,
            'subscribed' => $subscription !== null,
            'completed_days' => count($faits),
            'next_day' => $this->nextDay($plan, $faits),
            'days' => $jours,
        ];
    }

    /** Le premier jour non fait — ce que l'application propose d'ouvrir. */
    private function nextDay(ReadingPlan $plan, array $faits): ?int
    {
        for ($jour = 1; $jour <= $plan->days_count; $jour++) {
            if (! in_array($jour, $faits, true)) {
                return $jour;
            }
        }

        return null;
    }
}
