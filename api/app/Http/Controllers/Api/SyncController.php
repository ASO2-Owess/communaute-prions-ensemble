<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    public function __construct(
        private readonly SyncService $sync,
        private readonly ProgressService $progress,
    ) {
    }

    /**
     * POST /api/v1/sync
     *
     * Envoi d'un lot d'actions faites hors ligne.
     *
     * La réponse renvoie toujours 200, même si certains éléments ont échoué :
     * le client a besoin de savoir ce qui est passé et ce qui ne l'est pas,
     * pas de recevoir une erreur globale qui l'obligerait à tout renvoyer.
     */
    public function push(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:' . SyncService::MAX_ITEMS],
            'items.*.type' => ['required', 'string', 'in:reading,meditation,quiz_attempt,note'],
            'items.*.occurred_at' => ['nullable', 'date'],
            'items.*.client_uuid' => ['nullable', 'uuid'],
            'items.*.book_id' => ['nullable', 'integer'],
            'items.*.chapter' => ['nullable', 'integer', 'min:1'],
            'items.*.verse' => ['nullable', 'integer', 'min:1'],
            'items.*.content' => ['nullable', 'string', 'max:5000'],
            'items.*.scope' => ['nullable', 'in:general,chapter'],
            'items.*.score' => ['nullable', 'integer', 'min:0'],
            'items.*.total' => ['nullable', 'integer', 'min:0', 'max:50'],
        ]);

        $result = $this->sync->push($request->user(), $data['items']);

        return response()->json([
            ...$result,
            'progress' => $this->progress->summary($request->user()->refresh()),
        ]);
    }

    /**
     * GET /api/v1/sync?since=2026-08-25T10:00:00Z
     *
     * Ce qui a changé côté serveur : réponse du pasteur, progression modifiée
     * depuis un autre appareil. Sans `since`, on remonte un an.
     */
    public function pull(Request $request): JsonResponse
    {
        $request->merge(['since' => $this->reparerDecalageHoraire($request->query('since'))]);

        $request->validate(['since' => ['nullable', 'date']]);

        $since = $request->filled('since')
            ? Carbon::parse($request->string('since')->toString())
            : null;

        return response()->json([
            ...$this->sync->pull($request->user(), $since),
            'progress' => $this->progress->summary($request->user()),
        ]);
    }

    /**
     * Repare un horodatage ISO-8601 abime par la barre d'adresse.
     *
     * Un client qui colle une date dans l'URL sans l'encoder envoie :
     *
     *     /api/v1/sync?since=2026-08-25T10:00:00+00:00
     *
     * Or dans une chaine de requete, « + » est le codage historique de
     * l'ESPACE. Le serveur lit donc « 2026-08-25T10:00:00 00:00 », que PHP
     * refuse de comprendre (« double time specification »). Resultat : une
     * synchronisation parfaitement legitime etait rejetee.
     *
     * La regle a retenir : dans une URL, `+` ne veut pas dire `+`. Un client
     * correct encode la valeur (%2B) ; on ne peut pas compter dessus, donc le
     * serveur repare le cas — sans toucher a une date deja valide.
     */
    private function reparerDecalageHoraire(mixed $valeur): ?string
    {
        if (! is_string($valeur) || $valeur === '') {
            return null;
        }

        // « …T10:00:00 00:00 » -> « …T10:00:00+00:00 »
        return preg_replace('/(\d{2}:\d{2}:\d{2})\s(\d{2}:\d{2})$/', '$1+$2', $valeur);
    }
}
