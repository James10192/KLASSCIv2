<?php

namespace App\Console\Commands;

use App\Services\Reinscription\DiagnosticPortailReinscription;
use Illuminate\Console\Command;

/**
 * « La famille dit que le portail ne trouve pas son dossier. Pourquoi ? »
 *
 * Le portail public refuse de repondre a cette question, et il a raison de le
 * faire : sa reponse uniforme est ce qui empeche d'enumerer les matricules de
 * l'ecole. Cette commande est le pendant interne — elle repond, mais seulement
 * a qui a deja un acces au serveur.
 *
 * Lecture seule : elle ne corrige rien et ne depose aucune demande.
 */
class ReinscriptionPortailDiagnoseCommand extends Command
{
    protected $signature = 'reinscription:portail-diagnose
        {matricule : Le matricule tel que la famille le saisit}
        {--date= : Date de naissance saisie, au format AAAA-MM-JJ}
        {--json : Sortie lisible par machine}';

    protected $description = "Pourquoi le portail public repond « aucun dossier » pour ce matricule.";

    public function handle(DiagnosticPortailReinscription $diagnostic): int
    {
        $date = $this->option('date');

        if ($date !== null && ! $this->dateLisible($date)) {
            $this->components->error('La date doit s\'ecrire AAAA-MM-JJ (ex. 2007-09-15), comme le portail l\'exige.');

            return self::FAILURE;
        }

        $rapport = $diagnostic->pour($this->argument('matricule'), $date);

        if ($this->option('json')) {
            $this->line(json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->rendre($rapport);

        // Un dossier introuvable n'est pas une panne de la commande : elle a
        // fait son travail. Le code de sortie reste 0 pour que l'appelant
        // distingue « le diagnostic a echoue » de « le diagnostic est negatif ».
        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $rapport
     */
    private function rendre(array $rapport): void
    {
        $this->newLine();
        $this->components->info('Portail de reinscription — diagnostic');

        $canal = $rapport['canal'];
        $this->components->twoColumnDetail('Canal', $canal['ouvert'] ? '<fg=green>ouvert</>' : '<fg=yellow>ferme</>');
        $this->components->twoColumnDetail('  Interrupteur', $canal['interrupteur'] ? 'active' : 'desactive');
        $this->components->twoColumnDetail('  Fenetre de dates', $canal['fenetre_ouverte'] ? 'dans la saison' : 'hors saison');

        if (isset($rapport['annee_cible'])) {
            $annee = $rapport['annee_cible'];
            $this->components->twoColumnDetail('Annee visee', (string) $annee['libelle']);
            $this->components->twoColumnDetail('  Origine', (string) $annee['source']);
            $this->components->twoColumnDetail(
                '  Date de debut',
                $annee['start_date'] ?? '<fg=red>absente</>',
            );
        }

        $this->components->twoColumnDetail('Matricule saisi', (string) $rapport['matricule_demande']);
        $this->components->twoColumnDetail('Date saisie', $rapport['date_naissance_fournie'] ?? '(non fournie)');

        if (isset($rapport['etudiant'])) {
            $etudiant = $rapport['etudiant'];
            $this->components->twoColumnDetail(
                'Dossier',
                trim(($etudiant['nom'] ?? '').' '.($etudiant['prenoms'] ?? '')).' (#'.$etudiant['id'].')',
            );
            $this->components->twoColumnDetail('  Date en base', $etudiant['date_naissance'] ?? '<fg=red>absente</>');
        }

        if (! empty($rapport['matricules_proches'])) {
            $this->components->twoColumnDetail(
                'Matricules proches',
                implode(', ', $rapport['matricules_proches']),
            );
        }

        if (! empty($rapport['inscriptions'])) {
            $this->newLine();
            $this->table(
                ['Annee', 'Debut de l\'annee', 'Anteriorite', 'Statut', 'Classe'],
                array_map(static fn (array $ligne) => [
                    $ligne['annee'] ?? '?',
                    $ligne['annee_start_date'] ?? '— absente —',
                    $ligne['anteriorite_utilisable'] ? 'utilisable' : 'IGNOREE',
                    $ligne['statut'] ?? '?',
                    $ligne['classe'] ?? '',
                ], $rapport['inscriptions']),
            );
        }

        $this->newLine();

        if ($rapport['trouve']) {
            $this->components->info(
                $rapport['eligible']
                    ? 'Le portail TROUVE ce dossier et accepte le depot.'
                    : 'Le portail TROUVE ce dossier mais refuse le depot.',
            );
        } else {
            $this->components->error('Le portail repond « aucun dossier ne correspond ».');
            $this->components->twoColumnDetail('Cause', (string) $rapport['cause']);
        }

        if (! empty($rapport['explication'])) {
            $this->line('  '.$rapport['explication']);
        }

        if (! empty($rapport['action'])) {
            $this->newLine();
            $this->components->twoColumnDetail('<fg=cyan>A faire</>', (string) $rapport['action']);
        }

        if (! empty($canal['note'])) {
            $this->newLine();
            $this->warn('  '.$canal['note']);
        }

        $this->newLine();
    }

    /**
     * Meme severite que PortailRequest, et pour la meme raison : accepter ici
     * un format que le portail refuse ferait diagnostiquer autre chose que ce
     * que la famille a vecu.
     */
    private function dateLisible(string $valeur): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);

        return $date !== false && $date->format('Y-m-d') === $valeur;
    }
}
