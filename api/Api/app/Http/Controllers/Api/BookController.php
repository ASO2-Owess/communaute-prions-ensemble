<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * GET /api/books
     *
     * Les 66 livres avec leur nombre de chapitres. L'application embarque deja
     * le texte biblique ; elle a seulement besoin de cette structure pour
     * construire sa navigation et connaitre les references valides.
     */
    public function index(): AnonymousResourceCollection
    {
        return BookResource::collection(
            Book::orderBy('position')->get()
        );
    }
}
