<?php

namespace App\Console\Commands;

use App\Services\RendezVous\EtatChaineRdv;
use Illuminate\Console\Command;

class DiagnostiquerRdv extends Command
{
    protected $signature = 'inscriptions:diagnostiquer-rdv {--json : sortie JSON}';

    protected $description = 'Dit, maillon par maillon, ce qui empeche une famille de reserver ou de recevoir sa convocation.';

    public function handle(EtatChaineRdv $etat): int
    {
        $maillons = $etat->maillons();
        $convocations = $etat->convocations();

        if ($this->option('json')) {
            $this->line((string) json_encode(['maillons' => $maillons, 'convocations' => $convocations], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            foreach ($maillons as $m) {
                $this->line(($m['ok'] ? '<info>  OK </info> ' : '<error> KO </error> ').$m['titre'].' — '.$m['detail']);
            }
            $this->newLine();
            $this->table(array_keys($convocations), [array_values($convocations)]);
        }

        return $etat->toutEstEnOrdre() ? self::SUCCESS : self::FAILURE;
    }
}
