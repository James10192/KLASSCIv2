<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDSession;
use App\Models\ESBTPMatiere;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Workflow rattrapage UEMOA :
 * 1. Snapshot notes session normale → note_session_normale
 * 2. Détermination éligibles (ECUE < seuil_validation)
 * 3. Génération session rattrapage + examens 2e session
 * 4. Recalcul note_finale = max(normale, rattrapage) ou replace
 * 5. Notifications étudiants éligibles
 */
class RattrapageSchedulingService
{
    public function __construct(
        private readonly ExamenSchedulingService $examenScheduler
    ) {}

    /**
     * Crée la session rattrapage enfant d'une session normale.
     */
    public function genererSessionRattrapage(
        ESBTPLMDSession $sessionNormale,
        ?Carbon $dateDebut = null
    ): ESBTPLMDSession {
        if ($sessionNormale->type !== 'normale') {
            throw new \DomainException('La session parent doit être de type "normale".');
        }
        if ($sessionNormale->status !== 'completed' && $sessionNormale->status !== 'published') {
            throw new \DomainException("La session normale doit être complète/publiée avant rattrapage (actuel: {$sessionNormale->status}).");
        }

        $debut = $dateDebut ?? ($sessionNormale->date_fin
            ? $sessionNormale->date_fin->copy()->addWeeks(2)
            : now()->addWeeks(2));

        $session = ESBTPLMDSession::create([
            'annee_universitaire_id' => $sessionNormale->annee_universitaire_id,
            'parcours_id' => $sessionNormale->parcours_id,
            'type' => 'rattrapage',
            'parent_session_id' => $sessionNormale->id,
            'semestre' => $sessionNormale->semestre,
            'libelle' => 'Rattrapage — ' . $sessionNormale->libelle,
            'date_debut' => $debut,
            'date_fin' => $debut->copy()->addDays(7),
            'status' => 'planned',
            'created_by' => optional(auth()->user())->id,
        ]);

        Log::info('[RattrapageSchedulingService] session rattrapage créée', [
            'session_id' => $session->id,
            'parent' => $sessionNormale->id,
        ]);

        return $session;
    }

    /**
     * Snapshot notes session normale + identification éligibles.
     * Conditions UEMOA : ECUE < seuil_validation_ecue (setting `lmd_seuil_validation_ecue`, default 10).
     *
     * @return Collection<int, ESBTPLMDResultatECUE>
     */
    public function identifierEtudiantsEligibles(ESBTPLMDSession $sessionNormale): Collection
    {
        $seuil = (float) SettingsHelper::get('lmd_seuil_validation_ecue', 10);

        $eligibles = collect();

        DB::transaction(function () use ($sessionNormale, $seuil, &$eligibles) {
            $bulletins = $sessionNormale->parcours?->bulletins ?? collect();
            $bulletinIds = $bulletins->pluck('id');

            $query = ESBTPLMDResultatECUE::query();
            if ($bulletinIds->isNotEmpty()) {
                $query->whereIn('bulletin_id', $bulletinIds);
            }

            $resultats = $query->get();

            foreach ($resultats as $r) {
                if ($r->note_session_normale === null && $r->moyenne !== null) {
                    $r->note_session_normale = $r->moyenne;
                }
                $r->rattrapage_eligible = ($r->moyenne !== null && (float) $r->moyenne < $seuil);
                $r->save();

                if ($r->rattrapage_eligible) {
                    $eligibles->push($r);
                }
            }
        });

        return $eligibles;
    }

