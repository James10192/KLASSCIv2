<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Passe courte, bornée, compatible cron cPanel et sans daemon.
 * Deux tables propres à chaque établissement, aucune données en clair.
 */
class PruneMcpLinkChallenges extends Command
{
    protected $signature = 'nanan:mcp-prune-challenges {--limit=1000}';

    protected $description = 'Nettoie les preuves de liaison et les nonces MCP anti-rejeu expirés';

    public function handle(): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));
        $challenges = 0;
        $nonces = 0;

        if (Schema::hasTable('mcp_link_challenges')) {
            $ids = DB::table('mcp_link_challenges')
                ->where('expires_at', '<', now()->subDay())
                ->orderBy('id')
                ->limit($limit)
                ->pluck('id');
            if ($ids->isNotEmpty()) {
                $challenges = DB::table('mcp_link_challenges')
                    ->whereIn('id', $ids)->delete();
            }
        }

        // Les signatures expirent en 30 secondes et les preuves nonce
        // durent 120 secondes. Conserver une marge de dix minutes évite
        // une suppression trop précoce même si l'horloge dérive.
        if (Schema::hasTable('mcp_bridge_nonces')) {
            $hashes = DB::table('mcp_bridge_nonces')
                ->where('expires_at', '<', now()->subMinutes(10))
                ->orderBy('expires_at')
                ->limit($limit)
                ->pluck('nonce_hash');
            if ($hashes->isNotEmpty()) {
                $nonces = DB::table('mcp_bridge_nonces')
                    ->whereIn('nonce_hash', $hashes)->delete();
            }
        }

        $this->info("Nettoyage Nanan : {$challenges} preuves, {$nonces} nonces.");

        return self::SUCCESS;
    }
}
