<?php

namespace App\Console\Commands;

use App\Services\RendezVous\FermetureAutomatiqueCreneauxRdv;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FermerCreneauxRdvDuJour extends Command
{
    protected $signature = 'inscriptions:fermer-creneaux-rdv-du-jour {--date= : Date AAAA-MM-JJ, uniquement pour maintenance/test}';

    protected $description = 'Ferme les créneaux de rendez-vous du jour quand le réglage de fermeture à minuit est actif';

    public function handle(FermetureAutomatiqueCreneauxRdv $fermeture): int
    {
        $jour = Carbon::today();
        if (is_string($this->option('date')) && trim((string) $this->option('date')) !== '') {
            try {
                $jour = Carbon::createFromFormat('Y-m-d', trim((string) $this->option('date')))->startOfDay();
            } catch (\Throwable) {
                $this->error('La date doit être au format AAAA-MM-JJ.');
                return self::INVALID;
            }
        }

        if (! $fermeture->active()) {
            $this->info('Fermeture automatique désactivée : aucun créneau modifié.');
            return self::SUCCESS;
        }

        $n = $fermeture->fermer($jour);
        $this->info(sprintf('%d créneau(x) fermé(s) pour le %s. Les réservations existantes sont conservées.', $n, $jour->toDateString()));

        return self::SUCCESS;
    }
}
