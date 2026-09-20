<?php

namespace App\Console\Commands\Evaluations;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Sync the denormalized columns (classe_id, matiere_id, semestre) on esbtp_notes
 * to match their parent evaluation's values.
 *
 * Notes were denormalized for fast queries, but they don't auto-sync when the
 * evaluation is updated. Run this command after a bulk evaluation edit or
 * when migrating data, to fix stale note columns.
 */
class SyncNotesScopeCommand extends Command
{
    protected $signature = 'evaluations:sync-notes
                            {--evaluation= : Sync only this evaluation ID (default: all)}
                            {--dry : Show what would be updated without writing}
                            {--clean-resultats : Also delete orphan esbtp_resultats rows (no matching notes)}
                            {--matiere= : Limiter le nettoyage a cette matiere}
                            {--classe= : Limiter le nettoyage a cette classe}
                            {--periode= : Limiter le nettoyage a cette periode (semestre1|semestre2|annuel)}
                            {--niveau= : Limiter le nettoyage aux classes de ce niveau d etude}
                            {--filiere= : Limiter le nettoyage aux classes de cette filiere}
                            {--liste : Detailler les lignes concernees au lieu de les compter}';

    protected $description = 'Sync esbtp_notes.{classe_id, matiere_id, semestre} from their parent evaluation';

    public function handle(): int
    {
        $evaluationId = $this->option('evaluation');
        $dry = (bool) $this->option('dry');

        $query = ESBTPEvaluation::query();
        if ($evaluationId) {
            $query->where('id', $evaluationId);
        }

        $totalNotesFixed = 0;
        $evaluationsTouched = 0;

        $query->chunkById(100, function ($evaluations) use (&$totalNotesFixed, &$evaluationsTouched, $dry) {
            foreach ($evaluations as $eval) {
                $expectedSemestre = ESBTPNote::semestreDepuisLaPeriode((string) $eval->periode);

                $staleCount = ESBTPNote::where('evaluation_id', $eval->id)
                    ->where(function ($q) use ($eval, $expectedSemestre) {
                        $q->where('classe_id', '!=', $eval->classe_id)
                            ->orWhere('matiere_id', '!=', $eval->matiere_id)
                            ->orWhere('semestre', '!=', $expectedSemestre);
                    })
                    ->count();

                if ($staleCount === 0) {
                    continue;
                }

                $evaluationsTouched++;
                $totalNotesFixed += $staleCount;
                $this->line(sprintf(
                    '  eval#%d (%s) -> %d note(s) stale | target: classe=%d, matiere=%d, sem=%d',
                    $eval->id, $eval->titre, $staleCount,
                    $eval->classe_id, $eval->matiere_id, $expectedSemestre
                ));

                if (! $dry) {
                    ESBTPNote::where('evaluation_id', $eval->id)->update([
                        'classe_id' => $eval->classe_id,
                        'matiere_id' => $eval->matiere_id,
                        'semestre' => $expectedSemestre,
                    ]);
                }
            }
        });

        $verb = $dry ? 'would be' : 'were';
        $this->newLine();
        $this->info("Total: {$evaluationsTouched} evaluation(s), {$totalNotesFixed} note(s) {$verb} synced.");

        // Clean up orphan esbtp_resultats : rows for (etudiant, classe, matiere, periode, annee)
        // where no notes exist anymore. Ces moyennes snapshots restent visibles dans
        // resultats/etudiant alors que les notes ont été déplacées vers une autre matière.
        if ($this->option('clean-resultats')) {
            $this->newLine();
            $this->info('Cleaning orphan esbtp_resultats rows…');

            // Catégorie 1 : resultat dont la matière n'existe plus (matiere_id invalide ou soft-deleted)
            // → affichait "Matière inconnue" sur la page résultats
            $brokenMatiereQuery = ESBTPResultat::whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('esbtp_matieres')
                    ->whereColumn('esbtp_matieres.id', 'esbtp_resultats.matiere_id')
                    ->whereNull('esbtp_matieres.deleted_at');
            });
            // Meme perimetre que la categorie 2 : sans lui, le `delete()` qui
            // suit balaie l'ecole entiere. La categorie 2 etait bornee, la 1 ne
            // l'etait pas — et c'est celle qui supprime le plus largement,
            // puisque « matiere introuvable » ne depend d'aucune option.
            // `--matiere` s'y applique aussi, et utilement : le filtre porte
            // sur `esbtp_resultats.matiere_id`, qui garde l'identifiant d'une
            // matiere mise de cote. On peut donc viser UNE matiere effacee
            // precise. Un commentaire anterieur disait le contraire ; il fermait
            // une porte ouverte.
            $this->restreindreAuPerimetre($brokenMatiereQuery);