    /**
     * Génère un examen rattrapage par ECUE éligible × classe distinctes.
     */
    public function genererExamensRattrapage(
        ESBTPLMDSession $sessionRattrapage,
        ?Carbon $datePremier = null
    ): Collection {
        if ($sessionRattrapage->type !== 'rattrapage') {
            throw new \DomainException('Session doit être de type rattrapage.');
        }

        $parent = $sessionRattrapage->parentSession;
        if (! $parent) {
            throw new \DomainException('Pas de session parent — impossible de retrouver les ECUE éligibles.');
        }

        $eligibles = $this->identifierEtudiantsEligibles($parent);

        // Group eligibility by (classe + matiere)
        $created = collect();
        $base = $datePremier ?? ($sessionRattrapage->date_debut?->copy() ?? now()->addWeeks(2));

        $byScope = $eligibles->groupBy(fn ($r) => $this->scopeKey($r));

        $offset = 0;
        DB::transaction(function () use ($byScope, $sessionRattrapage, $base, &$created, &$offset) {
            foreach ($byScope as $key => $items) {
                $first = $items->first();
                $matiereId = $first->matiere_id;
                $etudiantIds = $items->pluck('etudiant_id')->unique();
                $classeIds = ESBTPInscription::whereIn('etudiant_id', $etudiantIds)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree')
                    ->pluck('classe_id')
                    ->unique();

                foreach ($classeIds as $classeId) {
                    $existing = ESBTPExamenPlanifie::where([
                        'classe_id' => $classeId,
                        'matiere_id' => $matiereId,
                        'session_id' => $sessionRattrapage->id,
                        'type_examen' => 'RATTRAPAGE',
                    ])->first();

                    if ($existing) {
                        continue;
                    }

                    $debut = $base->copy()->addDays($offset);
                    $exam = ESBTPExamenPlanifie::create([
                        'annee_universitaire_id' => $sessionRattrapage->annee_universitaire_id,
                        'classe_id' => $classeId,
                        'matiere_id' => $matiereId,
                        'semestre' => $sessionRattrapage->semestre,
                        'session_id' => $sessionRattrapage->id,
                        'parcours_id' => $sessionRattrapage->parcours_id,
                        'type_examen' => 'RATTRAPAGE',
                        'titre' => $this->buildTitre($matiereId, $sessionRattrapage->semestre),
                        'date_debut' => $debut->copy()->setTime(9, 0),
                        'date_fin' => $debut->copy()->setTime(11, 0),
                        'duree_minutes' => 120,
                        'coefficient' => 1,
                        'bareme' => 20,
                        'status' => 'planned',
                        'created_by' => optional(auth()->user())->id,
                    ]);
                    $exam->numero_convocation = $this->examenScheduler->genererNumeroConvocation($exam);
                    $exam->save();
                    $created->push($exam);
                    $offset++;
                }
            }
        });

        return $created;
    }

    /**
     * Recalcule note_finale pour les ECUE de l'étudiant dans la session rattrapage.
     * Setting `lmd_rattrapage_replace` (default false) :
     *   - false : note_finale = max(normale, rattrapage)
     *   - true  : note_finale = rattrapage (remplace)
     */
    public function recalculerMoyennesAvecRattrapage(
        int $etudiantId,
        ESBTPLMDSession $sessionRattrapage
    ): int {
        $replace = (bool) SettingsHelper::get('lmd_rattrapage_replace', false);
        $parent = $sessionRattrapage->parentSession;
        if (! $parent) {
            return 0;
        }

        $bulletinIds = $parent->parcours?->bulletins?->pluck('id') ?? collect();
        if ($bulletinIds->isEmpty()) {
            return 0;
        }

        $resultats = ESBTPLMDResultatECUE::query()
            ->whereIn('bulletin_id', $bulletinIds)
            ->where('etudiant_id', $etudiantId)
            ->where('rattrapage_eligible', true)
            ->get();

        $updated = 0;
        foreach ($resultats as $r) {
            if ($r->note_rattrapage === null) {
                continue;
            }
            $normale = $r->note_session_normale;
            $finale = $replace
                ? (float) $r->note_rattrapage
                : max((float) ($normale ?? 0), (float) $r->note_rattrapage);

            $r->note_finale = $finale;
            $r->moyenne = $finale;
            $r->save();
            $updated++;
        }

        return $updated;
    }

    /**
     * Marque les étudiants éligibles comme inscrits en rattrapage (idempotent).
     * Renvoie le nombre marqué.
     */
    public function inscrireEtudiantsEligibles(ESBTPLMDSession $sessionRattrapage, ?array $etudiantIds = null): int
    {
        $parent = $sessionRattrapage->parentSession;
        if (! $parent) {
            return 0;
        }

        $bulletinIds = $parent->parcours?->bulletins?->pluck('id') ?? collect();
        if ($bulletinIds->isEmpty()) {
            return 0;
        }

        $query = ESBTPLMDResultatECUE::query()
            ->whereIn('bulletin_id', $bulletinIds)
            ->where('rattrapage_eligible', true);

        if ($etudiantIds !== null) {
            $query->whereIn('etudiant_id', $etudiantIds);
        }

        return $query->update(['rattrapage_inscrit' => true]);
    }

    private function scopeKey(ESBTPLMDResultatECUE $r): string
    {
        return sprintf('%d::%d', $r->etudiant_id, $r->matiere_id);
    }

    private function buildTitre(int $matiereId, ?int $semestre): string
    {
        $name = ESBTPMatiere::find($matiereId)?->name ?? 'Matière';

        return sprintf('Rattrapage - %s - S%s', $name, $semestre ?? '');
    }
}
