<?php

namespace App\Services\LMD;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\User;
use App\Services\LMDBulletinService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Source canonique de l'enseignant d'un ECUE LMD.
 *
 * La maquette dit QUEL ECUE appartient au semestre. La planification académique
 * dit QUI l'enseigne. Les évaluations et les bulletins consomment cette source ;
 * ils ne maintiennent pas une troisième affectation concurrente.
 */
final class EnseignantDePlanificationLmd
{
    /** @var array<string, array<string, mixed>> */
    private static array $memo = [];

    public function __construct(
        private readonly MatiereTreeBuilder $matieres,
        private readonly LMDBulletinService $bulletins,
    ) {}

    /**
     * @return array{
     *   applicable: bool,
     *   dans_maquette: bool,
     *   semestre: int|null,
     *   planification: ESBTPPlanificationAcademique|null,
     *   enseignants: Collection<int, User>,
     *   enseignant_principal: User|null,
     *   enseignant_id: int|null,
     *   noms: string,
     *   source: string,
     *   message: string|null
     * }
     */
    public function resoudre(
        ESBTPClasse $classe,
        int $matiereId,
        int $anneeId,
        mixed $periode,
        bool $avecRepliEvaluations = false,
    ): array {
        if (($classe->systeme_academique ?? '') !== 'LMD') {
            return $this->vide(false, null, 'Cette classe n’est pas LMD.');
        }

        $semestre = $this->numeroSemestre($periode);
        if ($semestre === null || ! in_array($semestre, $classe->getSemestresLMD(), true)) {
            return $this->vide(true, $semestre, 'Le semestre ne correspond pas au niveau LMD de la classe.');
        }

        $cacheKey = implode(':', [$classe->id, $matiereId, $anneeId, $semestre, (int) $avecRepliEvaluations]);
        if (! app()->runningInConsole() && array_key_exists($cacheKey, self::$memo)) {
            return self::$memo[$cacheKey];
        }

        $dansMaquette = $this->estDansMaquette($classe, $matiereId, $semestre);
        if (! $dansMaquette) {
            return $this->memoriser($cacheKey, $this->vide(
                true,
                $semestre,
                'Cet ECUE ne figure pas dans la maquette LMD de cette classe pour ce semestre.'
            ));
        }

        $planification = $this->planification($classe, $matiereId, $anneeId, $semestre);
        $enseignants = $this->enseignantsDeLaPlanification($planification);

        if ($enseignants->isNotEmpty()) {
            $principal = $planification?->enseignantPrincipal;
            $principal ??= $enseignants->first();

            return $this->memoriser($cacheKey, [
                'applicable' => true,
                'dans_maquette' => true,
                'semestre' => $semestre,
                'planification' => $planification,
                'enseignants' => $enseignants,
                'enseignant_principal' => $principal,
                'enseignant_id' => $principal?->id,
                'noms' => $enseignants->pluck('name')->filter()->unique()->implode(' / '),
                'source' => 'planning',
                'message' => null,
            ]);
        }

        if ($avecRepliEvaluations) {
            $fallback = $this->enseignantsDesEvaluations($classe, $matiereId, $anneeId, $semestre);
            if ($fallback['noms'] !== '') {
                return $this->memoriser($cacheKey, [
                    'applicable' => true,
                    'dans_maquette' => true,
                    'semestre' => $semestre,
                    'planification' => $planification,
                    'enseignants' => $fallback['enseignants'],
                    'enseignant_principal' => $fallback['enseignants']->first(),
                    'enseignant_id' => $fallback['enseignant_id'],
                    'noms' => $fallback['noms'],
                    'source' => 'evaluations',
                    'message' => 'Aucun enseignant n’est affecté dans le planning ; repli sur les évaluations existantes.',
                ]);
            }
        }

        return $this->memoriser($cacheKey, [
            'applicable' => true,
            'dans_maquette' => true,
            'semestre' => $semestre,
            'planification' => $planification,
            'enseignants' => collect(),
            'enseignant_principal' => null,
            'enseignant_id' => null,
            'noms' => '',
            'source' => 'aucun',
            'message' => $planification
                ? 'Aucun enseignant principal n’est affecté à cet ECUE dans le planning LMD.'
                : 'Cet ECUE n’a pas encore de ligne de planification pour ce semestre.',
        ]);
    }

