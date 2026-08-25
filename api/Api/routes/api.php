<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\MeditationController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\Pastor\ContentReviewController;
use App\Http\Controllers\Api\Pastor\QuestionQueueController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuizAttemptController;
use App\Http\Controllers\Api\ReadingController;
use App\Http\Middleware\EnsurePastor;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API — Communaute Prions Ensemble
|--------------------------------------------------------------------------
| Toutes ces routes sont prefixees par /api (configure dans bootstrap/app.php).
|
| Trois zones : ouvert, membre authentifie, equipe pastorale. La separation est
| explicite pour qu'on ne puisse pas exposer une route par distraction — le
| defaut est protege, l'ouverture est un choix.
*/

// ------------------------------------------------------------------ publiques

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1'); // 6 inscriptions par minute et par IP

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1'); // freine les tentatives par force brute

// --------------------------------------------------------------------- membre

Route::middleware('auth:sanctum')->group(function () {

    // Compte et profil
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar'])
        ->middleware('throttle:10,60'); // 10 envois de photo par heure
    Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);

    // Reference biblique
    Route::get('/books', [BookController::class, 'index']);

    // Lectures
    Route::get('/readings', [ReadingController::class, 'index']);
    Route::post('/readings', [ReadingController::class, 'store']);
    Route::delete('/readings', [ReadingController::class, 'destroy']);

    // Meditations (suivi de progression)
    Route::get('/meditations', [MeditationController::class, 'index']);
    Route::post('/meditations', [MeditationController::class, 'store']);

    // Quiz
    Route::post('/quiz-attempts', [QuizAttemptController::class, 'store']);

    // Progression
    Route::get('/progress', [ProgressController::class, 'show']);
    Route::post('/progress/reset-reading', [ProgressController::class, 'resetReading']);

    // Communaute
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

    // Bloc-notes
    Route::get('/notes', [NoteController::class, 'index']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::put('/notes/{note}', [NoteController::class, 'update']);
    Route::delete('/notes/{note}', [NoteController::class, 'destroy']);

    // Questions au pasteur — un membre ne voit que les siennes (QuestionPolicy)
    Route::get('/questions', [QuestionController::class, 'index']);
    Route::get('/questions/{question}', [QuestionController::class, 'show']);
    Route::post('/questions', [QuestionController::class, 'store'])
        ->middleware('throttle:10,60'); // 10 questions par heure maximum

    // Contenus generes par l'IA — la cle reste sur le serveur (ADR-002)
    Route::get('/contents/meditation/{book}/{chapter}', [ContentController::class, 'meditation'])
        ->whereNumber('chapter');
    Route::get('/contents/chapter-quiz/{book}/{chapter}', [ContentController::class, 'chapterQuiz'])
        ->whereNumber('chapter');
    Route::get('/contents/biography/{name}', [ContentController::class, 'biography']);
});

// ------------------------------------------------------------ equipe pastorale

Route::middleware(['auth:sanctum', EnsurePastor::class])
    ->prefix('pastor')
    ->group(function () {

        // File des questions
        Route::get('/stats', [QuestionQueueController::class, 'stats']);
        Route::get('/questions', [QuestionQueueController::class, 'index']);
        Route::get('/questions/{question}', [QuestionQueueController::class, 'show']);
        Route::post('/questions/{question}/claim', [QuestionQueueController::class, 'claim']);
        Route::put('/questions/{question}/answer', [QuestionQueueController::class, 'answer']);
        Route::post('/questions/{question}/publish', [QuestionQueueController::class, 'publish']);

        // Relecture des contenus IA — rien n'est servi avant approbation
        Route::get('/contents', [ContentReviewController::class, 'index']);
        Route::get('/contents/{content}', [ContentReviewController::class, 'show']);
        Route::put('/contents/{content}', [ContentReviewController::class, 'update']);
        Route::post('/contents/{content}/approve', [ContentReviewController::class, 'approve']);
        Route::post('/contents/{content}/reject', [ContentReviewController::class, 'reject']);
    });
