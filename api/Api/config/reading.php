<?php

/**
 * Regles de lecture (ADR-009).
 */
return [

    /*
    | Delai minimum entre deux reinitialisations de cycle, en heures.
    |
    | Un membre peut recommencer sa lecture quand il veut — comme il reposerait
    | sa Bible pour repartir de la Genese. Mais sans delai, la sequence
    | "lire Genese 1 (+5 pts) / reinitialiser / relire Genese 1 (+5 pts)"
    | rapporterait des points a l'infini.
    |
    | 24 heures ne gene aucun usage reel et rend l'abus sans interet.
    */
    'reset_cooldown_hours' => (int) env('READING_RESET_COOLDOWN_HOURS', 24),

];
