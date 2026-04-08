<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Carbon\Carbon;

class KlassciCreateTokenCommand extends Command
{
    protected $signature = 'klassci:create-token
                           {--user= : User ID or email to create token for}
                           {--name=klassci-cli : Token name}
                           {--abilities=cli:read : Comma-separated abilities (cli:read, cli:write, cli:admin)}
                           {--expires= : Expiration in days (default: never)}';

    protected $description = 'Generate a Sanctum API token for KLASSCI CLI access';

    protected array $validAbilities = ['cli:read', 'cli:write', 'cli:admin'];

    public function handle(): int
    {
        $this->info('KLASSCI CLI Token Generator');
        $this->info('==========================');

        // 1. Find user
        $user = $this->resolveUser();
        if (!$user) {
            return self::FAILURE;
        }

        // 2. Parse and validate abilities
        $abilities = $this->resolveAbilities();
        if ($abilities === null) {
            return self::FAILURE;
        }

        // 3. Parse expiration
        $expiration = $this->resolveExpiration();

        // 4. Confirm before creating
        $this->info("\nToken summary:");
        $this->table(
            ['Field', 'Value'],
            [
                ['User', "{$user->name} ({$user->email})"],
                ['Token name', $this->option('name')],
                ['Abilities', implode(', ', $abilities)],
                ['Expires', $expiration ? $expiration->format('Y-m-d H:i:s') : 'Never'],
            ]
        );

        if (!$this->confirm('Create this token?', true)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        // 5. Create the token
        $token = $user->createToken(
            $this->option('name'),
            $abilities,
            $expiration
        );

        $plainTextToken = $token->plainTextToken;

        // 6. Display the token prominently
        $this->newLine();
        $this->warn('================================================================');
        $this->warn('  YOUR API TOKEN (save it now, it cannot be retrieved later):');
        $this->warn('================================================================');
        $this->newLine();
        $this->line("  <fg=green;options=bold>{$plainTextToken}</>");
        $this->newLine();
        $this->warn('================================================================');

        // 7. Usage examples
        $this->newLine();
        $this->info('Usage examples:');
        $this->line('  klassci config set-token <tenant> ' . $plainTextToken);
        $this->newLine();
        $this->line('  curl -H "Authorization: Bearer ' . $plainTextToken . '" \\');
        $this->line('       https://tenant.klassci.com/api/cli/stats');

        return self::SUCCESS;
    }

    protected function resolveUser(): ?User
    {
        $userOption = $this->option('user');

        if ($userOption) {
            // Search by ID or email
            $user = User::where('id', $userOption)
                ->orWhere('email', $userOption)
                ->first();

            if (!$user) {
                $this->error("User not found: {$userOption}");
                $this->info("Provide a valid user ID or email address.");
                return null;
            }

            $this->info("User found: {$user->name} ({$user->email})");
            return $user;
        }

        // Interactive: show admin users to pick from
        $this->info("\nNo --user provided. Showing admin users:");

        $adminUsers = User::role(['superAdmin', 'secretaire'])->get(['id', 'name', 'email']);

        if ($adminUsers->isEmpty()) {
            $this->error('No superAdmin or secretaire users found.');
            $this->info('Create a token for a specific user with: --user=<id or email>');
            return null;
        }

        $rows = $adminUsers->map(function ($u) {
            $roles = $u->getRoleNames()->implode(', ');
            return [$u->id, $u->name, $u->email, $roles];
        })->toArray();

        $this->table(['ID', 'Name', 'Email', 'Roles'], $rows);

        $selectedEmail = $this->choice(
            'Select a user (by email)',
            $adminUsers->pluck('email')->toArray()
        );

        $user = User::where('email', $selectedEmail)->first();

        if (!$user) {
            $this->error("User not found: {$selectedEmail}");
            return null;
        }

        return $user;
    }

    protected function resolveAbilities(): ?array
    {
        $raw = $this->option('abilities');
        $abilities = array_map('trim', explode(',', $raw));

        // Validate each ability
        foreach ($abilities as $ability) {
            if (!in_array($ability, $this->validAbilities)) {
                $this->error("Invalid ability: {$ability}");
                $this->info('Valid abilities: ' . implode(', ', $this->validAbilities));
                return null;
            }
        }

        // cli:admin implies cli:read + cli:write
        if (in_array('cli:admin', $abilities)) {
            $abilities = array_unique(array_merge($abilities, ['cli:read', 'cli:write']));
            $this->info('cli:admin detected: automatically including cli:read and cli:write');
        }

        return array_values($abilities);
    }

    protected function resolveExpiration(): ?Carbon
    {
        $days = $this->option('expires');

        if (!$days) {
            return null;
        }

        if (!is_numeric($days) || (int) $days < 1) {
            $this->warn("Invalid expiration value '{$days}', token will not expire.");
            return null;
        }

        return Carbon::now()->addDays((int) $days);
    }
}
