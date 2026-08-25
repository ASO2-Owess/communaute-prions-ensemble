<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NoteController extends Controller
{
    /** GET /api/notes — les siennes, filtrables par passage. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $notes = Note::where('user_id', $request->user()->id)
            ->when($request->integer('book_id'), fn ($q, $id) => $q->where('book_id', $id))
            ->when($request->integer('chapter'), fn ($q, $c) => $q->where('chapter', $c))
            ->with('book:id,name,slug')
            ->latest()
            ->paginate(30);

        return NoteResource::collection($notes);
    }

    /** POST /api/notes */
    public function store(StoreNoteRequest $request): JsonResponse
    {
        $note = Note::create([
            'user_id' => $request->user()->id,
            ...$request->noteData(),
        ]);

        return (new NoteResource($note->load('book:id,name,slug')))
            ->response()
            ->setStatusCode(201);
    }

    /** PUT /api/notes/{note} */
    public function update(StoreNoteRequest $request, Note $note): NoteResource
    {
        $this->authorize('update', $note);

        $note->update($request->noteData());

        return new NoteResource($note->refresh()->load('book:id,name,slug'));
    }

    /** DELETE /api/notes/{note} */
    public function destroy(Request $request, Note $note): JsonResponse
    {
        $this->authorize('delete', $note);

        $note->delete();

        return response()->json(['message' => 'Note supprimee.']);
    }
}
