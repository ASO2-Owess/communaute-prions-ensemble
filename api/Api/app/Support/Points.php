<?php

namespace App\Support;

/**
 * Bareme de points et niveaux.
 *
 * Ces valeurs sont des regles metier, pas des details techniques : elles
 * vivent a un seul endroit pour qu'on puisse les ajuster sans chercher des
 * nombres dispersees dans le code.
 */
final class Points
{
    public const READ = 5;
    public const MEDITATION = 15;
    public const QUIZ = 10;

    /** Seuils repris du prototype valide par le client. */
    public const LEVELS = [
        ['name' => 'Disciple',  'from' => 0],
        ['name' => 'Serviteur', 'from' => 150],
        ['name' => 'Intendant', 'from' => 400],
        ['name' => 'Ancien',    'from' => 900],
        ['name' => 'Berger',    'from' => 1800],
    ];

    /** Niveau atteint avec un total de points donne. */
    public static function levelFor(int $points): array
    {
        $current = self::LEVELS[0];

        foreach (self::LEVELS as $level) {
            if ($points >= $level['from']) {
                $current = $level;
            }
        }

        return $current;
    }

    /**
     * Points manquants pour le niveau suivant, ou null si le dernier niveau
     * est atteint.
     */
    public static function pointsToNextLevel(int $points): ?int
    {
        foreach (self::LEVELS as $level) {
            if ($points < $level['from']) {
                return $level['from'] - $points;
            }
        }

        return null;
    }
}
