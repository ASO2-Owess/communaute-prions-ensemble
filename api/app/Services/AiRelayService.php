<?php

namespace App\Services;

use App\Models\AiUsage;
use App\Models\GeneratedContent;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Relais vers l'IA — la piece centrale de l'ADR-002.
 *
 * Quatre responsabilites, et c'est justement pour les tenir ensemble que ce
 * service existe :
 *
 *   1. La cle d'API ne quitte jamais le serveur.
 *   2. Un contenu est genere UNE FOIS puis servi a tous (cache mutualise).
 *   3. Un quota par membre empeche qu'un seul compte epuise le budget.
 *   4. Rien n'est servi avant relecture humaine.
 *
 * Consequence assumee du point 4 : le premier membre qui demande une
 * meditation inedite ne la recoit pas immediatement — elle part en file de
 * validation. La parade est de pre-generer a l'avance (php artisan
 * ai:pregenerate), pour que le cas courant soit deja approuve.
 */
class AiRelayService
{
    /**
     * Recupere un contenu, ou declenche sa generation.
     *
     * @return array{status: string, content: array|null, message: string|null}
     *         status vaut 'ready', 'pending_review', 'quota_exceeded' ou 'failed'.
     */
    public function fetch(User $user, string $kind, string $reference, string $prompt): array
    {
        $existing = GeneratedContent::where('kind', $kind)
            ->where('reference', $reference)
            ->first();

        // Deja approuve : aucun appel a l'IA, aucun cout. C'est le cas courant.
        if ($existing && $existing->status === GeneratedContent::STATUS_APPROVED) {
            return $this->ready($existing);
        }

        // Deja demande par quelqu'un d'autre : on n'appelle pas deux fois.
        if ($existing && $existing->status === GeneratedContent::STATUS_PENDING) {
            return $this->pending();
        }

        // Rejete par l'equipe pastorale : on ne regenere pas automatiquement.
        if ($existing && $existing->status === GeneratedContent::STATUS_REJECTED) {
            return [
                'status' => 'failed',
                'content' => null,
                'message' => 'Ce contenu a ete ecarte par l\'equipe pastorale.',
            ];
        }

        if (! $this->withinQuota($user)) {
            return [
                'status' => 'quota_exceeded',
                'content' => null,
                'message' => 'Tu as atteint ta limite de generations pour aujourd\'hui.',
            ];
        }

        $payload = $this->generate($prompt);

        if ($payload === null) {
            return [
                'status' => 'failed',
                'content' => null,
                'message' => 'La generation a echoue. Reessaie plus tard.',
            ];
        }

        AiUsage::create([
            'user_id' => $user->id,
            'kind' => $kind,
            'created_at' => now(),
        ]);

        GeneratedContent::updateOrCreate(
            ['kind' => $kind, 'reference' => $reference],
            [
                'payload' => $payload,
                'status' => GeneratedContent::STATUS_PENDING,
                'model' => config('ai.model'),
            ]
        );

        return $this->pending();
    }

    /**
     * Genere sans quota ni utilisateur — reserve a la commande de
     * pre-generation, lancee par un administrateur depuis le serveur.
     */
    public function generateForReview(string $kind, string $reference, string $prompt): ?GeneratedContent
    {
        $payload = $this->generate($prompt);

        if ($payload === null) {
            return null;
        }

        return GeneratedContent::updateOrCreate(
            ['kind' => $kind, 'reference' => $reference],
            [
                'payload' => $payload,
                'status' => GeneratedContent::STATUS_PENDING,
                'model' => config('ai.model'),
            ]
        );
    }

    /** Generations declenchees par ce membre depuis minuit. */
    public function usageToday(User $user): int
    {
        return AiUsage::where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    public function withinQuota(User $user): bool
    {
        return $this->usageToday($user) < config('ai.daily_quota_per_user');
    }

    // ------------------------------------------------------------------ prive

    /**
     * Appelle l'IA et renvoie le JSON analyse, ou null en cas d'echec.
     *
     * @return array<string, mixed>|null
     */
    private function generate(string $prompt): ?array
    {
        if (blank(config('ai.key'))) {
            Log::error('[ai] ANTHROPIC_API_KEY absente : generation impossible.');

            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('ai.key'),
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
                ->timeout(config('ai.timeout'))
                ->post(rtrim(config('ai.base_url'), '/') . '/v1/messages', [
                    'model' => config('ai.model'),
                    'max_tokens' => config('ai.max_tokens'),
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);
        } catch (\Throwable $e) {
            // On journalise le type d'erreur, jamais la cle ni le prompt complet.
            Log::error('[ai] appel echoue : ' . $e->getMessage());

            return null;
        }

        if ($response->failed()) {
            Log::error('[ai] reponse HTTP ' . $response->status());

            return null;
        }

        $text = collect($response->json('content', []))
            ->pluck('text')
            ->filter()
            ->implode("\n");

        return $this->parseJson($text);
    }

    /**
     * Analyse la reponse.
     *
     * Le modele encadre parfois son JSON de ```json ... ``` ou d'une phrase
     * d'introduction. On nettoie, puis on isole le premier objet complet.
     *
     * @return array<string, mixed>|null
     */
    private function parseJson(string $raw): ?array
    {
        $clean = trim(str_replace(['```json', '```'], '', $raw));

        $decoded = json_decode($clean, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $clean, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('[ai] reponse non analysable en JSON.');

        return null;
    }

    /** @return array{status: string, content: array, message: null} */
    private function ready(GeneratedContent $content): array
    {
        return ['status' => 'ready', 'content' => $content->payload, 'message' => null];
    }

    /** @return array{status: string, content: null, message: string} */
    private function pending(): array
    {
        return [
            'status' => 'pending_review',
            'content' => null,
            'message' => 'Ce contenu est en cours de relecture par l\'equipe pastorale.',
        ];
    }
}
