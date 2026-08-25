<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\GeneratedContent;
use App\Services\AiRelayService;
use App\Services\BibleTextService;
use App\Support\Prompts;
use Illuminate\Console\Command;

/**
 * Pre-genere des contenus pour la file de relecture.
 *
 *   php artisan ai:pregenerate --book=43 --from=1 --to=5
 *   php artisan ai:pregenerate --book=43 --kind=chapter_quiz
 *
 * Pourquoi cette commande existe : la validation humaine impose que le premier
 * membre a demander une meditation inedite ne la recoive pas immediatement. En
 * pre-generant a l'avance les chapitres les plus lus, on fait en sorte que le
 * cas courant soit deja approuve — l'attente devient l'exception plutot que la
 * regle.
 */
class PregenerateContent extends Command
{
    protected $signature = 'ai:pregenerate
        {--book= : Identifiant du livre (1 a 66)}
        {--from=1 : Premier chapitre}
        {--to= : Dernier chapitre (defaut : dernier du livre)}
        {--kind=meditation : meditation ou chapter_quiz}
        {--force : Regenerer meme si un contenu existe deja}';

    protected $description = 'Genere des contenus IA et les place en file de relecture';

    public function handle(AiRelayService $ai, BibleTextService $bible): int
    {
        $kind = $this->option('kind');

        if (! in_array($kind, [GeneratedContent::KIND_MEDITATION, GeneratedContent::KIND_CHAPTER_QUIZ], true)) {
            $this->error('--kind doit valoir meditation ou chapter_quiz.');

            return self::FAILURE;
        }

        $book = Book::find($this->option('book'));

        if (! $book) {
            $this->error('Precise un livre existant : --book=43 pour Jean.');

            return self::FAILURE;
        }

        $from = max(1, (int) $this->option('from'));
        $to = min((int) ($this->option('to') ?: $book->chapter_count), $book->chapter_count);

        if ($from > $to) {
            $this->error('Intervalle de chapitres invalide.');

            return self::FAILURE;
        }

        $this->info("Generation ({$kind}) : {$book->name} {$from} a {$to}");
        $bar = $this->output->createProgressBar($to - $from + 1);
        $bar->start();

        $created = $skipped = $failed = 0;

        for ($chapter = $from; $chapter <= $to; $chapter++) {
            $reference = GeneratedContent::chapterReference($book->id, $chapter);

            $exists = GeneratedContent::where('kind', $kind)
                ->where('reference', $reference)
                ->exists();

            if ($exists && ! $this->option('force')) {
                $skipped++;
                $bar->advance();

                continue;
            }

            if (! $bible->hasText($book, $chapter)) {
                $failed++;
                $bar->advance();

                continue;
            }

            $texte = $bible->chapterText($book, $chapter);

            $prompt = $kind === GeneratedContent::KIND_MEDITATION
                ? Prompts::meditation($book->name, $chapter, $texte)
                : Prompts::chapterQuiz($book->name, $chapter, $texte);

            $ai->generateForReview($kind, $reference, $prompt) ? $created++ : $failed++;

            $bar->advance();

            // Respiration entre deux appels : evite de saturer l'API.
            usleep(500_000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Generes : {$created} | Deja presents : {$skipped} | Echecs : {$failed}");
        $this->comment('Ces contenus sont en attente de relecture (/api/pastor/contents).');

        return self::SUCCESS;
    }
}
