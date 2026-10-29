<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RunQueueWorker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:work:run {--daemon : Run the worker in daemon mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run queue worker with optimized settings for notifications';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting queue worker for ESBTP notification system...');

        $command = 'queue:work database --sleep=3 --tries=3';

        if (!$this->option('daemon')) {
            $this->info('Running in foreground mode. Press Ctrl+C to stop.');
            $this->info('To run in background, use --daemon option.');
            Artisan::call($command);
        } else {
            $this->info('Running in daemon mode. Process will continue in background.');
            $this->info('To check status, use: php artisan queue:status');
            $this->info('To stop workers, use: php artisan queue:restart');

            // Run in background
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows
                pclose(popen('start /B php artisan ' . $command . ' > storage/logs/queue-worker.log', 'r'));
            } else {
                // Unix/Linux
                exec('nohup php artisan ' . $command . ' > storage/logs/queue-worker.log 2>&1 &');
            }
        }

        return Command::SUCCESS;
    }
}