    /**
     * Assigne l'enseignant principal sur une ligne de planning EXISTANTE.
     *
     * L'édition rapide ne crée volontairement pas une planification pédagogique
     * vide : si la ligne n'existe pas, l'utilisateur doit passer par le planning
     * LMD, qui sait initialiser crédits, volumes et portée de parcours.
     */
    public function assignerPrincipal(
        ESBTPClasse $classe,
        int $matiereId,
        int $anneeId,
        mixed $periode,
        int $enseignantId,
        int $auteurId,
    ): ESBTPPlanificationAcademique {
        $resolution = $this->resoudre($classe, $matiereId, $anneeId, $periode, false);
        if (! $resolution['applicable'] || ! $resolution['dans_maquette']) {
            throw ValidationException::withMessages(['matiere_id' => $resolution['message'] ?? 'ECUE LMD invalide.']);
        }

        $user = User::find($enseignantId);
        if (! $user || ! $user->hasRole('enseignant')) {
            throw ValidationException::withMessages(['enseignant_id' => 'L’utilisateur choisi n’est pas un enseignant.']);
        }

        /** @var ESBTPPlanificationAcademique|null $planification */
        $planification = $resolution['planification'];
        if (! $planification) {
            throw ValidationException::withMessages([
                'planification' => 'Configurez d’abord cet ECUE dans le planning LMD ; l’affectation rapide ne crée pas une planification vide.',
            ]);
        }

        DB::transaction(function () use ($planification, $enseignantId, $auteurId): void {
            $locked = ESBTPPlanificationAcademique::whereKey($planification->id)->lockForUpdate()->firstOrFail();
            $locked->enseignant_principal_id = $enseignantId;
            $locked->updated_by = $auteurId;
            $locked->save();
        });

        $this->oublier($classe, $matiereId, $anneeId, $resolution['semestre']);

        return ESBTPPlanificationAcademique::with('enseignantPrincipal:id,name')->findOrFail($planification->id);
    }

    /**
     * ECUE du semestre avec leur affectation planning, pour écran bulletin / API.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function elementsDuSemestre(ESBTPClasse $classe, int $anneeId, int $semestre): Collection
    {
        $ues = $this->bulletins->getUEsForSemestre($classe, $semestre);
        $parcoursId = $classe->parcours_id ? (int) $classe->parcours_id : null;

        return $ues->flatMap(function ($ue) use ($classe, $anneeId, $semestre, $parcoursId) {
            return $ue->getEcuesEffectifs($parcoursId)->map(function (ESBTPMatiere $ecue) use ($ue, $classe, $anneeId, $semestre) {
                $r = $this->resoudre($classe, (int) $ecue->id, $anneeId, $semestre, true);

                return [
                    'matiere_id' => (int) $ecue->id,
                    'code' => $ecue->code,
                    'name' => $ecue->name,
                    'ue' => trim((string) (($ue->code_affiche ?? $ue->code ?? '').' — '.$ue->name), ' —'),
                    'semestre' => $semestre,
                    'planification_id' => $r['planification']?->id,
                    'enseignant_id' => $r['source'] === 'planning' ? $r['enseignant_id'] : null,
                    'enseignant_nom' => $r['noms'],
                    'source_enseignant' => $r['source'],
                    'assignable_rapidement' => $r['planification'] !== null,
                    'message' => $r['message'],
                ];
            });
        })->unique('matiere_id')->values();
    }

    private function estDansMaquette(ESBTPClasse $classe, int $matiereId, int $semestre): bool
    {
        // La rule lmd-bts-matieres-single-source impose MatiereTreeBuilder pour
        // la liste des matières d'une classe. Le bulletin tranche ensuite le
        // semestre exact (notamment UE partagées / réservées par parcours).
        $arbre = $this->matieres->loadLmdMatieresForClasse($classe);
        $treeIds = $arbre->pluck('matiere.id')->map(fn ($id) => (int) $id);
        if ($treeIds->isNotEmpty() && ! $treeIds->contains($matiereId)) {
            return false;
        }

        $parcoursId = $classe->parcours_id ? (int) $classe->parcours_id : null;
        $semestreIds = $this->bulletins->getUEsForSemestre($classe, $semestre)
            ->flatMap(fn ($ue) => $ue->getEcuesEffectifs($parcoursId))
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        return $semestreIds->contains($matiereId);
    }

    private function planification(ESBTPClasse $classe, int $matiereId, int $anneeId, int $semestre): ?ESBTPPlanificationAcademique
    {
        $filiereId = $classe->parcours?->filiere_id ?: $classe->filiere_id;
        if (! $filiereId || ! $classe->niveau_etude_id) {
            return null;
        }

        return ESBTPPlanificationAcademique::query()
            ->where('annee_universitaire_id', $anneeId)
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('matiere_id', $matiereId)
            ->where('semestre', $semestre)
            ->where('is_active', true)
            ->with(['enseignantPrincipal:id,name', 'teachers.user:id,name'])
            ->first();
    }

    /** @return Collection<int, User> */
    private function enseignantsDeLaPlanification(?ESBTPPlanificationAcademique $planification): Collection
    {
        if (! $planification) {
            return collect();
        }

        $users = collect();
        if ($planification->enseignantPrincipal) {
            $users->push($planification->enseignantPrincipal);
        }

        $secondaires = collect($planification->enseignants_secondaires ?? [])->filter()->map(fn ($id) => (int) $id);
        if ($secondaires->isNotEmpty()) {
            $users = $users->merge(User::whereIn('id', $secondaires)->get(['id', 'name']));
        }

        foreach ($planification->teachers as $teacher) {
            if ($teacher->user) {
                $users->push($teacher->user);
            }
        }

        return $users->filter()->unique('id')->values();
    }

