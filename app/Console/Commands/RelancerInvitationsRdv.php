<?php

namespace App\Console\Commands;

use App\Services\RendezVous\MessagerieRdv;
use Illuminate\Console\Command;

class RelancerInvitationsRdv extends Command
{
    protected $signature = 'inscriptions:relancer-rdv {--apply : Envoyer réellement les mails}';

    protected $description = 'Invite par email les dossiers en attente sans rendez-vous.';

    public function handle(MessagerieRdv $mails): int
    {
        $ecrire = (bool) $this->option('apply');
        $rapport = $mails->inviterEnAttente($ecrire);

        $this->info(sprintf(
            'Envoyés : %d · sans email : %d · déjà relancés : %d%s',
            $rapport['envoyes'],
            $rapport['sans_email'],
            $rapport['deja'],
            $ecrire ? '' : ' (simulation)'
        ));

        return self::SUCCESS;
    }
}
