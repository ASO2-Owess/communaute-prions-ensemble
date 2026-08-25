<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

/**
 * Change le role d'un utilisateur.
 *
 *   php artisan user:promote pasteur@exemple.ci pastor
 *
 * Il n'existe volontairement aucune route HTTP pour cela : devenir pasteur ou
 * administrateur ne doit jamais dependre d'une requete entrante. La ligne de
 * commande exige un acces au serveur, ce qui est exactement la barriere qu'on
 * veut ici.
 */
class PromoteUser extends Command
{
    protected $signature = 'user:promote {email} {role}';

    protected $description = 'Attribue un role (member, pastor, admin) a un utilisateur';

    public function handle(): int
    {
        $email = $this->argument('email');
        $role = $this->argument('role');

        $validator = Validator::make(
            ['role' => $role],
            ['role' => [Rule::in([User::ROLE_MEMBER, User::ROLE_PASTOR, User::ROLE_ADMIN])]]
        );

        if ($validator->fails()) {
            $this->error("Role inconnu : {$role}. Valeurs possibles : member, pastor, admin.");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("Aucun compte avec l'adresse {$email}.");

            return self::FAILURE;
        }

        $previous = $user->role;
        $user->update(['role' => $role]);

        $this->info("{$user->name} : {$previous} -> {$role}");

        return self::SUCCESS;
    }
}
