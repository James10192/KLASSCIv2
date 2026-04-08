<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class KlassciTokensCommand extends Command
{
    protected $signature = 'klassci:tokens
                           {action=list : Action: list or revoke}
                           {--user= : Filter by user ID or email}
                           {--id= : Token ID to revoke}';

    protected $description = 'List or revoke KLASSCI CLI tokens';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listTokens(),
            'revoke' => $this->revokeToken(),
            default => $this->invalidAction($action),
        };
    }

    protected function listTokens(): int
    {
        $this->info('KLASSCI CLI Tokens');
        $this->info('==================');

        $query = PersonalAccessToken::query()
            ->where(function ($q) {
                // Tokens named klassci-cli* or having cli:* abilities
                $q->where('name', 'like', 'klassci-cli%')
                    ->orWhere('abilities', 'like', '%cli:%');
            });

        // Filter by user if provided
        $user = $this->resolveUserFilter();
        if ($user === false) {
            return self::FAILURE;
        }
        if ($user) {
            $query->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id);
        }

        $tokens = $query->orderByDesc('created_at')->get();

        if ($tokens->isEmpty()) {
            $this->warn('No CLI tokens found.');
            $this->info('Create one with: php artisan klassci:create-token');
            return self::SUCCESS;
        }

        $rows = $tokens->map(function ($token) {
            $abilities = is_array($token->abilities)
                ? $token->abilities
                : json_decode($token->abilities, true) ?? [];

            $tokenable = $token->tokenable;
            $userName = $tokenable ? "{$tokenable->name} ({$tokenable->email})" : "Unknown (ID: {$token->tokenable_id})";

            return [
                $token->id,
                $userName,
                $token->name,
                implode(', ', $abilities),
                $token->last_used_at ? $token->last_used_at->format('Y-m-d H:i') : 'Never',
                $token->expires_at ? $token->expires_at->format('Y-m-d H:i') : 'Never',
                $token->created_at->format('Y-m-d H:i'),
            ];
        })->toArray();

        $this->table(
            ['ID', 'User', 'Name', 'Abilities', 'Last Used', 'Expires', 'Created'],
            $rows
        );

        $this->info("Total: {$tokens->count()} token(s)");

        return self::SUCCESS;
    }

    protected function revokeToken(): int
    {
        $tokenId = $this->option('id');

        if (!$tokenId) {
            $this->error('Please provide a token ID with --id=<token_id>');
            $this->info('List tokens first: php artisan klassci:tokens list');
            return self::FAILURE;
        }

        $token = PersonalAccessToken::find($tokenId);

        if (!$token) {
            $this->error("Token not found with ID: {$tokenId}");
            return self::FAILURE;
        }

        // Show token info before revoking
        $tokenable = $token->tokenable;
        $userName = $tokenable ? "{$tokenable->name} ({$tokenable->email})" : "Unknown";
        $abilities = is_array($token->abilities)
            ? $token->abilities
            : json_decode($token->abilities, true) ?? [];

        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $token->id],
                ['User', $userName],
                ['Name', $token->name],
                ['Abilities', implode(', ', $abilities)],
                ['Created', $token->created_at->format('Y-m-d H:i:s')],
                ['Last Used', $token->last_used_at ? $token->last_used_at->format('Y-m-d H:i:s') : 'Never'],
            ]
        );

        if (!$this->confirm('Revoke this token? This action is irreversible.', false)) {
            $this->info('Cancelled.');
            return self::SUCCESS;
        }

        $token->delete();

        $this->info("Token #{$tokenId} ({$token->name}) has been revoked.");

        return self::SUCCESS;
    }

    /**
     * @return User|null|false  User if found, null if no filter, false on error
     */
    protected function resolveUserFilter(): User|null|false
    {
        $userOption = $this->option('user');

        if (!$userOption) {
            return null;
        }

        $user = User::where('id', $userOption)
            ->orWhere('email', $userOption)
            ->first();

        if (!$user) {
            $this->error("User not found: {$userOption}");
            return false;
        }

        $this->info("Filtering by user: {$user->name} ({$user->email})");
        return $user;
    }

    protected function invalidAction(string $action): int
    {
        $this->error("Invalid action: {$action}");
        $this->info('Available actions: list, revoke');
        $this->newLine();
        $this->info('Examples:');
        $this->line('  php artisan klassci:tokens list');
        $this->line('  php artisan klassci:tokens list --user=admin@klassci.com');
        $this->line('  php artisan klassci:tokens revoke --id=5');

        return self::FAILURE;
    }
}
