<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AskQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // 20 caracteres minimum : "aide moi" ne permet pas au pasteur de
            // repondre, et lui fait perdre un aller-retour.
            'body' => ['required', 'string', 'min:20', 'max:4000'],

            'topic' => ['nullable', Rule::in(config('pastoral.topics'))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'body.required' => 'Ecris ta question avant de l\'envoyer.',
            'body.min' => 'Donne un peu plus de contexte : au moins 20 caracteres.',
            'body.max' => 'Ta question depasse 4 000 caracteres.',
            'topic.in' => 'Ce theme n\'existe pas.',
        ];
    }
}
