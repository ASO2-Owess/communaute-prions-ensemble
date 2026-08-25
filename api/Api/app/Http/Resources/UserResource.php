<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Une Resource decide de ce qui sort de l'API.
 *
 * Sans elle, on renverrait le modele brut — et toute colonne ajoutee demain se
 * retrouverait exposee sans qu'on l'ait decide. Ici, la sortie est explicite :
 * ce qui n'est pas ecrit ne sort pas.
 */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar_url' => $this->avatar_path
                ? Storage::disk('public')->url($this->avatar_path)
                : null,
            'points_total' => $this->points_total,
            'reading_cycle' => $this->reading_cycle,
            'member_since' => $this->created_at?->toDateString(),
        ];
    }
}
