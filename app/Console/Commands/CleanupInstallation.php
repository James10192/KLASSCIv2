<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Helpers\InstallationHelper;

class CleanupInstallation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up installation files after installation is complete';

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
     *
     * @return int
     */
    public function handle()
    {
        // Check if the application is installed
        if (!InstallationHelper::isInstalled()) {
            $this->error('The application is not installed yet. Please complete the installation first.');
            return 1;
        }

        $this->info('Starting installation cleanup...');

        // Create backup directory if it doesn't exist
        $backupDir = storage_path('app/installation_backup');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // Files to backup
        $filesToBackup = [
            app_path('Http/Controllers/InstallController.php'),
            app_path('Http/Middleware/CheckInstalled.php'),
            app_path('Helpers/InstallationHelper.php'),
        ];

        // Directories to backup
        $dirsToBackup = [
            resource_path('views/install'),
        ];

        // Backup files
        foreach ($filesToBackup as $file) {
            if (File::exists($file)) {
                $filename = basename($file);
                $this->info("Backing up file: {$filename}");
                
                // Copy file to backup directory
                File::copy($file, $backupDir . '/' . $filename);
                
                // Delete original file
                File::delete($file);
            }
        }

        // Backup directories
        foreach ($dirsToBackup as $dir) {
            if (File::exists($dir)) {
                $dirname = basename($dir);
                $this->info("Backing up directory: {$dirname}");
                
                // Copy directory to backup directory
                File::copyDirectory($dir, $backupDir . '/' . $dirname);
                
                // Delete original directory
                File::deleteDirectory($dir);
            }
        }

        // Update routes file to remove installation routes
        $this->info('Updating routes file...');
        $routesFile = base_path('routes/web.php');
        
        if (File::exists($routesFile)) {
            $content = File::get($routesFile);
            
            // Remove installation routes
            $pattern = '/\/\* Installation Routes \*\/.*?\/\* End Installation Routes \*\//s';
            $content = preg_replace($pattern, '/* Installation Routes have been removed */', $content);
            
            File::put($routesFile, $content);
        }

        $this->info('Installation cleanup completed successfully!');
        $this->info("Backup files are stored in: {$backupDir}");

        return 0;
    }
} 