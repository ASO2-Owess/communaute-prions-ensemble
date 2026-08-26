<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingPlan;
use App\Services\ReadingPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingPlanController extends Controller
{
    public function __construct(private readonly ReadingPlanService $plans)
    {
    }

    /** GET /api/v1/reading-plans — les plans disponibles. */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => ReadingPlan::published()->get()->map(function (ReadingPlan $plan) use ($user) {
                $sub = $this->plans->activeSubscription($user, $plan);

                return [
                    'slug' => $plan->slug,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'days_count' => $plan->days_count,
                    'subscribed' => $sub !== null,
                    'completed_days' => $sub?->completions()->count() ?? 0,
                ];
            }),
        ]);
    }

    /** GET /api/v1/reading-plans/{plan} — le détail, jour par jour. */
    public function show(Request $request, ReadingPlan $plan): JsonResponse
    {
        return response()->json([
            'data' => $this->plans->detail(
                $plan,
                $this->plans->activeSubscription($request->user(), $plan)
            ),
        ]);
    }

    /** POST /api/v1/reading-plans/{plan}/subscribe */
    public function subscribe(Request $request, ReadingPlan $plan): JsonResponse
    {
        $sub = $this->plans->subscribe($request->user(), $plan);

        return response()->json([
            'message' => "Tu suis maintenant « {$plan->name} ».",
            'data' => $this->plans->detail($plan, $sub),
        ], 201);
    }

    /** DELETE /api/v1/reading-plans/{plan}/subscribe — abandonner. */
    public function abandon(Request $request, ReadingPlan $plan): JsonResponse
    {
        $sub = $this->plans->activeSubscription($request->user(), $plan);

        if (! $sub) {
            return response()->json(['message' => 'Tu ne suis pas ce plan.'], 404);
        }

        $this->plans->abandon($sub);

        return response()->json([
            'message' => 'Plan abandonné. Tu pourras le reprendre quand tu veux.',
        ]);
    }

    /** POST /api/v1/reading-plans/{plan}/days/{day} — marquer un jour comme fait. */
    public function completeDay(Request $request, ReadingPlan $plan, int $day): JsonResponse
    {
        if ($day < 1 || $day > $plan->days_count) {
            return response()->json([
                'message' => "Ce plan compte {$plan->days_count} jours.",
            ], 422);
        }

        $sub = $this->plans->activeSubscription($request->user(), $plan);

        if (! $sub) {
            return response()->json([
                'message' => 'Commence par suivre ce plan.',
            ], 422);
        }

        $result = $this->plans->completeDay($sub, $day);

        return response()->json([
            ...$result,
            'data' => $this->plans->detail($plan, $sub->refresh()),
        ], $result['recorded'] ? 201 : 200);
    }

    /** DELETE /api/v1/reading-plans/{plan}/days/{day} — décocher un jour. */
    public function uncompleteDay(Request $request, ReadingPlan $plan, int $day): JsonResponse
    {
        $sub = $this->plans->activeSubscription($request->user(), $plan);

        if (! $sub || ! $this->plans->uncompleteDay($sub, $day)) {
            return response()->json(['message' => 'Ce jour n\'était pas marqué comme fait.'], 404);
        }

        return response()->json([
            'message' => 'Jour décoché.',
            'data' => $this->plans->detail($plan, $sub->refresh()),
        ]);
    }
}
