<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnswerQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la route est deja protegee par EnsurePastor
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:20', 'max:8000'],

            // Par defaut on enregistre un brouillon. Publier est un acte
            // separe et explicite : rien ne part vers le membre par accident.
            'publish' => ['sometimes', 'boolean'],
        ];
    }
}