    /** @return array{enseignants: Collection<int, User>, enseignant_id: int|null, noms: string} */
    private function enseignantsDesEvaluations(ESBTPClasse $classe, int $matiereId, int $anneeId, int $semestre): array
    {
        $periodes = [(string) $semestre, 'semestre'.$semestre, 'S'.$semestre, 'Semestre '.$semestre, 'semestre '.$semestre];
        $evaluations = ESBTPEvaluation::query()
            ->where('classe_id', $classe->id)
            ->where('matiere_id', $matiereId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('periode', $periodes)
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->with('enseignant:id,name')
            ->orderBy('date_evaluation')
            ->orderBy('id')
            ->get();

        $users = $evaluations->pluck('enseignant')->filter()->unique('id')->values();
        $noms = $evaluations->map(fn (ESBTPEvaluation $evaluation) => trim((string) (
            $evaluation->enseignant?->name ?: $evaluation->enseignant_externe_nom ?: ''
        )))->filter()->unique(fn (string $nom) => mb_strtolower($nom, 'UTF-8'))->values();

        return [
            'enseignants' => $users,
            'enseignant_id' => $users->first()?->id,
            'noms' => $noms->implode(' / '),
        ];
    }

    private function numeroSemestre(mixed $periode): ?int
    {
        if (is_int($periode) || (is_string($periode) && preg_match('/^\d{1,2}$/', $periode))) {
            return (int) $periode;
        }

        return is_string($periode) && preg_match('/(\d{1,2})/', $periode, $m) ? (int) $m[1] : null;
    }

    private function oublier(ESBTPClasse $classe, int $matiereId, int $anneeId, ?int $semestre): void
    {
        if ($semestre === null) {
            return;
        }
        $prefix = implode(':', [$classe->id, $matiereId, $anneeId, $semestre]);
        foreach (array_keys(self::$memo) as $key) {
            if (str_starts_with($key, $prefix.':')) {
                unset(self::$memo[$key]);
            }
        }
    }

    private function memoriser(string $key, array $value): array
    {
        if (! app()->runningInConsole()) {
            self::$memo[$key] = $value;
        }

        return $value;
    }

    private function vide(bool $applicable, ?int $semestre, ?string $message): array
    {
        return [
            'applicable' => $applicable,
            'dans_maquette' => false,
            'semestre' => $semestre,
            'planification' => null,
            'enseignants' => collect(),
            'enseignant_principal' => null,
            'enseignant_id' => null,
            'noms' => '',
            'source' => 'aucun',
            'message' => $message,
        ];
    }
}
