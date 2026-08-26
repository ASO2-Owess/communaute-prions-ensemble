<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une entree d'un jour de plan : une PLAGE de chapitres d'un meme livre.
 *
 * Un jour qui chevauche deux livres produit deux entrees, distinguees par
 * `position`. Stocker une ligne par chapitre aurait quadruple la table sans
 * rien apporter.
 */
class ReadingPlanDay extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'reading_plan_id', 'day', 'book_id',
        'chapter_from', 'chapter_to', 'label', 'position',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'integer',
            'chapter_from' => 'integer',
            'chapter_to' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class, 'reading_plan_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /** Reference lisible : « Genese 1-3 » ou « Jean 3 ». */
    public function reference(): string
    {
        $nom = $this->book?->name ?? '?';

        return $this->chapter_from === $this->chapter_to
            ? "{$nom} {$this->chapter_from}"
            : "{$nom} {$this->chapter_from}-{$this->chapter_to}";
    }

    public function chapterCount(): int
    {
        return $this->chapter_to - $this->chapter_from + 1;
    }
}
