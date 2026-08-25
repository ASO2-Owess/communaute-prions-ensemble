<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],

            // Nom de l'appareil : permet de revoquer un jeton precis plus tard
            // ("deconnecter mon ancien telephone") sans deconnecter les autres.
            'device_name' => ['nullable', 'string', 'max:60'],
        ];
    }
}
