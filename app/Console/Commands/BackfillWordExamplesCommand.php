<?php

namespace App\Console\Commands;

use App\Services\Examples\BackfillWordExamplesService;
use Illuminate\Console\Command;
use Throwable;

class BackfillWordExamplesCommand extends Command
{
    protected $signature = 'words:backfill-examples
        {--source= : Which words to process: user or ready}
        {--limit=0 : Maximum number of words to process}';

    protected $description = 'Backfill saved word examples for existing user or ready dictionary words.';

    public function handle(BackfillWordExamplesService $service): int
    {
        $source = trim((string) $this->option('source'));
        $limit = max(0, (int) $this->option('limit'));

        if (! in_array($source, ['user', 'ready'], true)) {
            $this->error('Option --source must be either "user" or "ready".');

            return self::FAILURE;
        }

        $this->info("Starting word example backfill for source [{$source}]...");
        if ($limit > 0) {
            $this->line("Processing limit: {$limit}");
        }

        try {
            $stats = $service->backfill($source, $limit);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Backfill completed.');
        $this->line('Processed: '.$stats['processed']);
        $this->line('Enriched: '.$stats['enriched']);
        $this->line('Skipped existing: '.$stats['skipped_existing']);
        $this->line('Skipped unsupported language: '.$stats['skipped_unsupported_language']);
        $this->line('Skipped without examples: '.$stats['skipped_without_examples']);

        return self::SUCCESS;
    }
}
