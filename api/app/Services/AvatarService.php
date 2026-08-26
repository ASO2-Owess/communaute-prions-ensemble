<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Photo de profil.
 *
 * Trois regles, chacune pour une raison precise :
 *
 *   1. La base ne stocke que le CHEMIN, jamais l'image. Une base est faite
 *      pour des lignes courtes qu'on trie et qu'on filtre.
 *   2. L'image est re-encodee en 256x256. Cela divise le poids par cent,
 *      efface les metadonnees cachees — dont la position GPS de la prise de
 *      vue — et rejette tout fichier qui pretend etre une image sans en etre
 *      une.
 *   3. Le nom du fichier est aleatoire. Le nom d'origine peut contenir
 *      n'importe quoi ; et comme le nom change a chaque envoi, l'URL change,
 *      donc le navigateur affiche la nouvelle photo au lieu de garder
 *      l'ancienne en cache.
 */
class AvatarService
{
    private const SIZE = 256;

    /**
     * Le disque vient de la configuration, jamais code en dur : passer du
     * stockage local a Cloudflare R2 en production ne demande alors aucune
     * modification de code.
     */
    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(config('filesystems.avatars', 'public'));
    }

    /** @return string Le chemin relatif enregistre dans users.avatar_path */
    public function store(User $user, UploadedFile $file): string
    {
        $image = $this->openImage($file);
        $square = $this->cropToSquare($image);

        $path = 'avatars/' . Str::random(24) . '.jpg';

        ob_start();
        imagejpeg($square, null, 82);
        $binary = ob_get_clean();

        imagedestroy($image);
        imagedestroy($square);

        $this->disk()->put($path, $binary);

        $this->deletePrevious($user);

        $user->update(['avatar_path' => $path]);

        return $path;
    }

    public function remove(User $user): void
    {
        $this->deletePrevious($user);
        $user->update(['avatar_path' => null]);
    }

    public function urlFor(User $user): ?string
    {
        return $user->avatar_path
            ? $this->disk()->url($user->avatar_path)
            : null;
    }

    // ------------------------------------------------------------------ prive

    private function openImage(UploadedFile $file): \GdImage
    {
        // imagecreatefromstring echoue sur tout ce qui n'est pas une vraie
        // image, quelle que soit l'extension du fichier envoye.
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw new \RuntimeException('Fichier image illisible.');
        }

        return $image;
    }

    /** Recadre au centre puis redimensionne en carre. */
    private function cropToSquare(\GdImage $source): \GdImage
    {
        $w = imagesx($source);
        $h = imagesy($source);
        $side = min($w, $h);

        $square = imagecreatetruecolor(self::SIZE, self::SIZE);

        // Fond parchemin : evite un cadre noir si l'image a de la transparence.
        $background = imagecolorallocate($square, 247, 242, 231);
        imagefilledrectangle($square, 0, 0, self::SIZE, self::SIZE, $background);

        imagecopyresampled(
            $square, $source,
            0, 0,
            (int) (($w - $side) / 2), (int) (($h - $side) / 2),
            self::SIZE, self::SIZE,
            $side, $side
        );

        return $square;
    }

    private function deletePrevious(User $user): void
    {
        if ($user->avatar_path && $this->disk()->exists($user->avatar_path)) {
            $this->disk()->delete($user->avatar_path);
        }
    }
}
