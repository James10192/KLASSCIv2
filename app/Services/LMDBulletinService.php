<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDDeliberation;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDResultatUE;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPUniteEnseignement;
use App\Helpers\SettingsHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LMDBulletinService
{
    /** Cache des settings LMD pour eviter des requetes repetees. */
    protected array $settings = [];

    /** Pre-loaded notes grouped by matiere_id (avoid N+1). */
    protected ?\Illuminate\Support\Collection $preloadedNotes = null;

    /** Pre-loaded enseignant mapping matiere_id => enseignant_id (avoid N+1). */
    protected ?\Illuminate\Support\Collection $preloadedEnseignants = null;

    protected function getSetting(string $key, $default = null)
    {
        if (!isset($this->settings[$key])) {
            $this->settings[$key] = SettingsHelper::get($key, $default);
        }
        return $this->settings[$key];
    }

    protected function getValidationThreshold(): float
    {
        return (float) $this->getSetting('lmd_validation_threshold', 10);
    }
    /**
     * Generer le bulletin LMD complet pour un etudiant.
     *
     * @param int $etudiantId
     * @param int $classeId
     * @param int $anneeUniversitaireId
     * @param int $semestre (1-10)
     * @return ESBTPLMDBulletin
     */
    public function genererBulletinLMD(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        int $semestre,
        bool $skipRanksAndStats = false
    ): ESBTPLMDBulletin {
        return DB::transaction(function () use ($etudiantId, $classeId, $anneeUniversitaireId, $semestre, $skipRanksAndStats) {

            $classe = ESBTPClasse::with(['parcours.mention.domaine', 'parcours.filiere', 'niveau'])->findOrFail($classeId);
            $parcours = $classe->parcours;

            // 1. Creer ou mettre a jour le bulletin
            // Label parcours bulletin : "LICENCE 3 GCV BATIMENT & URBANISME"
            $parcoursLabel = $parcours
                ? $parcours->genererLabelBulletin($classe->niveau)
                : ($classe->niveau?->name ?? '');

            $bulletin = ESBTPLMDBulletin::updateOrCreate(
                [
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classeId,
                    'annee_universitaire_id' => $anneeUniversitaireId,
                    'semestre' => $semestre,
                ],
                [
                    'parcours_id' => $parcours?->id,
                    'niveau' => $classe->niveau?->name,
                    'domaine_label' => $parcours?->mention?->domaine?->name,
                    'mention_label' => $parcours?->mention?->name,
                    'parcours_label' => $parcoursLabel,
                    'updated_by' => auth()->id(),
                ]
            );

            // 2. Recuperer les UEs du semestre pour cette classe
            $ues = $this->getUEsForSemestre($classe, $semestre);

            // Pre-load ALL notes for this student in this semestre (avoid N+1)
            $periodeLabel = 'semestre' . $semestre;
            $allNotes = ESBTPNote::where('etudiant_id', $etudiantId)
                ->where('classe_id', $classeId)
                ->whereHas('evaluation', function ($q) use ($periodeLabel, $anneeUniversitaireId) {
                    $q->where('periode', $periodeLabel)
                      ->where('annee_universitaire_id', $anneeUniversitaireId)
                      ->where('status', ESBTPEvaluation::STATUS_COMPLETED);
                })
                ->with('evaluation')
                ->get()
                ->groupBy('matiere_id');

            // Pre-load enseignant mapping (avoid N+1)
            $enseignantMap = ESBTPEvaluation::where('classe_id', $classeId)
                ->where('annee_universitaire_id', $anneeUniversitaireId)
                ->whereNotNull('enseignant_id')
                ->distinct()
                ->pluck('enseignant_id', 'matiere_id');

            $this->preloadedNotes = $allNotes;
            $this->preloadedEnseignants = $enseignantMap;

            // 3. Calculer les resultats par UE et ECUE
            $resultatsUEs = [];
            $creditsTotaux = 0;

            foreach ($ues as $ue) {
                $resultatUE = $this->calculerResultatUE($bulletin, $ue, $etudiantId, $classeId, $semestre, $anneeUniversitaireId);
                $resultatsUEs[] = $resultatUE;
                $creditsTotaux += $ue->credit;
            }

            // 4. Calculer la moyenne generale ponderee par credits
            $moyenneGenerale = $this->calculerMoyenneGenerale($resultatsUEs);

            // 5. Appliquer la compensation inter-UE
            $creditsCapitalises = $this->appliquerCompensation($resultatsUEs, $moyenneGenerale);

            // 6. Mettre a jour le bulletin
            $bulletin->update([
                'moyenne_generale' => $moyenneGenerale,
                'credits_capitalises' => $creditsCapitalises,
                'credits_totaux' => $creditsTotaux,
                'updated_by' => auth()->id(),
            ]);

            // 7. Calculer les rangs et stats (skip si batch — sera fait une seule fois apres la boucle)
            if (!($skipRanksAndStats ?? false)) {
                $this->calculerRangsClasse($classeId, $anneeUniversitaireId, $semestre);
                $this->calculerStatsPromo($classeId, $anneeUniversitaireId, $semestre);
            }

            return $bulletin->fresh([
                'resultatsUEs.uniteEnseignement',
                'resultatsUEs.resultatsECUEs.matiere',
                'etudiant',
                'classe',
                'deliberation',
            ]);
        });
    }

    /**
     * Generer les bulletins pour toute une classe.
     */
    public function genererBulletinsClasse(int $classeId, int $anneeUniversitaireId, int $semestre): array
    {
        $classe = ESBTPClasse::with('inscriptions.etudiant')->findOrFail($classeId);

        $bulletins = [];
        $errors = [];
        foreach ($classe->inscriptions as $inscription) {
            if ($inscription->status !== 'active') continue;

            try {
                $bulletins[] = $this->genererBulletinLMD(
                    $inscription->etudiant_id,
                    $classeId,
                    $anneeUniversitaireId,
                    $semestre,
                    skipRanksAndStats: true // Calculer une seule fois apres la boucle
                );
            } catch (\Exception $e) {
                Log::error("LMD Bulletin generation failed for etudiant {$inscription->etudiant_id}: {$e->getMessage()}");
                $errors[] = $inscription->etudiant_id;
            }
        }

        // Calculer rangs et stats une seule fois pour toute la classe
        if (count($bulletins) > 0) {
            $this->calculerRangsClasse($classeId, $anneeUniversitaireId, $semestre);
            $this->calculerStatsPromo($classeId, $anneeUniversitaireId, $semestre);
        }

        return $bulletins;
    }

    /**
     * Recuperer les UEs pour un semestre donne.
     */
    protected function getUEsForSemestre(ESBTPClasse $classe, int $semestre): \Illuminate\Support\Collection
    {
        // Eager-load ECUEs via pivot (prioritaire) ET matieres HasMany (fallback)
        $eagerLoad = [
            'ecues' => fn($q) => $q->where('esbtp_matieres.is_active', true)->orderBy('esbtp_ue_matiere.ordre_bulletin')->orderBy('esbtp_matieres.code'),
            'matieres' => fn($q) => $q->where('is_active', true)->orderBy('ordre_bulletin')->orderBy('code'),
        ];

        if ($classe->parcours_id) {
            $pivotData = DB::table('esbtp_lmd_parcours_ue')
                ->where('parcours_id', $classe->parcours_id)
                ->where('semestre', $semestre)
                ->orderBy('ordre')
                ->get();

            if ($pivotData->isNotEmpty()) {
                $ueIds = $pivotData->pluck('unite_enseignement_id');
                $ues = ESBTPUniteEnseignement::active()
                    ->with($eagerLoad)
                    ->whereIn('id', $ueIds)
                    ->get()
                    ->keyBy('id');

                return $pivotData->map(fn($p) => $ues->get($p->unite_enseignement_id))->filter()->values();
            }
        }

        return ESBTPUniteEnseignement::active()
            ->with($eagerLoad)
            ->where('semestre', $semestre)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_id', $classe->niveau_etude_id)
            ->orderBy('code')
            ->get();
    }

    /**
     * Calculer le resultat d'une UE pour un etudiant.
     */
    protected function calculerResultatUE(
        ESBTPLMDBulletin $bulletin,
        ESBTPUniteEnseignement $ue,
        int $etudiantId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId
    ): ESBTPLMDResultatUE {

        // Creer/mettre a jour le resultat UE
        $resultatUE = ESBTPLMDResultatUE::updateOrCreate(
            [
                'bulletin_id' => $bulletin->id,
                'unite_enseignement_id' => $ue->id,
            ],
            [
                'etudiant_id' => $etudiantId,
                'credit' => $ue->credit,
                'updated_by' => auth()->id(),
            ]
        );

        // Calculer les resultats de chaque ECUE — pivot prioritaire, fallback HasMany
        $ecues = $ue->getEcuesEffectifs();
        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($ecues as $ecue) {
            $resultatECUE = $this->calculerResultatECUE(
                $bulletin, $resultatUE, $ecue, $etudiantId, $classeId, $semestre, $anneeUniversitaireId
            );

            if ($resultatECUE->moyenne !== null) {
                // Priorité: pivot coefficient > matière coefficient_ecue > matière coefficient > 1
                $coeff = $ecue->pivot?->coefficient_ecue ?? $ecue->coefficient_ecue ?? $ecue->coefficient ?? 1;
                $totalPoints += (float) $resultatECUE->moyenne * (float) $coeff;
                $totalCoefficients += (float) $coeff;
            }
        }

        // Moyenne UE = Σ(moyenne_ecue × coeff_ecue) / Σ coeff_ecue
        $moyenneUE = $totalCoefficients > 0
            ? round($totalPoints / $totalCoefficients, 2)
            : null;

        // Statut initial (AQ si >= seuil, NAQ sinon — APC sera applique apres)
        $threshold = $this->getValidationThreshold();
        $statut = ESBTPLMDResultatUE::STATUT_NAQ;
        if ($moyenneUE !== null && $moyenneUE >= $threshold) {
            $statut = ESBTPLMDResultatUE::STATUT_AQ;
        }

        $resultatUE->update([
            'moyenne' => $moyenneUE,
            'statut' => $statut,
            'mention' => $this->determinerMentionUE($moyenneUE),
            'updated_by' => auth()->id(),
        ]);

        return $resultatUE;
    }

    /**
     * Calculer le resultat d'un ECUE (matiere) pour un etudiant.
     */
    protected function calculerResultatECUE(
        ESBTPLMDBulletin $bulletin,
        ESBTPLMDResultatUE $resultatUE,
        ESBTPMatiere $ecue,
        int $etudiantId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId
    ): ESBTPLMDResultatECUE {

        // Calculer la moyenne ECUE depuis les notes (use preloaded data if available)
        $moyenneECUE = $this->calculerMoyenneECUE(
            $etudiantId, $ecue->id, $classeId, $semestre, $anneeUniversitaireId,
            $this->preloadedNotes?->get($ecue->id, collect())
        );

        // Trouver l'enseignant principal (use preloaded map if available)
        $enseignantId = $this->getEnseignantForECUE($ecue->id, $classeId, $anneeUniversitaireId, $this->preloadedEnseignants);

        return ESBTPLMDResultatECUE::updateOrCreate(
            [
                'bulletin_id' => $bulletin->id,
                'matiere_id' => $ecue->id,
            ],
            [
                'resultat_ue_id' => $resultatUE->id,
                'etudiant_id' => $etudiantId,
                'moyenne' => $moyenneECUE,
                'credit' => $ecue->pivot?->credit_ecue ?? $ecue->credit_ecue ?? 0,
                'enseignant_id' => $enseignantId,
                'updated_by' => auth()->id(),
            ]
        );
    }

    /**
     * Calculer la moyenne d'un ECUE depuis les notes des evaluations.
     *
     * Moyenne ECUE = Σ(note_normalized × coeff_eval) / Σ coeff_eval
     */
    public function calculerMoyenneECUE(
        int $etudiantId,
        int $matiereId,
        int $classeId,
        int $semestre,
        int $anneeUniversitaireId,
        ?\Illuminate\Support\Collection $preloadedNotes = null
    ): ?float {
        if ($preloadedNotes !== null) {
            $notes = $preloadedNotes;
        } else {
            $periodeLabel = 'semestre' . $semestre;
            $notes = ESBTPNote::where('etudiant_id', $etudiantId)
                ->where('matiere_id', $matiereId)
                ->where('classe_id', $classeId)
                ->whereHas('evaluation', function ($q) use ($periodeLabel, $anneeUniversitaireId) {
                    $q->where('periode', $periodeLabel)
                      ->where('annee_universitaire_id', $anneeUniversitaireId)
                      ->where('status', ESBTPEvaluation::STATUS_COMPLETED);
                })
                ->with('evaluation')
                ->get();
        }

        if ($notes->isEmpty()) return null;

        $totalPoints = 0;
        $totalCoeff = 0;

        foreach ($notes as $note) {
            $eval = $note->evaluation;
            if (!$eval) continue;

            $bareme = $eval->bareme ?: 20;
            $coeffEval = $eval->coefficient ?: 1;

            // Normaliser la note sur 20
            $noteNormalisee = $note->is_absent ? 0 : (($note->note / $bareme) * 20);

            $totalPoints += $noteNormalisee * $coeffEval;
            $totalCoeff += $coeffEval;
        }

        if ($totalCoeff == 0) return null;

        return round($totalPoints / $totalCoeff, 2);
    }

    /**
     * Calculer la moyenne generale ponderee par credits.
     *
     * Moyenne Generale = Σ(moyenne_ue × credits_ue) / Σ credits_ue
     */
    public function calculerMoyenneGenerale(array $resultatsUEs): ?float
    {
        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($resultatsUEs as $resultat) {
            if ($resultat->moyenne !== null && $resultat->credit > 0) {
                $totalPoints += (float) $resultat->moyenne * $resultat->credit;
                $totalCredits += $resultat->credit;
            }
        }

        if ($totalCredits == 0) return null;

        return round($totalPoints / $totalCredits, 2);
    }

    /**
     * Appliquer la compensation inter-UE et calculer les credits capitalises.
     *
     * Regles:
     * - AQ: moyenne_ue >= 10 → credits capitalises
     * - APC: moyenne_ue < 10 MAIS moyenne_generale >= 10 → credits capitalises
     * - NAQ: sinon → pas de credits
     */
    public function appliquerCompensation(array $resultatsUEs, ?float $moyenneGenerale): int
    {
        $threshold = $this->getValidationThreshold();
        $compensationEnabled = $this->getSetting('lmd_compensation_inter_ue', '1') == '1';
        $creditsCapitalises = 0;
        $apcIds = [];

        foreach ($resultatsUEs as $resultat) {
            if ($resultat->moyenne === null) continue;

            if ((float) $resultat->moyenne >= $threshold) {
                // Deja AQ
                $creditsCapitalises += $resultat->credit;
            } elseif ($compensationEnabled && $moyenneGenerale !== null && $moyenneGenerale >= $threshold) {
                // Compensation: APC
                $apcIds[] = $resultat->id;
                $creditsCapitalises += $resultat->credit;
            }
            // Sinon reste NAQ, pas de credits
        }

        // Batch update APC au lieu d'un update par UE
        if (!empty($apcIds)) {
            ESBTPLMDResultatUE::whereIn('id', $apcIds)
                ->update(['statut' => ESBTPLMDResultatUE::STATUT_APC]);
        }

        return $creditsCapitalises;
    }

    /**
     * Calculer les rangs de tous les etudiants d'une classe pour un semestre.
     */
    public function calculerRangsClasse(int $classeId, int $anneeUniversitaireId, int $semestre): void
    {
        $bulletins = ESBTPLMDBulletin::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('semestre', $semestre)
            ->whereNotNull('moyenne_generale')
            ->orderByDesc('moyenne_generale')
            ->get();

        $effectif = $bulletins->count();
        $rang = 0;
        $lastMoyenne = null;

        foreach ($bulletins as $bulletin) {
            $moy = (float) $bulletin->moyenne_generale;
            if ($moy !== $lastMoyenne) {
                $rang++;
                $lastMoyenne = $moy;
            }
            $bulletin->update([
                'rang' => $rang,
                'effectif' => $effectif,
            ]);
        }
    }

    /**
     * Calculer les stats promo (min/moy/max) pour chaque UE et ECUE.
     */
    public function calculerStatsPromo(int $classeId, int $anneeUniversitaireId, int $semestre): void
    {
        $bulletinIds = ESBTPLMDBulletin::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->where('semestre', $semestre)
            ->pluck('id');

        if ($bulletinIds->isEmpty()) return;

        // Stats par UE
        $statsUE = ESBTPLMDResultatUE::whereIn('bulletin_id', $bulletinIds)
            ->whereNotNull('moyenne')
            ->select('unite_enseignement_id')
            ->selectRaw('MIN(moyenne) as min_moy, AVG(moyenne) as avg_moy, MAX(moyenne) as max_moy')
            ->groupBy('unite_enseignement_id')
            ->get()
            ->keyBy('unite_enseignement_id');

        foreach ($statsUE as $ueId => $stat) {
            ESBTPLMDResultatUE::whereIn('bulletin_id', $bulletinIds)
                ->where('unite_enseignement_id', $ueId)
                ->update([
                    'stat_min' => round($stat->min_moy, 2),
                    'stat_moy' => round($stat->avg_moy, 2),
                    'stat_max' => round($stat->max_moy, 2),
                ]);
        }

        // Stats par ECUE
        $statsECUE = ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
            ->whereNotNull('moyenne')
            ->select('matiere_id')
            ->selectRaw('MIN(moyenne) as min_moy, AVG(moyenne) as avg_moy, MAX(moyenne) as max_moy')
            ->groupBy('matiere_id')
            ->get()
            ->keyBy('matiere_id');

        foreach ($statsECUE as $matiereId => $stat) {
            ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
                ->where('matiere_id', $matiereId)
                ->update([
                    'stat_min' => round($stat->min_moy, 2),
                    'stat_moy' => round($stat->avg_moy, 2),
                    'stat_max' => round($stat->max_moy, 2),
                ]);
        }

        // Rangs par ECUE
        $ecueIds = ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
            ->distinct()->pluck('matiere_id');

        foreach ($ecueIds as $matiereId) {
            $resultats = ESBTPLMDResultatECUE::whereIn('bulletin_id', $bulletinIds)
                ->where('matiere_id', $matiereId)
                ->whereNotNull('moyenne')
                ->orderByDesc('moyenne')
                ->get();

            $rang = 0;
            $lastMoy = null;
            foreach ($resultats as $r) {
                $m = (float) $r->moyenne;
                if ($m !== $lastMoy) {
                    $rang++;
                    $lastMoy = $m;
                }
                $r->update(['rang' => $rang]);
            }
        }
    }

    /**
     * Determiner la mention d'une UE selon sa moyenne.
     */
    public function determinerMentionUE(?float $moyenne): ?string
    {
        if ($moyenne === null) return null;

        $tb = (float) $this->getSetting('lmd_mention_tb_threshold', 16);
        $b  = (float) $this->getSetting('lmd_mention_b_threshold', 14);
        $ab = (float) $this->getSetting('lmd_mention_ab_threshold', 12);
        $p  = (float) $this->getSetting('lmd_mention_p_threshold', 10);

        if ($moyenne >= $tb) return 'TB';
        if ($moyenne >= $b)  return 'B';
        if ($moyenne >= $ab) return 'AB';
        if ($moyenne >= $p)  return 'P';
        if ($moyenne >= 8)   return 'INS';
        return 'F';
    }

    /**
     * Determiner la decision de deliberation.
     */
    public function determinerDecisionDeliberation(
        ?float $moyenneGenerale,
        int $creditsCapitalises,
        int $creditsTotaux
    ): string {
        if ($moyenneGenerale === null) return '';

        $tauxCapitalisation = $creditsTotaux > 0
            ? ($creditsCapitalises / $creditsTotaux) * 100
            : 0;

        if ($tauxCapitalisation == 100 && $moyenneGenerale >= 16) {
            return 'Félicitations du jury';
        }
        if ($tauxCapitalisation == 100 && $moyenneGenerale >= 14) {
            return 'Tableau d\'honneur';
        }
        if ($tauxCapitalisation == 100 && $moyenneGenerale >= 12) {
            return 'Encouragement pour le travail fourni';
        }
        if ($tauxCapitalisation == 100) {
            return 'Passage';
        }
        if ($tauxCapitalisation >= 70) {
            return 'Passage conditionnel';
        }
        return 'Ajourné(e)';
    }

    /**
     * Trouver l'enseignant d'un ECUE pour une classe donnee.
     */
    protected function getEnseignantForECUE(int $matiereId, int $classeId, int $anneeUniversitaireId, ?\Illuminate\Support\Collection $enseignantMap = null): ?int
    {
        if ($enseignantMap !== null) {
            return $enseignantMap->get($matiereId);
        }

        // Chercher dans les evaluations completees
        $eval = ESBTPEvaluation::where('matiere_id', $matiereId)
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereNotNull('enseignant_id')
            ->first();

        return $eval?->enseignant_id;
    }

    /**
     * Preparer les donnees pour le rendu du bulletin PDF/preview.
     */
    public function preparerDonneesBulletin(ESBTPLMDBulletin $bulletin): array
    {
        $bulletin->load([
            'etudiant',
            'classe.niveau',
            'classe.filiere',
            'parcours.mention.domaine',
            'anneeUniversitaire',
            'resultatsUEs.uniteEnseignement',
            'resultatsUEs.resultatsECUEs.matiere',
            'resultatsUEs.resultatsECUEs.enseignant',
            'deliberation',
        ]);

        // Bulletin field visibility & labels (configurable per tenant)
        $bulletinFields = [
            ['key' => 'domaine', 'show' => $this->getSetting('lmd_bulletin_show_domaine', '1') == '1', 'label' => $this->getSetting('lmd_bulletin_label_domaine', 'DOMAINE'), 'value' => $bulletin->domaine_label],
            ['key' => 'mention', 'show' => $this->getSetting('lmd_bulletin_show_mention', '1') == '1', 'label' => $this->getSetting('lmd_bulletin_label_mention', 'MENTION'), 'value' => $bulletin->mention_label],
            ['key' => 'specialite', 'show' => $this->getSetting('lmd_bulletin_show_specialite', '0') == '1', 'label' => $this->getSetting('lmd_bulletin_label_specialite', 'SPÉCIALITÉ'), 'value' => $bulletin->specialite_label ?? ''],
            ['key' => 'parcours', 'show' => $this->getSetting('lmd_bulletin_show_parcours', '1') == '1', 'label' => $this->getSetting('lmd_bulletin_label_parcours', 'PARCOURS'), 'value' => $bulletin->parcours_label],
        ];

        return [
            'bulletin' => $bulletin,
            'etudiant' => $bulletin->etudiant,
            'classe' => $bulletin->classe,
            'annee' => $bulletin->anneeUniversitaire,
            'parcours' => $bulletin->parcours,
            'domaine' => $bulletin->domaine_label,
            'mention' => $bulletin->mention_label,
            'parcours_label' => $bulletin->parcours_label,
            'niveau' => $bulletin->niveau,
            'semestre' => $bulletin->semestre,
            'resultats_ues' => $bulletin->resultatsUEs,
            'moyenne_generale' => $bulletin->moyenne_generale,
            'mention_generale' => $bulletin->mention_generale,
            'credits_capitalises' => $bulletin->credits_capitalises,
            'credits_totaux' => $bulletin->credits_totaux,
            'rang' => $bulletin->rang,
            'effectif' => $bulletin->effectif,
            'decision' => $bulletin->decision_deliberation,
            'deliberation' => $bulletin->deliberation,
            'bulletin_fields' => $bulletinFields,
        ];
    }
}
