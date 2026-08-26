<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Sert aussi bien aux lectures qu'aux meditations : meme couple
 * (livre, chapitre), meme validation.
 */
class RecordReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la route est deja protegee par auth:sanctum
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'chapter' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * `exists:books,id` verifie que le livre existe, mais rien ne verifie
     * encore que le chapitre existe DANS ce livre. C'est tout l'interet
     * d'avoir chapter_count dans la table de reference.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return; // book_id ou chapter deja invalide
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

    public function book(): Book
    {
        return Book::findOrFail($this->integer('book_id'));
    }
}
