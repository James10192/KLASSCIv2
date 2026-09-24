<?php

namespace App\Console\Commands;

use App\Enums\CanalVerification;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPVerificationContact;
use App\Services\Verification\ContactDeVerification;
use App\Services\Verification\DemarrageVerification;
use App\Services\MailPulse\RefusMailPulse;
use App\Services\Verification\MasqueContact;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Les demandes en attente deposees AVANT la verification, sans e-mail
 * joignable : qui recevrait un code WhatsApp.
 *
 * Simulation par defaut. `--execute` envoie reellement un message a chaque
 * famille : a ne lancer qu'avec l'accord de l'ecole. La demande reste visible
 * pendant et apres (la verification ne fait que dater le telephone).
 */
class VerifierFamillesSansEmail extends Command
{
    private const LIMITE_MAX = 20;

    protected $signature = 'inscriptions:verifier-familles-sans-email
        {--execute : envoie reellement les codes WhatsApp}
        {--limite=20 : nombre maximal de familles par passage (20 au plus, pour rester sous le debit WhatsApp de MailPulse)}';

    protected $description = 'Liste les familles en attente sans e-mail joignable qui recevraient une verification WhatsApp ; --execute l\'envoie.';

    public function handle(ContactDeVerification $contacts, DemarrageVerification $demarrage): int
    {
        if (! $demarrage->active()) {
            $this->warn('La vérification du contact est désactivée dans les réglages (Inscriptions) : rien à faire.');

            return self::SUCCESS;
        }

        $limite = min(self::LIMITE_MAX, max(1, (int) $this->option('limite')));
        $cibles = [];

        foreach ([ESBTPCandidature::class, ESBTPReinscriptionDemande::class] as $modele) {
            $modele::query()->where('statut', 'en_attente')->whereNull('verification_contact')
                ->when($modele === ESBTPReinscriptionDemande::class, fn ($q) => $q->with('etudiant'))
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('esbtp_verifications_contact as v')
                    ->whereColumn('v.verifiable_id', (new $modele)->qualifyColumn('id'))
                    ->where('v.verifiable_type', (new $modele)->getMorphClass()))
                ->orderBy('id')
                ->each(function (Model $demande) use ($contacts, &$cibles) {
                    $contact = $contacts->pour($demande);
                    if ($contact !== null && $contact['canal'] === CanalVerification::Telephone) {
                        $cibles[] = [$demande, $contact['destination']];
                    }
                });
        }

        $cibles = array_slice($cibles, 0, $limite);

        $this->table(['Type', 'Id', 'WhatsApp'], array_map(fn ($c) => [$c[0]->typeDemandePublique(), $c[0]->getKey(), MasqueContact::telephone($c[1])], $cibles));
        $this->info(count($cibles).' famille(s) recevrai(en)t un code WhatsApp.');

        if (! $this->option('execute')) {
            $this->comment('Simulation : aucun message envoyé. --execute pour envoyer (accord de l\'école requis).');

            return self::SUCCESS;
        }

        $envoyes = 0;
        foreach ($cibles as [$demande]) {
            if ($demarrage->demarrer($demande, false) !== null && $demarrage->dernierRefus === null) {
                $envoyes++;

                continue;
            }
            // Debit ou configuration : les suivants echoueraient pareil. On s'arrete.
            if (in_array($demarrage->dernierRefus, ['rate_limited', 'trop_de_demandes'], true) || RefusMailPulse::bloquant($demarrage->dernierRefus)) {
                $this->warn('Arrêt : '.$demarrage->dernierRefus.'. Relancer plus tard.');
                break;
            }
        }
        $this->info($envoyes.' code(s) envoyé(s).');

        return self::SUCCESS;
    }
}
