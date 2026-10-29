<?php

namespace App\Console\Commands;

use App\Models\ESBTPInscription;
use Illuminate\Console\Command;

class BtsTcLegacyAuditCommand extends Command
{
    protected $signature = 'bts-tc:legacy-audit {--annee= : ID année} {--json : Sortie JSON}';
    protected $description = 'Liste les dossiers legacy BTS TC à double inscription.';

    public function handle(): int
    {
        $items = ESBTPInscription::with(['inscriptionOrigine', 'classe'])
            ->where('type_changement', 'specialisation')
            ->when($this->option('annee'), fn ($query) => $query->where('annee_universitaire_id', (int) $this->option('annee')))
            ->get()
            ->map(fn ($inscription) => [
                'legacy_inscription_id' => $inscription->id,
                'origine_id' => $inscription->inscription_origine_id,
                'classe' => $inscription->classe?->name,
                'compatible' => $inscription->inscriptionOrigine !== null,
            ])
            ->values();

        if ($this->option('json')) {
            $this->line(json_encode(['status' => 'ok', 'items' => $items], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->table(['Legacy', 'Origine', 'Classe', 'Compatible'], $items->map(fn ($item) => array_values($item))->all());

        return self::SUCCESS;
    }
}
