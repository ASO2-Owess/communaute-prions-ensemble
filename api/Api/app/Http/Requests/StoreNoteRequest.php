<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:5000'],

            // Une note libre n'est rattachee a aucun passage : les trois
            // references sont facultatives, mais chapter exige book_id.
            'book_id' => ['nullable', 'integer', 'exists:books,id'],
            'chapter' => ['nullable', 'integer', 'min:1', 'required_with:verse'],
            'verse' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->filled('chapter') && ! $this->filled('book_id')) {
                $validator->errors()->add('book_id', 'Precise le livre avec le chapitre.');

                return;
            }

            if ($this->filled('book_id') && $this->filled('chapter')) {
                $book = Book::find($this->integer('book_id'));

                if ($book && ! $book->hasChapter($this->integer('chapter'))) {
                    $validator->errors()->add(
                        'chapter',
                        "Le livre {$book->name} compte {$book->chapter_count} chapitres."
                    );
                }
            }
        });
    }

    /** @return array{content: string, book_id: int|null, chapter: int|null, verse: int|null} */
    public function noteData(): array
    {
        return [
            'content' => $this->string('content')->toString(),
            'book_id' => $this->filled('book_id') ? $this->integer('book_id') : null,
            'chapter' => $this->filled('chapter') ? $this->integer('chapter') : null,
            'verse' => $this->filled('verse') ? $this->integer('verse') : null,
        ];
    }
}
