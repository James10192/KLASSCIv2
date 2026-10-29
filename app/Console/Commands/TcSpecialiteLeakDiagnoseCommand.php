<?php

namespace App\Console\Commands;

use App\Domain\BtsTroncCommun\Diagnostics\TcSpecialiteLeakDiagnostic;
use Illuminate\Console\Command;

/**
 * Meme diagnostic que GET /api/cli/diagnostics/tc-specialite-leak, pour un
 * terminal serveur. Lecture seule.
 */
class TcSpecialiteLeakDiagnoseCommand extends Command
{
    protected $signature = 'diagnostics:tc-specialite-leak
        {--annee= : ID de l\'annee universitaire (defaut : annee courante)}
        {--classe= : ID d\'une classe de tronc commun}
        {--etudiant= : ID d\'un etudiant}
        {--json : Sortie JSON}';

    protected $description = 'Matieres de specialite affichees sur les bulletins de tronc commun (lecture seule).';

    public function handle(TcSpecialiteLeakDiagnostic $diagnostic): int
    {
        try {
            $rapport = $diagnostic->executer(
                $this->entier('annee'),
                $this->entier('classe'),
                $this->entier('etudiant'),
            );
        } catch (\DomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->afficherResume($rapport);
        $this->afficherLignes($rapport);

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $rapport */
    private function afficherResume(array $rapport): void
    {
        $resume = $rapport['resume'];

        $this->info('Annee : '.($rapport['annee_universitaire']['libelle'] ?? '?'));
        $this->line(sprintf(
            '%d classe(s) de tronc commun analysee(s), %d touchee(s), %d matiere(s) suspecte(s), %d etudiant(s), %d ligne(s).',
            $resume['classes_analysees'],
            $resume['classes_touchees'],
            $resume['matieres_suspectes'],
            $resume['etudiants_touches'],
            $resume['lignes'],
        ));

        $this->table(['Cause', 'Lignes', 'Matieres'], collect($resume['par_cause'])
            ->map(fn ($n, $cause) => [$cause, $n, $resume['matieres_par_cause'][$cause] ?? 0])
            ->values()->all());
    }

    /** @param array<string, mixed> $rapport */
    private function afficherLignes(array $rapport): void
    {
        $lignes = [];

        foreach ($rapport['classes'] as $classe) {
            foreach ($classe['semestres'] as $semestre) {
                foreach ($semestre['matieres'] as $matiere) {
                    $prefixe = [$classe['classe'], 'S'.$semestre['semestre'], $matiere['matiere'], $matiere['type_suspicion']];

                    if ($matiere['etudiants'] === []) {
                        $lignes[] = array_merge($prefixe, ['-', '-', '-', $matiere['cause_principale']]);
                    }

                    foreach ($matiere['etudiants'] as $etudiant) {
                        $qui = trim(($etudiant['nom'] ?? '').' '.($etudiant['prenoms'] ?? '')).' #'.$etudiant['etudiant_id'];

                        foreach ($etudiant['notes'] as $note) {
                            $lignes[] = array_merge($prefixe, [$qui, $note['note'] ?? 'abs', ($note['evaluation_classe'] ?? '?').($note['retenue_au_bulletin'] ? '' : ' (hors bulletin)'), $note['cause']]);
                        }

                        foreach ($etudiant['moyennes_enregistrees'] as $moyenne) {
                            $lignes[] = array_merge($prefixe, [$qui, $moyenne['moyenne'], 'moyenne enregistree', $moyenne['cause']]);
                        }
                    }
                }
            }
        }

        if ($lignes === []) {
            $this->info('Aucune matiere de specialite ne remonte sur un bulletin de tronc commun.');

            return;
        }

        $this->table(['Classe', 'Sem.', 'Matiere', 'Type', 'Etudiant', 'Note', 'Source', 'Cause'], $lignes);
        $this->comment('Les actions suggerees figurent dans la sortie --json.');
    }

    private function entier(string $option): ?int
    {
        $valeur = $this->option($option);

        return $valeur === null || $valeur === '' ? null : (int) $valeur;
    }
}
