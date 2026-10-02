<?php

namespace App\Services\Reinscription;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPRegleAcademique;
use App\Services\Inscriptions\NormalisationTypeInscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Read model leger pour les compteurs de la page /esbtp/reinscription.
 *
 * L'ancien index appelait ReeinscriptionService::getStatistiquesReinscription(),
 * qui rechargeait les notes et leurs relations etudiant par etudiant. Sur une
 * promotion reelle, cela transforme l'ouverture de la page en plusieurs
 * centaines/milliers de requetes et peut atteindre le max_execution_time avant
 * meme que Blade ne rende le premier octet.
 *
 * Ici les inscriptions sont traitees par lots et toutes les notes du lot sont
 * chargees en une requete (plus les eager-loads Eloquent). La decision reste la
 * meme : meme regle academique, meme moyenne par matiere, meme filtre BTS/LMD.
 */
final class ReinscriptionDashboardStats
{
    public function __construct(
        private readonly NotesDeLaPromotion $notes,
        private readonly MoyennesAnnuellesDuBulletin $moyennesAnnuelles,
    ) {
    }

    /**
     * @return array{passages:int,rattrapages:int,redoublements:int,valides:int,abandons_annee:int,abandons_ecole:int,errors:int}
     */
    public function calculate(): array
    {
        $stats = $this->emptyStats();

        $courante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $courante) {
            return $stats;
        }

        $precedente = ESBTPAnneeUniversitaire::where('end_date', '<', $courante->start_date)
            ->orderBy('end_date', 'desc')
            ->first();

        $stats['valides'] = ESBTPInscription::where('type_inscription', NormalisationTypeInscription::REINSCRIPTION)
            ->where('annee_universitaire_id', $courante->id)
            ->where('status', 'active')
            ->count();

        if (! $precedente) {
            return $stats;
        }

        $stats['abandons_annee'] = ESBTPEtudiant::where('statut', 'abandon')
            ->where(function ($query) {
                $query->where('abandon_type', 'annee_scolaire')
                    ->orWhereNull('abandon_type');
            })
            ->whereHas('inscriptions', fn ($query) => $query->where('annee_universitaire_id', $precedente->id))
            ->count();

        $stats['abandons_ecole'] = ESBTPEtudiant::where('statut', 'abandon')
            ->where('abandon_type', 'ecole')
            ->whereHas('inscriptions', fn ($query) => $query->where('annee_universitaire_id', $precedente->id))
            ->count();

        // Le total qui alimente la categorie "Non valides" dans le service
        // historique : etudiants de N-1 avec une classe, sans inscription en N.
        $poolNonReinscrits = ESBTPEtudiant::whereHas('inscriptions', function ($query) use ($precedente) {
                $query->where('annee_universitaire_id', $precedente->id)
                    ->whereNotNull('classe_id');
            })
            ->whereDoesntHave('inscriptions', fn ($query) => $query->where('annee_universitaire_id', $courante->id))
            ->count();

        $decisionsReussies = 0;
        $regles = [];

