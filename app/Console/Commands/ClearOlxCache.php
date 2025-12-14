<?php

namespace App\Console\Commands;

use App\Services\OlxApiService;
use Illuminate\Console\Command;

class ClearOlxCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'olx:clear-cache
                            {--force : Force cache clearing without confirmation}
                            {--stats : Show cache statistics before clearing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all OLX API cached data (categories and fields)';

    public function __construct(
        private readonly OlxApiService $olxApiService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('OLX API Cache Management');
        $this->newLine();

        if ($this->option('stats')) {
            $this->displayCacheStats();
            $this->newLine();
        }

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to clear all OLX API cache?', false)) {
                $this->warn('Cache clearing cancelled.');
                return Command::FAILURE;
            }
        }

        $this->info('Clearing OLX API cache...');

        try {
            $success = $this->olxApiService->clearCache();

            if ($success) {
                $this->components->success('OLX API cache cleared successfully!');
                $this->info('Next API call will fetch fresh data from OLX.');
                return Command::SUCCESS;
            } else {
                $this->components->error('Failed to clear OLX API cache.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->components->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function displayCacheStats(): void
    {
        $this->info('Current Cache Statistics:');

        try {
            $stats = $this->olxApiService->getCacheStats();

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Cache Driver', $stats['cache_driver']],
                    ['Cache TTL', $stats['cache_ttl_hours'] . ' hours'],
                    ['Categories Cached', $stats['categories_cached'] ? 'Yes' : 'No'],
                    ['Cache Tag', $stats['cache_tag']],
                ]
            );
        } catch (\Exception $e) {
            $this->warn('Could not retrieve cache stats: ' . $e->getMessage());
        }
    }
}
