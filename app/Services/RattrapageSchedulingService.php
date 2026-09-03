<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPExamenPlanifie;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDResultatECUE;
use App\Models\ESBTPLMDResultatUE;
use App\Models\ESBTPLMDSession;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPLMDJury;
use App\Models\ESBTPMatiere;
use App\Services\LMD\LmdAcademicRuleProfile;
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
    /**
     * Bareme de dernier recours, aligne sur la convention du projet (note sur 20).
     *
     * Il ne sert que si l'examen de rattrapage planifie ne porte pas de bareme : la
     * borne haute reelle d'une note se lit toujours d'abord sur l'examen concerne.
     */
    public const BAREME_PAR_DEFAUT = 20.0;

    private readonly LmdAcademicRuleProfile $rules;

    public function __construct(
        private readonly ExamenSchedulingService $examenScheduler,
        ?LmdAcademicRuleProfile $rules = null,
    ) {
        $this->rules = $rules ?? new LmdAcademicRuleProfile();
    }

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
     *
     * Deux portées possibles, pilotées par le réglage `lmd_rattrapage_scope` :
     * - `ecue` (défaut) : seuls les ECUE sous le seuil de validation sont à repasser ;
     * - `ue` : tous les ECUE d'une UE non acquise sont à repasser, et un ECUE faible
     *   d'une UE acquise (y compris par compensation) ne l'est pas.
     *
     * Le seuil est lu via LmdAcademicRuleProfile (clé d'écran `lmd_validation_threshold`,
     * repli sur l'ancienne clé `lmd_seuil_validation_ecue`).
     *
     * @return Collection<int, ESBTPLMDResultatECUE>
     */
    public function identifierEtudiantsEligibles(ESBTPLMDSession $sessionNormale): Collection
    {
        $seuil = $this->rules->validationThreshold();
        $portee = $this->rules->rattrapageScope();

        $eligibles = collect();

        DB::transaction(function () use ($sessionNormale, $seuil, $portee, &$eligibles) {
            $bulletins = $this->bulletinsForSession($sessionNormale);
            $this->assertResultsMutable($sessionNormale, $sessionNormale, $bulletins);
            $bulletinIds = $bulletins->pluck('id');

            $resultats = $bulletinIds->isEmpty()
                ? collect()
                : ESBTPLMDResultatECUE::query()->whereIn('bulletin_id', $bulletinIds)->get();

            $uesNonAcquises = $portee === LmdAcademicRuleProfile::RATTRAPAGE_SCOPE_UE
                ? $this->uesNonAcquises($bulletinIds)
                : collect();

            foreach ($resultats as $r) {
                if ($r->note_session_normale === null && $r->moyenne !== null) {
                    $r->note_session_normale = $r->moyenne;
                }

                if ($portee === LmdAcademicRuleProfile::RATTRAPAGE_SCOPE_UE && $r->resultat_ue_id !== null) {
                    // Toute l'UE non acquise se repasse ; une UE acquise ne se repasse pas.
                    $r->rattrapage_eligible = $uesNonAcquises->contains((int) $r->resultat_ue_id);
                } else {
                    // Portée ECUE, ou ECUE sans UE rattachée : on retombe sur le seuil.
                    $r->rattrapage_eligible = ($r->moyenne !== null && (float) $r->moyenne < $seuil);
                }

                $r->save();

                if ($r->rattrapage_eligible) {
                    $eligibles->push($r);
                }
            }
        });

        return $eligibles;
    }

    /**
     * Identifiants des résultats d'UE non acquis pour les bulletins donnés.
     *
     * @param  Collection<int, int>  $bulletinIds
     * @return Collection<int, int>
     */
    private function uesNonAcquises(Collection $bulletinIds): Collection
    {
        if ($bulletinIds->isEmpty()) {
            return collect();
        }

        return ESBTPLMDResultatUE::query()
            ->whereIn('bulletin_id', $bulletinIds)
            ->where('statut', ESBTPLMDResultatUE::STATUT_NAQ)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id);
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

        $bulletins = $this->bulletinsForSession($parent)->keyBy('id');
        $byScope = $eligibles
            ->filter(fn (ESBTPLMDResultatECUE $resultat): bool => $bulletins->has($resultat->bulletin_id))
            ->groupBy(fn (ESBTPLMDResultatECUE $resultat): string => sprintf('%d::%d', $bulletins->get($resultat->bulletin_id)->classe_id, $resultat->matiere_id));

        $offset = 0;
        DB::transaction(function () use ($byScope, $bulletins, $sessionRattrapage, $base, &$created, &$offset) {
            foreach ($byScope as $key => $items) {
                $first = $items->first();
                $matiereId = $first->matiere_id;
                $classeId = $bulletins->get($first->bulletin_id)->classe_id;
                $etudiantIds = $items->pluck('etudiant_id')->unique();
                $hasEligibleStudent = ESBTPInscription::whereIn('etudiant_id', $etudiantIds)
                    ->where('classe_id', $classeId)
                    ->where('annee_universitaire_id', $sessionRattrapage->annee_universitaire_id)
                    ->where('status', 'active')
                    ->where('workflow_step', 'etudiant_cree')
                    ->exists();

                if ($hasEligibleStudent) {
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
                        'bareme' => self::BAREME_PAR_DEFAUT,
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

        $bulletins = $this->bulletinsForSession($parent);
        $bulletinIds = $bulletins->pluck('id');
        if ($bulletinIds->isEmpty()) {
            return 0;
        }

        $this->assertResultsMutable($sessionRattrapage, $parent, $bulletins);

        $resultats = ESBTPLMDResultatECUE::query()
            ->whereIn('bulletin_id', $bulletinIds)
            ->where('etudiant_id', $etudiantId)
            ->where('rattrapage_eligible', true)
            ->where('rattrapage_inscrit', true)
            ->get();

        $updated = 0;
        foreach ($resultats as $r) {
            if ($r->note_rattrapage === null) {
                continue;
            }
            $normale = $r->note_session_normale;
            $finale = $replace
                ? (float) $r->note_rattrapage
                : ($normale === null
                    ? (float) $r->note_rattrapage
                    : max((float) $normale, (float) $r->note_rattrapage));

            $r->note_finale = $finale;
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

        $bulletins = $this->bulletinsForSession($parent);
        $bulletinIds = $bulletins->pluck('id');
        if ($bulletinIds->isEmpty()) {
            return 0;
        }

        $this->assertResultsMutable($sessionRattrapage, $parent, $bulletins);

        $query = ESBTPLMDResultatECUE::query()
            ->whereIn('bulletin_id', $bulletinIds)
            ->where('rattrapage_eligible', true);

        if ($etudiantIds !== null) {
            $query->whereIn('etudiant_id', $etudiantIds);
        }

        return $query->update(['rattrapage_inscrit' => true]);
    }

    /**
     * Lignes de saisie de seconde session : un ECUE eligible ET inscrit par etudiant.
     *
     * Seules les lignes reellement inscrites au rattrapage sont saisissables, car ce sont
     * les seules que le recalcul de la note finale prend en compte.
     *
     * @return Collection<int, ESBTPLMDResultatECUE>
     */
    public function lignesSaisieRattrapage(ESBTPLMDSession $sessionRattrapage): Collection
    {
        if ($sessionRattrapage->type !== 'rattrapage') {
            throw new \DomainException('La saisie de notes est reservee aux sessions de rattrapage.');
        }

        $parent = $sessionRattrapage->parentSession;
        if (! $parent) {
            throw new \DomainException('Session de rattrapage sans session parent : impossible de retrouver les resultats.');
        }

        $bulletinIds = $this->bulletinsForSession($parent)->pluck('id');
        if ($bulletinIds->isEmpty()) {
            return collect();
        }

        return ESBTPLMDResultatECUE::query()
            ->with(['etudiant:id,nom,prenoms,matricule', 'matiere:id,name,code', 'bulletin:id,classe_id,etudiant_id', 'bulletin.classe:id,name', 'updatedBy:id,name'])
            ->whereIn('bulletin_id', $bulletinIds)
            ->where('rattrapage_eligible', true)
            ->where('rattrapage_inscrit', true)
            ->get()
            ->sortBy([
                fn (ESBTPLMDResultatECUE $r): string => (string) optional($r->etudiant)->nom,
                fn (ESBTPLMDResultatECUE $r): string => (string) optional($r->etudiant)->prenoms,
                fn (ESBTPLMDResultatECUE $r): string => (string) optional($r->matiere)->name,
            ])
            ->values();
    }

    /**
     * Enregistre les notes de seconde session puis recalcule les notes finales.
     *
     * Chaque entree est un couple {resultat_id, note}. Une note nulle efface la note de
     * seconde session et la note finale correspondante (retour au seul resultat de la
     * premiere session).
     *
     * La borne haute de chaque note est le bareme de l'examen de rattrapage planifie pour
     * le couple (classe, ECUE) ; a defaut, le bareme par defaut du projet.
     *
     * @param  array<int, array{resultat_id: int|string, note: float|int|string|null}>  $notes
     * @return array{saisies: int, effacees: int, recalculees: int, ignorees: int}
     */
    public function saisirNotesRattrapage(ESBTPLMDSession $sessionRattrapage, array $notes): array
    {
        if ($sessionRattrapage->type !== 'rattrapage') {
            throw new \DomainException('La saisie de notes est reservee aux sessions de rattrapage.');
        }

        $parent = $sessionRattrapage->parentSession;
        if (! $parent) {
            throw new \DomainException('Session de rattrapage sans session parent : impossible de retrouver les resultats.');
        }

        $bulletins = $this->bulletinsForSession($parent);
        $this->assertResultsMutable($sessionRattrapage, $parent, $bulletins);

        $bulletinIds = $bulletins->pluck('id');
        if ($bulletinIds->isEmpty()) {
            throw new \DomainException('Aucun bulletin actif sur le perimetre de cette session.');
        }

        $saisies = 0;
        $effacees = 0;
        $ignorees = 0;
        $etudiantsTouches = collect();

        DB::transaction(function () use ($notes, $bulletinIds, $bulletins, &$saisies, &$effacees, &$ignorees, &$etudiantsTouches): void {
            $demandes = collect($notes)
                ->filter(fn ($ligne): bool => is_array($ligne) && isset($ligne['resultat_id']))
                ->keyBy(fn ($ligne): int => (int) $ligne['resultat_id']);

            if ($demandes->isEmpty()) {
                return;
            }

            $resultats = ESBTPLMDResultatECUE::query()
                ->whereIn('bulletin_id', $bulletinIds)
                ->whereIn('id', $demandes->keys())
                ->where('rattrapage_eligible', true)
                ->where('rattrapage_inscrit', true)
                ->lockForUpdate()
                ->get();

            $ignorees = $demandes->count() - $resultats->count();
            $baremes = $this->baremesRattrapage($resultats, $bulletins);

            foreach ($resultats as $resultat) {
                $brut = $demandes->get($resultat->id)['note'] ?? null;

                if ($brut === null || $brut === '') {
                    // Effacement : sans note de seconde session, il n'y a plus de note finale.
                    $resultat->note_rattrapage = null;
                    $resultat->note_finale = null;
                    $resultat->save();
                    $effacees++;
                    $etudiantsTouches->push((int) $resultat->etudiant_id);

                    continue;
                }

                if (! is_numeric($brut)) {
                    throw new \DomainException('Chaque note de seconde session doit etre un nombre.');
                }

                $note = (float) $brut;
                $bareme = (float) ($baremes[$resultat->id] ?? self::BAREME_PAR_DEFAUT);

                if ($note < 0 || $note > $bareme) {
                    throw new \DomainException(sprintf(
                        'La note de %s doit etre comprise entre 0 et %s.',
                        optional($resultat->matiere)->name ?? 'cet enseignement',
                        rtrim(rtrim(number_format($bareme, 2, ',', ' '), '0'), ',')
                    ));
                }

                $resultat->note_rattrapage = $note;
                $resultat->save();
                $saisies++;
                $etudiantsTouches->push((int) $resultat->etudiant_id);
            }
        });

        $etudiantsTouches = $etudiantsTouches->unique()->values();

        $recalculees = 0;
        foreach ($etudiantsTouches as $etudiantId) {
            $recalculees += $this->recalculerMoyennesAvecRattrapage((int) $etudiantId, $sessionRattrapage);
        }

        Log::info('[RattrapageSchedulingService] notes de seconde session enregistrees', [
            'session_id' => $sessionRattrapage->id,
            'saisies' => $saisies,
            'effacees' => $effacees,
            'ignorees' => $ignorees,
            'recalculees' => $recalculees,
            'etudiants' => $etudiantsTouches->all(),
            'user_id' => optional(auth()->user())->id,
        ]);

        return [
            'saisies' => $saisies,
            'effacees' => $effacees,
            'recalculees' => $recalculees,
            'ignorees' => $ignorees,
        ];
    }

    /**
     * Bareme applicable a chaque ligne, lu sur l'examen de rattrapage planifie.
     *
     * @param  Collection<int, ESBTPLMDResultatECUE>  $resultats
     * @param  Collection<int, ESBTPLMDBulletin>  $bulletins
     * @return array<int, float>
     */
    private function baremesRattrapage(Collection $resultats, Collection $bulletins): array
    {
        if ($resultats->isEmpty()) {
            return [];
        }

        $classeParBulletin = $bulletins->pluck('classe_id', 'id');

        $examens = ESBTPExamenPlanifie::query()
            ->where('type_examen', 'RATTRAPAGE')
            ->whereIn('classe_id', $classeParBulletin->unique()->filter()->values())
            ->whereIn('matiere_id', $resultats->pluck('matiere_id')->unique()->values())
            ->get(['classe_id', 'matiere_id', 'bareme'])
            ->keyBy(fn (ESBTPExamenPlanifie $examen): string => sprintf('%d::%d', $examen->classe_id, $examen->matiere_id));

        $baremes = [];
        foreach ($resultats as $resultat) {
            $classeId = $classeParBulletin->get($resultat->bulletin_id);
            $examen = $classeId ? $examens->get(sprintf('%d::%d', $classeId, $resultat->matiere_id)) : null;
            $bareme = $examen && (float) $examen->bareme > 0
                ? (float) $examen->bareme
                : self::BAREME_PAR_DEFAUT;
            $baremes[$resultat->id] = $bareme;
        }

        return $baremes;
    }

    private function bulletinsForSession(ESBTPLMDSession $session): Collection
    {
        if (! $session->annee_universitaire_id || ! $session->parcours_id || ! $session->semestre) {
            throw new \DomainException('Le perimetre annee, parcours et semestre de la session est obligatoire.');
        }

        return ESBTPLMDBulletin::query()
            ->where('annee_universitaire_id', $session->annee_universitaire_id)
            ->where('parcours_id', $session->parcours_id)
            ->where('semestre', $session->semestre)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('esbtp_inscriptions')
                    ->whereColumn('esbtp_inscriptions.etudiant_id', 'esbtp_lmd_bulletins.etudiant_id')
                    ->whereColumn('esbtp_inscriptions.classe_id', 'esbtp_lmd_bulletins.classe_id')
                    ->whereColumn('esbtp_inscriptions.annee_universitaire_id', 'esbtp_lmd_bulletins.annee_universitaire_id')
                    ->where('esbtp_inscriptions.status', 'active')
                    ->where('esbtp_inscriptions.workflow_step', 'etudiant_cree');
            })
            ->get();
    }

    private function assertResultsMutable(ESBTPLMDSession $sessionRattrapage, ESBTPLMDSession $sessionNormale, Collection $bulletins): void
    {
        if (in_array($sessionRattrapage->status, ['published', 'publie'], true)
            || in_array($sessionNormale->status, ['published', 'publie'], true)
            || $bulletins->contains(fn (ESBTPLMDBulletin $bulletin): bool => (bool) $bulletin->is_published)
            || ESBTPLMDJury::query()
                ->where('annee_universitaire_id', $sessionNormale->annee_universitaire_id)
                ->where('parcours_id', $sessionNormale->parcours_id)
                ->where('semestre', $sessionNormale->semestre)
                ->whereIn('classe_id', $bulletins->pluck('classe_id')->unique())
                ->where('status', 'publie')
                ->exists()) {
            throw new \LogicException('Les resultats de rattrapage sont verrouilles apres publication.');
        }
    }

    private function buildTitre(int $matiereId, ?int $semestre): string
    {
        $name = ESBTPMatiere::find($matiereId)?->name ?? 'Matière';

        return sprintf('Rattrapage - %s - S%s', $name, $semestre ?? '');
    }
}