        ESBTPInscription::query()
            ->with(['etudiant', 'classe.niveau', 'classe.filiere'])
            ->whereNotNull('classe_id')
            ->whereNotNull('etudiant_id')
            ->where('annee_universitaire_id', $precedente->id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->whereDoesntHave('etudiant.inscriptions', fn ($query) => $query->where('annee_universitaire_id', $courante->id))
            ->orderBy('id')
            ->chunkById(200, function ($inscriptions) use (&$stats, &$decisionsReussies, &$regles, $precedente) {
                $ids = $inscriptions->pluck('etudiant_id')->filter()->unique()->values();

                // Lignes brutes, sans un modele par note : voir NotesDeLaPromotion.
                $notesParEtudiant = $this->notes->pour($ids->all(), $precedente->name);
                // Meme moyenne que les onglets : l'annuelle du bulletin en BTS
                // (voir ReeinscriptionService::moyennePourDecision()).
                $annuelles = $this->moyennesAnnuelles->pour($inscriptions);

                foreach ($inscriptions as $inscription) {
                    try {
                        $etudiant = $inscription->etudiant;
                        $classe = $inscription->classe;
                        if (! $etudiant || ! $classe) {
                            continue;
                        }

                        $niveau = $classe->niveau?->name ?? '';
                        $filiere = $classe->filiere?->name ?? '';
                        $cleRegle = $niveau.'|'.$filiere;

                        if (! array_key_exists($cleRegle, $regles)) {
                            $regles[$cleRegle] = ESBTPRegleAcademique::getRegleForNiveauFiliere($niveau, $filiere)
                                ?? ESBTPRegleAcademique::where('niveau', '')
                                    ->where('filiere', '')
                                    ->where('actif', true)
                                    ->first()
                                ?? $this->fallbackRule($niveau, $filiere);
                        }

                        $regle = $regles[$cleRegle];
                        $notes = ($notesParEtudiant->get($etudiant->id) ?? collect())
                            ->filter(function ($note) use ($classe) {
                                $matiere = $note->matiere ?? $note->evaluation?->matiere;

                                return ! $matiere || CoherenceSystemeAcademique::matiereRetenue(
                                    $matiere,
                                    $classe,
                                    'reinscription/stats'
                                );
                            })
                            ->values();

                        [$moyenne, $nbEchecs] = $this->moyenneEtEchecs($notes, (float) $regle->moyenne_passage);
                        $annuelle = $annuelles[(int) $inscription->id]['moyenne'] ?? null;
                        if ($annuelle !== null) {
                            $moyenne = $annuelle;
                        }

                        if ($regle->peutPasser($moyenne)) {
                            $stats['passages']++;
                        } elseif ($regle->peutRattraper($moyenne) && $nbEchecs <= (int) $regle->max_matieres_rattrapage) {
                            $stats['rattrapages']++;
                        } else {
                            $stats['redoublements']++;
                        }

                        $decisionsReussies++;
                    } catch (\Throwable $e) {
                        Log::warning('Reinscription dashboard: decision ignoree', [
                            'inscription_id' => $inscription->id,
                            'etudiant_id' => $inscription->etudiant_id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        // Cela reproduit le sens du tableau "Non valides" sans refaire une
        // seconde analyse complete : tout dossier N-1 sans inscription N qui
        // n'a pas produit une decision exploitable.
        $stats['errors'] = max(0, $poolNonReinscrits - $decisionsReussies);

        return $stats;
    }

    /**
     * @param Collection<int, object> $notes lignes de NotesDeLaPromotion
     * @return array{0:float,1:int}
     */
    private function moyenneEtEchecs(Collection $notes, float $moyennePassage): array
    {
        if ($notes->isEmpty()) {
            return [0.0, 0];
        }

        $moyennes = $notes
            ->groupBy(fn ($note) => $note->matiere_id ?? $note->evaluation?->matiere?->id)
            ->map(function ($notesMatiere) {
                $premiere = $notesMatiere->first();
                $matiere = $premiere?->matiere ?? $premiere?->evaluation?->matiere;

                return [
                    'matiere' => $matiere,
                    'moyenne' => (float) $notesMatiere->avg('note'),
                ];
            });

        $moyenneGenerale = (float) $moyennes->avg('moyenne');
        $echecs = $moyennes
            ->filter(fn ($item) => $item['matiere'] && $item['moyenne'] < $moyennePassage)
            ->count();

        return [$moyenneGenerale, $echecs];
    }

    private function fallbackRule(string $niveau, string $filiere): ESBTPRegleAcademique
    {
        return new ESBTPRegleAcademique([
            'niveau' => $niveau,
            'filiere' => $filiere,
            'moyenne_passage' => 12.00,
            'moyenne_rattrapage' => 8.00,
            'max_matieres_rattrapage' => 3,
            'autoriser_redoublement' => true,
            'max_redoublements' => 2,
            'actif' => true,
        ]);
    }

    /** @return array{passages:int,rattrapages:int,redoublements:int,valides:int,abandons_annee:int,abandons_ecole:int,errors:int} */
    private function emptyStats(): array
    {
        return [
            'passages' => 0,
            'rattrapages' => 0,
            'redoublements' => 0,
            'valides' => 0,
            'abandons_annee' => 0,
            'abandons_ecole' => 0,
            'errors' => 0,
        ];
    }
}
