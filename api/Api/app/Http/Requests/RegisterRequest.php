<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Une FormRequest regroupe la validation d'une requete a un seul endroit.
 * Le controleur n'est appele que si toutes les regles passent : il n'a donc
 * jamais a verifier ses entrees lui-meme.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route publique
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],

            // Password::defaults() applique la politique definie une fois pour
            // toutes dans AppServiceProvider, au lieu de repeter des regles.
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.required' => 'Merci d\'indiquer ton nom.',
            'email.unique' => 'Un compte existe deja avec cette adresse.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ];
    }
}
