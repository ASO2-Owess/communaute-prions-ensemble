<?php

namespace App\Http\Resources;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue d'une question cote pasteur : il voit l'auteur, l'anciennete et le
 * brouillon de reponse — trois choses que le membre ne voit jamais.
 *
 * Deux Resources distinctes plutot qu'une seule avec des conditions partout :
 * chaque fichier dit clairement ce que son destinataire a le droit de voir.
 */
class PastorQuestionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Question $question */
        $question = $this->resource;

        $answer = $question->relationLoaded('answer') ? $question->answer : null;

        return [
            'id' => $question->id,
            'body' => $question->body,
            'topic' => $question->topic,
            'status' => $question->status,
            'asked_at' => $question->created_at?->toIso8601String(),
            'waiting_days' => (int) $question->created_at?->diffInDays(now()),

            'author' => $this->whenLoaded('user', fn () => [
                'id' => $question->user->id,
                'name' => $question->user->name,
            ]),

            'assigned_to' => $question->assigned_to,

            'draft' => $answer ? [
                'body' => $answer->body,
                'author_id' => $answer->author_id,
                'published' => $answer->isPublished(),
                'published_at' => $answer->published_at?->toIso8601String(),
                'updated_at' => $answer->updated_at?->toIso8601String(),
            ] : null,
        ];
    }
}
