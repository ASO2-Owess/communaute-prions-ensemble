<?php

namespace App\Console\Commands;

use App\Models\MeditationCompletion;
use App\Models\QuizAttempt;
use App\Models\Reading;
use App\Models\User;
use App\Support\Points;
use Illuminate\Console\Command;

/**
 * Recalcule points_total a partir des tables sources.
 *
 *   php artisan points:recalculate
 *   php artisan points:recalculate --fix
 *
 * C'est cette commande qui JUSTIFIE la denormalisation.
 *
 * On avait accepte de dupliquer le total des points sur users pour que le
 * classement puisse trier 2 000 membres sans parcourir trois tables. Le prix
 * a payer : ce cache peut se desynchroniser (une action enregistree, le cache
 * oublie, une transaction interrompue). La contrepartie promise etait de
 * pouvoir le recalculer — sans cette commande, la promesse n'etait pas tenue.
 *
 * Sans --fix, elle se contente de SIGNALER les ecarts. On regarde avant de
 * corriger : un ecart massif indiquerait un bug, pas une derive, et
 * l'ecraser silencieusement effacerait l'indice.
 */
class RecalculatePoints extends Command
{
    protected $signature = 'points:recalculate {--fix : Appliquer les corrections}';

    protected $description = 'Verifie et corrige le total de points de chaque membre';

    public function handle(): int
    {
        $ecarts = [];

        User::query()->orderBy('id')->chunkById(200, function ($users) use (&$ecarts) {
            foreach ($users as $user) {
                $attendu = $this->compute($user);

                if ($attendu !== $user->points_total) {
                    $ecarts[] = [
                        $user->id,
                        $user->name,
                        $user->points_total,
                        $attendu,
                        $attendu - $user->points_total,
                    ];

                    if ($this->option('fix')) {
                        $user->forceFill(['points_total' => $attendu])->save();
                    }
                }
            }
        });

        if ($ecarts === []) {
            $this->info('Aucun ecart : tous les totaux sont coherents.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Membre', 'Enregistre', 'Attendu', 'Ecart'], $ecarts);

        if ($this->option('fix')) {
            $this->info(count($ecarts) . ' total(aux) corrige(s).');
        } else {
            $this->warn(count($ecarts) . ' ecart(s) detecte(s). Relance avec --fix pour corriger.');
        }

        return self::SUCCESS;
    }

    /**
     * Le total tel qu'il decoule des donnees brutes.
     *
     * Les lectures sont comptees TOUS CYCLES CONFONDUS : c'est exactement
     * pourquoi une reinitialisation incremente un compteur au lieu de
     * supprimer les lignes (ADR-009). Avec une suppression, ce calcul rendrait
     * un total plus faible et ferait chuter les scores.
     */
    private function compute(User $user): int
    {
        $lectures = Reading::where('user_id', $user->id)->count();
        $meditations = MeditationCompletion::where('user_id', $user->id)->count();
        $quiz = QuizAttempt::where('user_id', $user->id)->count();

        return $lectures * Points::READ
            + $meditations * Points::MEDITATION
            + $quiz * Points::QUIZ;
    }
}
