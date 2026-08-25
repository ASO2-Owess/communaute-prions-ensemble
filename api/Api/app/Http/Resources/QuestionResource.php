<?php

namespace App\Http\Resources;

use App\Models\Question;
use App\Services\PastoralService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue d'une question cote membre.
 *
 * `body` est masque par defaut sur le modele ($hidden) : on le rend visible
 * ici explicitement, parce qu'a cet endroit precis on sait que le lecteur y a
 * droit — la Policy l'a deja verifie.
 */
class QuestionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Question $question */
        $question = $this->resource;

        $answer = $question->relationLoaded('answer') ? $question->answer : null;
        $published = $answer && $answer->isPublished();

        return [
            'id' => $question->id,
            'body' => $question->body,
            'topic' => $question->topic,
            'status' => $question->status,
            'asked_at' => $question->created_at?->toIso8601String(),

            // Le membre voit une date, jamais "en attente" sans horizon.
            'expected_answer_by' => $published
                ? null
                : app(PastoralService::class)->expectedAnswerDate($question)->toDateString(),

            // Un brouillon n'existe pas du point de vue du membre.
            'answer' => $published ? [
                'body' => $answer->body,
                'author' => $answer->author?->name,
                'published_at' => $answer->published_at?->toIso8601String(),
            ] : null,
        ];
    }
}