            $brokenCount = $brokenMatiereQuery->count();
            $this->line("  Found {$brokenCount} resultat(s) with broken matiere_id");
            if (! $dry && $brokenCount > 0) {
                $deleted = $brokenMatiereQuery->delete();
                $this->info("  Deleted {$deleted} resultat(s) with broken matiere");
            }

            // Catégorie 2 : resultat sans note correspondante (matière déplacée vers autre matière)
            // esbtp_notes n'a pas annee_universitaire_id direct — passer par esbtp_evaluations via join.
            $orphanQuery = ESBTPResultat::whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('esbtp_notes')
                    ->join('esbtp_evaluations', 'esbtp_evaluations.id', '=', 'esbtp_notes.evaluation_id')
                    ->whereColumn('esbtp_notes.etudiant_id', 'esbtp_resultats.etudiant_id')
                    ->whereColumn('esbtp_notes.classe_id', 'esbtp_resultats.classe_id')
                    ->whereColumn('esbtp_notes.matiere_id', 'esbtp_resultats.matiere_id')
                    ->whereColumn('esbtp_evaluations.annee_universitaire_id', 'esbtp_resultats.annee_universitaire_id')
                    ->where(function ($q2) {
                        $q2->where(function ($a) {
                            $a->where('esbtp_resultats.periode', 'semestre1')->where('esbtp_notes.semestre', 1);
                        })->orWhere(function ($a) {
                            $a->where('esbtp_resultats.periode', 'semestre2')->where('esbtp_notes.semestre', 2);
                        })->orWhere('esbtp_resultats.periode', 'annuel');
                    });
            });

            // Le perimetre est facultatif, mais il change tout : sans lui, la
            // commande balaie l'ecole entiere, et une moyenne saisie a la main
            // pour une matiere jamais evaluee est orpheline elle aussi. Viser
            // la matiere deplacee et sa periode d'origine ne detruit que ce que
            // le deplacement a laisse derriere lui.
            $this->restreindreAuPerimetre($orphanQuery);

            if ($this->option('liste')) {
                $this->detailler($orphanQuery);
            }

            $orphanCount = $orphanQuery->count();
            $this->line("  Found {$orphanCount} orphan resultat(s) (no matching note)");

            if (! $dry && $orphanCount > 0) {
                $deleted = $orphanQuery->delete();
                $this->info("  Deleted {$deleted} orphan resultat(s)");
            }
        }

        if ($dry) {
            $this->warn('[DRY-RUN] Pass without --dry to actually update.');
        }

        return self::SUCCESS;
    }

    /** @param \Illuminate\Database\Eloquent\Builder<ESBTPResultat> $query */
    private function restreindreAuPerimetre($query): void
    {
        foreach (['matiere' => 'matiere_id', 'classe' => 'classe_id', 'periode' => 'periode'] as $option => $colonne) {
            if ($this->option($option) !== null && $this->option($option) !== '') {
                $query->where($colonne, $this->option($option));
            }
        }

        // Niveau et filiere ne sont pas portes par le resultat : ils designent
        // un ensemble de classes. C'est ainsi qu'on couvre « les autres classes
        // du meme niveau, de la meme filiere et du meme semestre » sans avoir a
        // les lister a la main.
        $niveau = $this->option('niveau');
        $filiere = $this->option('filiere');

        if ($niveau || $filiere) {
            $query->whereIn('classe_id', ESBTPClasse::query()
                ->when($niveau, fn ($q) => $q->where('niveau_etude_id', $niveau))
                ->when($filiere, fn ($q) => $q->where('filiere_id', $filiere))
                ->pluck('id'));
        }
    }

    /** @param \Illuminate\Database\Eloquent\Builder<ESBTPResultat> $query */
    private function detailler($query): void
    {
        $lignes = (clone $query)
            ->with(['etudiant:id,nom,prenoms,matricule', 'matiere:id,name', 'classe:id,name'])
            ->orderBy('classe_id')
            ->orderBy('matiere_id')
            ->get();

        if ($lignes->isEmpty()) {
            $this->line('  (aucune ligne)');

            return;
        }

        $this->table(
            ['Classe', 'Periode', 'Matiere', 'Etudiant', 'Moyenne'],
            $lignes->map(fn (ESBTPResultat $r) => [
                $r->classe?->name ?? '#'.$r->classe_id,
                $r->periode,
                $r->matiere?->name ?? '#'.$r->matiere_id,
                trim(($r->etudiant?->nom ?? '').' '.($r->etudiant?->prenoms ?? '')) ?: '#'.$r->etudiant_id,
                $r->moyenne,
            ])->all()
        );
    }
}
