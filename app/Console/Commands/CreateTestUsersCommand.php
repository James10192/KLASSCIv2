<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\TestUsersSeeder;

class CreateTestUsersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'esbtp:create-test-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test users for all roles (superAdmin, secretaire, etudiant, teacher)';

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
        $this->info('Creating test users for ESBTP-yAKRO...');
        
        // Run the TestUsersSeeder
        $seeder = new TestUsersSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Test users created successfully!');
        $this->info('You can now login with the following credentials:');
        $this->table(
            ['Role', 'Email', 'Password'],
            [
                ['SuperAdmin', 'superadmin@esbtp.ci', 'password123'],
                ['Secretary', 'secretaire@esbtp.ci', 'password123'],
                ['Student', 'etudiant@esbtp.ci', 'password123'],
                ['Teacher', 'teacher@esbtp.ci', 'password123'],
            ]
        );
        
        return 0;
    }
} 