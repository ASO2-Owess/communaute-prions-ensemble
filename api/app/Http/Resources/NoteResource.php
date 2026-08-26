<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'book_id' => $this->book_id,
            'book_name' => $this->whenLoaded('book', fn () => $this->book?->name),
            'chapter' => $this->chapter,
            'verse' => $this->verse,

            // Reference lisible : "Jean 3.16", "Jean 3" ou null.
            'reference' => $this->reference(),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function reference(): ?string
    {
        if (! $this->relationLoaded('book') || ! $this->book || ! $this->chapter) {
            return null;
        }

        return $this->verse
            ? "{$this->book->name} {$this->chapter}.{$this->verse}"
            : "{$this->book->name} {$this->chapter}";
    }
}
