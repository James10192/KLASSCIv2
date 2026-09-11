<?php

namespace App\Console\Commands;

use App\Domain\Students\DiagnosticStatutAffectation;
use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Console\Command;

/**
 * Affiche le diagnostic des statuts d'affectation ecrases.
 *
 * Toute la lecture vit dans DiagnosticStatutAffectation ; cette commande ne
 * fait que choisir l'annee et rendre le rapport. Elle ne modifie rien.
 *
 * Usage : php artisan affectation:diagnose [--annee=ID] [--limite=200] [--json]
 */
class AffectationDiagnoseCommand extends Command
{
    protected $signature = 'affectation:diagnose
                            {--annee= : ID de l\'année universitaire (par défaut : courante)}
                            {--limite=200 : Nombre de cas détaillés listés (le total, lui, porte sur tout)}
                            {--json : Sortie JSON brute}';

    protected $description = "Recense les inscriptions dont le statut d'affectation a pu être écrasé, et ce que cela coûte";

    public function handle(DiagnosticStatutAffectation $diagnostic): int
    {
        $annee = $this->anneeCible();
        if (! $annee) {
            $this->error("Aucune année universitaire courante : préciser --annee=ID.");

            return self::FAILURE;
        }

        $rapport = $diagnostic->pour($annee, (int) $this->option('limite'));

        if ($this->option('json')) {
            $this->line(json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->afficher($rapport);

        return self::SUCCESS;
    }

    private function anneeCible(): ?ESBTPAnneeUniversitaire
    {
        return $this->option('annee')
            ? ESBTPAnneeUniversitaire::find((int) $this->option('annee'))
            : ESBTPAnneeUniversitaire::where('is_current', true)->first();
    }

    private function afficher(array $rapport): void
    {
        $this->info("Statut d'affectation — année {$rapport['annee']['nom']}");
        $this->newLine();

        $this->line('Répartition des inscriptions :');
        foreach ($rapport['repartition'] as $statut => $total) {
            $this->line(sprintf('  %-14s %d', $statut ?: '(vide)', $total));
        }
        $this->newLine();

        foreach ([
            'retournements' => "Statut retourné vers « affecté » d'une année sur l'autre",
            'specialisations' => 'Spécialisation en désaccord avec son tronc commun',
        ] as $cle => $titre) {
            $bloc = $rapport[$cle];
            $this->line($titre.' : '.$bloc['total'].' cas, '
                .number_format($bloc['manque_a_gagner_fcfa'], 0, ',', ' ').' FCFA non réclamés');

            if ($bloc['cas']) {
                $this->table(
                    ['Inscription', 'Matricule', 'Étudiant', 'Classe', 'Avant', 'Après', 'Écart FCFA'],
                    array_map(fn ($c) => [
                        $c['inscription_id'],
                        $c['matricule'],
                        mb_substr($c['etudiant'], 0, 28),
                        $c['classe'],
                        $c['statut_precedent'] ?? $c['statut_origine'] ?? '—',
                        $c['statut_actuel'],
                        number_format($c['manque_a_gagner_fcfa'], 0, ',', ' '),
                    ], $bloc['cas'])
                );
                if ($bloc['cas_tronques'] > 0) {
                    $this->comment("  … et {$bloc['cas_tronques']} autre(s), voir --limite.");
                }
            }
            $this->newLine();
        }

        $this->info(sprintf(
            'Total : %d cas, %s FCFA non réclamés.',
            $rapport['resume']['cas_total'],
            number_format($rapport['resume']['manque_a_gagner_fcfa'], 0, ',', ' ')
        ));
        $this->comment("Cette commande ne modifie rien. Chaque cas se corrige depuis la fiche d'inscription.");
    }
}
