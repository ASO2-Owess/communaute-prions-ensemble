<?php

namespace App\Support;

/**
 * Les instructions envoyees a l'IA, regroupees a un seul endroit.
 *
 * Ce ne sont pas des details techniques : ce sont des choix editoriaux qui
 * engagent la communaute. Les isoler ici permet de les relire, de les faire
 * valider par le pasteur, et de les corriger sans toucher au reste du code.
 *
 * Chaque prompt exige un JSON strict : le serveur doit pouvoir analyser la
 * reponse sans deviner. Les structures correspondent exactement a ce que
 * l'application affiche.
 */
final class Prompts
{
    private const CADRE = <<<'TXT'
    Tu ecris pour une communaute chretienne evangelique francophone d'Afrique
    de l'Ouest. Appuie-toi sur le texte biblique Louis Segond. Reste sobre,
    respectueux et accessible : evite le jargon theologique inutile. N'invente
    aucune reference biblique. Si un point fait debat entre traditions
    chretiennes, presente-le comme tel plutot que de trancher.
    TXT;

    public static function meditation(string $book, int $chapter, string $texte): string
    {
        $cadre = self::CADRE;

        return <<<TXT
        {$cadre}

        Redige une meditation sur {$book} chapitre {$chapter}.

        Voici le texte du chapitre :
        ---
        {$texte}
        ---

        Reponds UNIQUEMENT avec un JSON valide, sans texte avant ni apres,
        selon exactement cette structure :
        {
          "contexte": "(situation historique et litteraire du chapitre, 80-120 mots)",
          "resume": "(ce que raconte le chapitre, 60-100 mots)",
          "lecons": ["lecon spirituelle 1", "lecon spirituelle 2", "lecon spirituelle 3"],
          "versets_cles": ["reference + citation courte", "reference + citation courte"],
          "rhema": "(une phrase courte a mediter dans la journee)",
          "priere": "(une priere de 3 a 4 phrases inspiree du chapitre)"
        }
        TXT;
    }

    public static function biography(string $name, string $category): string
    {
        $cadre = self::CADRE;

        return <<<TXT
        {$cadre}

        Redige une etude biographique du personnage biblique "{$name}"
        (categorie : {$category}).

        Reponds UNIQUEMENT avec un JSON valide, sans texte avant ni apres,
        selon exactement cette structure :
        {
          "intro": "(2-3 phrases de presentation)",
          "parcours": "(recit detaille de son histoire biblique, 300-400 mots)",
          "episodes_cles": ["episode 1", "episode 2", "episode 3"],
          "lecons": ["lecon spirituelle 1", "lecon spirituelle 2", "lecon spirituelle 3"],
          "references": "(principales references bibliques, ex: Genese 12-25)",
          "rhema": "(une phrase courte inspiree de sa vie)"
        }
        TXT;
    }

    public static function chapterQuiz(string $book, int $chapter, string $texte): string
    {
        $cadre = self::CADRE;

        return <<<TXT
        {$cadre}

        Redige 5 questions a choix multiples portant UNIQUEMENT sur le texte de
        {$book} chapitre {$chapter} ci-dessous. Chaque question doit avoir une
        reponse verifiable dans ce texte precis, et 4 propositions dont une
        seule est correcte.

        ---
        {$texte}
        ---

        Reponds UNIQUEMENT avec un JSON valide, sans texte avant ni apres,
        selon exactement cette structure :
        {
          "questions": [
            {
              "q": "(la question)",
              "opts": ["proposition 1", "proposition 2", "proposition 3", "proposition 4"],
              "correct": 0,
              "explication": "(une phrase renvoyant au verset concerne)"
            }
          ]
        }

        "correct" est l'index (0 a 3) de la bonne proposition dans "opts".
        TXT;
    }
}
