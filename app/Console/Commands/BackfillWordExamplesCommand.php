<?php

namespace App\Console\Commands;

use App\Services\Examples\BackfillWordExamplesService;
use Illuminate\Console\Command;
use Throwable;

class BackfillWordExamplesCommand extends Command
{
    protected $signature = 'words:backfill-examples
        {--source= : Which words to process: user or ready}
        {--id= : Process only one dictionary id}
        {--clear : Clear saved examples before backfill}
        {--limit=0 : Maximum number of words to process}';

    protected $description = 'Backfill saved word examples for existing user or ready dictionary words.';

    public function handle(BackfillWordExamplesService $service): int
    {
        $source = trim((string) $this->option('source'));
        $dictionaryId = $this->option('id');
        $clear = (bool) $this->option('clear');
        $limit = max(0, (int) $this->option('limit'));

        if ($source === '' && ($dictionaryId !== null && $dictionaryId !== '' || $clear)) {
            $this->error('Option --source is required when using --id or --clear.');

            return self::FAILURE;
        }

        if (! in_array($source, ['user', 'ready'], true)) {
            $this->error('Option --source must be either "user" or "ready".');

            return self::FAILURE;
        }

        $dictionaryId = $dictionaryId !== null && $dictionaryId !== ''
            ? (int) $dictionaryId
            : null;

        if ($dictionaryId !== null && $dictionaryId <= 0) {
            $this->error('Option --id must be a positive integer.');

            return self::FAILURE;
        }

        if ($clear && $dictionaryId === null) {
            $this->error('Option --clear requires both --source and --id.');

            return self::FAILURE;
        }

        $modeLabel = $clear ? 'clear' : 'backfill';

        $this->info("Starting word example {$modeLabel} for source [{$source}]...");
        if ($dictionaryId !== null) {
            $this->line("Dictionary id: {$dictionaryId}");
        }
        if ($clear) {
            $this->line('Clear mode: enabled');
        }
        if ($limit > 0) {
            $this->line("Processing limit: {$limit}");
        }

        try {
            $stats = $clear
                ? $service->clear($source, $dictionaryId, function (string $currentSource, string $word, int $processed): void {
                    $this->line("[{$processed}] {$currentSource}: {$word}");
                })
                : $service->backfill($source, $limit, $dictionaryId, function (string $currentSource, string $word, int $processed): void {
                    $this->line("[{$processed}] {$currentSource}: {$word}");
                });
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(ucfirst($modeLabel).' completed.');
        $this->line('Processed: '.$stats['processed']);
        $this->line('Enriched: '.$stats['enriched']);
        $this->line('Cleared: '.$stats['cleared']);
        $this->line('Skipped existing: '.$stats['skipped_existing']);
        $this->line('Skipped unsupported language: '.$stats['skipped_unsupported_language']);
        $this->line('Skipped without examples: '.$stats['skipped_without_examples']);

        return self::SUCCESS;
    }
}
