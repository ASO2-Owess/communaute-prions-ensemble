<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['general', 'chapter'])],

            // Obligatoires si et seulement si le quiz porte sur un chapitre.
            'book_id' => ['nullable', 'required_if:scope,chapter', 'integer', 'exists:books,id'],
            'chapter' => ['nullable', 'required_if:scope,chapter', 'integer', 'min:1'],

            'total' => ['required', 'integer', 'min:1', 'max:50'],
            // Le score ne peut pas depasser le nombre de questions posees.
            'score' => ['required', 'integer', 'min:0', 'lte:total'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || $this->input('scope') !== 'chapter') {
                return;
            }

            $book = Book::find($this->integer('book_id'));

            if ($book && ! $book->hasChapter($this->integer('chapter'))) {
                $validator->errors()->add(
                    'chapter',
                    "Le livre {$book->name} compte {$book->chapter_count} chapitres."
                );
            }
        });
    }

    /**
     * Un quiz general ne porte sur aucun chapitre : on force les deux champs a
     * NULL plutot que de faire confiance a ce qu'envoie le client.
     *
     * @return array{scope: string, book_id: int|null, chapter: int|null, score: int, total: int}
     */
    public function attempt(): array
    {
        $isChapter = $this->input('scope') === 'chapter';

        return [
            'scope' => $this->string('scope')->toString(),
            'book_id' => $isChapter ? $this->integer('book_id') : null,
            'chapter' => $isChapter ? $this->integer('chapter') : null,
            'score' => $this->integer('score'),
            'total' => $this->integer('total'),
        ];
    }
}
