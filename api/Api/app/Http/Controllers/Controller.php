<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Classe de base des controleurs.
 *
 * Depuis Laravel 11 elle est vide par defaut. On y ajoute AuthorizesRequests
 * pour pouvoir ecrire $this->authorize('view', $question) dans les
 * controleurs : la methode leve une 403 si la Policy refuse.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
