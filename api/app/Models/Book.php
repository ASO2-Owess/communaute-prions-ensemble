<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Livre de la Bible. Table de reference : contenu fige, charge par BookSeeder.
 */
class Book extends Model
{
    /** L'id (1 a 66) est assigne a la main : l'ordre canonique est une donnee. */
    public $incrementing = false;

    protected $keyType = 'int';

    /** Aucune colonne created_at / updated_at : cette table ne bouge pas. */
    public $timestamps = false;

    protected $fillable = ['id', 'name', 'slug', 'testament', 'chapter_count', 'position'];

    protected function casts(): array
    {
        return [
            'chapter_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }

    /** Le serveur peut ainsi refuser "Genese 51". */
    public function hasChapter(int $chapter): bool
    {
        return $chapter >= 1 && $chapter <= $this->chapter_count;
    }

    /*
     * PAS de getRouteKeyName() renvoyant 'slug'.
     *
     * Il y en avait un, et il faisait echouer toutes les routes /api/v1/contents :
     * l'application envoie des identifiants numeriques ("43" pour Jean), mais
     * la liaison de route cherchait un livre dont le slug vaut "43" — jamais
     * trouve, donc 404. Le slug reste en base pour un usage futur (URLs
     * lisibles cote web), mais l'API travaille avec des identifiants partout,
     * comme dans le corps des requetes.
     */
}
