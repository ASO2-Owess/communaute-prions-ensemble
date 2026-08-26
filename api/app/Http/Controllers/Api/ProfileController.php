<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly AvatarService $avatars)
    {
    }

    /** PUT /api/v1/profile — seul le nom est modifiable. */
    public function update(Request $request): UserResource
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        // On passe un tableau explicite, jamais $request->all() : meme avec
        // $fillable correctement rempli, enumerer ici ce qui est modifiable
        // rend l'intention lisible.
        $request->user()->update(['name' => $data['name']]);

        return new UserResource($request->user()->refresh());
    }

    /** POST /api/v1/profile/avatar */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'file',
                // `image` s'appuie sur le contenu du fichier, pas sur son
                // extension : un .php renomme en .jpg est rejete ici.
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120', // 5 Mo
                'dimensions:min_width=100,min_height=100',
            ],
        ], [
            'avatar.max' => 'La photo ne doit pas depasser 5 Mo.',
            'avatar.dimensions' => 'La photo doit faire au moins 100x100 pixels.',
            'avatar.mimes' => 'Formats acceptes : JPEG, PNG ou WebP.',
        ]);

        try {
            $this->avatars->store($request->user(), $request->file('avatar'));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => 'Ce fichier n\'est pas une image valide.'], 422);
        }

        return response()->json([
            'message' => 'Photo mise a jour.',
            'user' => new UserResource($request->user()->refresh()),
        ]);
    }

    /** DELETE /api/v1/profile/avatar */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $this->avatars->remove($request->user());

        return response()->json([
            'message' => 'Photo retiree.',
            'user' => new UserResource($request->user()->refresh()),
        ]);
    }
}
