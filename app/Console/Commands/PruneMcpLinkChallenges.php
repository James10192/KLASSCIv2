<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ne jamais conserver indéfiniment l'empreinte d'une tentative de liaison.
 * La suppression est locale à l'établissement et bornée par passage.
 */
class PruneMcpLinkChallenges extends Command
{
    protected $signature = 'nanan:mcp-prune-challenges {--limit=1000}';

    protected $description = 'Supprime progressivement les preuves MCP expirées depuis plus de 24 heures';

    public function handle(): int
    {
        if (! Schema::hasTable('mcp_link_challenges')) {
            return self::SUCCESS;
        }

        $limit = max(1, min(1000, (int) $this->option('limit')));
        $ids = DB::table('mcp_link_challenges')
            ->where('expires_at', '<', now()->subDay())
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('mcp_link_challenges')->whereIn('id', $ids)->delete();
        }

        $this->info('Preuves temporaires supprimées : '.$ids->count());

        return self::SUCCESS;
    }
}
