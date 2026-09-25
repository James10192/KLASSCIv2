<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Http\Controllers\AcademicPilotage\AcademicCoverageController;
use App\Models\ESBTPClasse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * La vue d'ensemble du pilotage pédagogique : quelle classe n'a pas toutes ses
 * notes, qui relancer, qui ne vient plus en cours.
 *
 * TOUT CHIFFRE DE NOTES VIENT DE LA COUVERTURE, et de la même entrée de cache
 * que le bandeau « Notes reçues » posé sur huit écrans. Ce tableau de bord ne
 * recalcule rien de son côté : un « 12 notes manquantes » ici et un « 14 »
 * sur la saisie seraient deux vérités, et la direction relancerait sur la
 * mauvaise.
 *
 * Il ne dépend PAS des instantanés du moteur de santé. L'ancien tableau de
 * bord restait vide tant que personne n'avait « synchronisé » chaque classe ;
 * la couverture, elle, se calcule sur les notes telles qu'elles sont.
 */
final class ApercuDuPilotage
{
    private const TTL_COUVERTURE = 600;

    public function __construct(
        private readonly AcademicNoteCoverageService $couverture,
        private readonly AcademicPeriodNormalizer $periodes,
        private readonly SeuilsDePilotage $seuils,
        private readonly PresenceDuPerimetre $presence,
    ) {}

    /**
     * La période ouverte par défaut : celle de la dernière évaluation passée.
     *
     * Déduite des données, pas du calendrier : une école qui commence son
     * second semestre en janvier et une autre qui le commence en mars ne
     * tombent pas au même mois.
     */
    public function periodeCourante(int $anneeId, ?Collection $classesAutorisees): string
    {
        $brute = DB::table('esbtp_evaluations')
            ->where('annee_universitaire_id', $anneeId)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->where('date_evaluation', '<=', now())
            ->when($classesAutorisees !== null, fn ($q) => $q->whereIn('classe_id', $classesAutorisees))
            ->orderByDesc('date_evaluation')
            ->value('periode');

        try {
            return $brute ? $this->periodes->normalize($brute) : 'semestre1';
        } catch (\InvalidArgumentException) {
            return 'semestre1';
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function construire(int $anneeId, string $periode, ?string $systeme, ?int $classeId, ?Collection $classesAutorisees): array
    {
        $classes = $this->classes($anneeId, $systeme, $classeId, $classesAutorisees);
        $presences = $this->presence->parClasse($anneeId, $periode, $classes->pluck('id'));
        $lignes = $classes->map(fn (ESBTPClasse $classe) => $this->ligneDeClasse($classe, $anneeId, $periode, $presences))->values();
        $relances = $this->relances($lignes);

        return [
            'periode' => $periode,
            'fenetre_presence' => $this->presence->fenetre($anneeId, $periode),
            'kpis' => $this->indicateurs($lignes, $relances, $presences),
            'relances' => $relances->take(12)->values()->all(),
            'relances_total' => $relances->count(),
            'saisies_en_cours' => (int) $lignes->sum('evaluations_recentes'),
            'classes' => $this->trierLesClasses($lignes)->all(),
            'etudiants' => $this->presence->etudiantsSousLeSeuil($anneeId, $periode, $classes->pluck('id'), $this->seuils->presenceMinimale()),
            'tendance' => $this->presence->tendanceMensuelle($anneeId, $classes->pluck('id')),
            'seuils' => [
                'relance_jours' => $this->seuils->relanceApresJours(),
                'presence_min' => $this->seuils->presenceMinimale(),
            ],
            'calcule_a' => now()->toIso8601String(),
        ];
    }

    /**
     * Les classes qui ont des inscrits cette année. L'année se lit sur
     * l'INSCRIPTION, jamais sur la classe (classes universelles).
     */
    private function classes(int $anneeId, ?string $systeme, ?int $classeId, ?Collection $autorisees): Collection
    {
        return ESBTPClasse::query()
            ->without(['filiere', 'niveau', 'annee'])
            ->where('is_active', true)
            ->whereHas('inscriptions', fn ($q) => $q->where('annee_universitaire_id', $anneeId)->where('status', 'active'))
            ->when($systeme !== null, fn ($q) => $systeme === 'LMD'
                ? $q->where('systeme_academique', 'LMD')
                : $q->where(fn ($w) => $w->where('systeme_academique', '!=', 'LMD')->orWhereNull('systeme_academique')))
            ->when($classeId !== null, fn ($q) => $q->whereKey($classeId))
            ->when($autorisees !== null, fn ($q) => $q->whereIn('id', $autorisees))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'systeme_academique']);
    }

    /**
     * @param  array<int, array{taux: float|null, appels: int}>  $presences
     * @return array<string, mixed>
     */
    private function ligneDeClasse(ESBTPClasse $classe, int $anneeId, string $periode, array $presences): array
    {
        $constat = Cache::remember(
            AcademicCoverageController::cle((int) $classe->id, $anneeId, $periode),
            self::TTL_COUVERTURE,
            fn () => $this->couverture->summarize($anneeId, $periode, null, (int) $classe->id),
        );

        $resume = $constat['summary'] ?? [];
        $matieres = collect($constat['subjects'] ?? [])->reject(fn ($m) => (bool) ($m['is_orphan'] ?? false));
        $attendues = (int) ($resume['expected_results'] ?? 0);
        $recues = (int) ($resume['treated_results'] ?? 0);
        [$enRetard, $recentes] = $this->evaluationsIncompletes($matieres);

        return [
            'id' => (int) $classe->id,
            'nom' => $classe->code && $classe->code !== $classe->name ? "{$classe->code} · {$classe->name}" : (string) $classe->name,
            'systeme' => $classe->systeme_academique === 'LMD' ? 'LMD' : 'BTS',
            'etat' => (string) ($resume['state'] ?? 'indisponible'),
            'message' => $constat['message'] ?? null,
            'attendues' => $attendues,
            'recues' => $recues,
            'manquantes' => (int) ($resume['missing_results'] ?? 0),
            'pourcentage' => $attendues > 0 ? (int) floor($recues / $attendues * 100) : null,
            'matieres_total' => (int) ($resume['subjects_total'] ?? 0),
            'matieres_sans_evaluation' => $matieres->where('statut', 'non_evaluee')->count(),
            'evaluations' => (int) ($resume['evaluations_total'] ?? 0),
            'evaluations_sans_note' => $matieres->flatMap(fn ($m) => $m['evaluations'] ?? [])->where('treated_count', 0)->count(),
            'en_retard' => $enRetard->all(),
            'evaluations_recentes' => $recentes,
            'presence' => $presences[(int) $classe->id]['taux'] ?? null,
        ];
    }

    /**
     * Les évaluations passées dont il manque des notes, séparées en deux :
     * celles qu'on relance (au-delà du délai de l'école) et celles qu'on
     * laisse à l'enseignant le temps de corriger.
     *
     * @return array{0: Collection<int, array<string, mixed>>, 1: int}
     */
    private function evaluationsIncompletes(Collection $matieres): array
    {
        $limite = now()->startOfDay()->subDays($this->seuils->relanceApresJours());
        $enRetard = collect();
        $recentes = 0;

        foreach ($matieres as $matiere) {
            $incompletes = collect($matiere['evaluations'] ?? [])->where('missing_count', '>', 0);
            $anciennes = $incompletes->filter(fn ($e) => $e['date'] && Carbon::parse($e['date'])->lte($limite));
            $recentes += $incompletes->count() - $anciennes->count();

            if ($anciennes->isEmpty()) {
                continue;
            }

            $enRetard->push([
                'matiere_id' => $matiere['id'] ?? null,
                'matiere' => $matiere['name'] ?? 'Matière',
                'manquantes' => (int) $anciennes->sum('missing_count'),
                'evaluations' => $anciennes->count(),
                'plus_ancienne' => $anciennes->min('date'),
                'enseignant' => $matiere['enseignant'] ?? null,
            ]);
        }

        return [$enRetard, $recentes];
    }

    /**
     * La file de travail : une ligne par classe et matière à relancer, la plus
     * ancienne d'abord. Le contact vient de la couverture, donc du même
     * lecteur que le bandeau « Notes reçues » (planning, bulletin, évaluation).
     */
    private function relances(Collection $lignes): Collection
    {
        return $lignes
            ->flatMap(fn (array $classe) => collect($classe['en_retard'])->map(fn (array $r) => [
                'classe_id' => $classe['id'],
                'classe' => $classe['nom'],
                'matiere_id' => $r['matiere_id'],
                'matiere' => $r['matiere'],
                'manquantes' => $r['manquantes'],
                'evaluations' => $r['evaluations'],
                'plus_ancienne' => $r['plus_ancienne'],
                'depuis_jours' => $r['plus_ancienne'] ? (int) Carbon::parse($r['plus_ancienne'])->diffInDays(now()) : null,
                'contact' => $r['enseignant'],
            ]))
            ->sortByDesc('depuis_jours')
            ->values();
    }

    /**
     * @param  array<int, array{taux: float|null, appels: int}>  $presences
     * @return array<string, mixed>
     */
    private function indicateurs(Collection $lignes, Collection $relances, array $presences): array
    {
        $mesurables = $lignes->whereNotIn('etat', ['indisponible', 'referentiel_absent', 'cohorte_vide', 'aucune_matiere_ce_semestre']);
        $appels = collect($presences)->sum('appels');
        $presents = collect($presences)->sum('presents');

        return [
            'classes_a_jour' => $mesurables->where('etat', 'complete')->count(),
            'classes_mesurables' => $mesurables->count(),
            'classes_total' => $lignes->count(),
            'notes_manquantes' => (int) $lignes->sum('manquantes'),
            'notes_attendues' => (int) $lignes->sum('attendues'),
            'relances' => $relances->count(),
            'enseignants_a_relancer' => $relances->map(fn ($r) => $r['contact']['name'] ?? null)->filter()->unique()->count(),
            'presence' => $appels > 0 ? round($presents / $appels * 100, 1) : null,
            'appels' => (int) $appels,
        ];
    }

    /**
     * Les classes à traiter d'abord : celles à qui il manque le plus de notes
     * en proportion, puis celles sans rien d'évalué, puis les complètes.
     */
    private function trierLesClasses(Collection $lignes): Collection
    {
        $rang = ['incomplete' => 0, 'aucune_evaluation' => 1, 'evaluations_programmees' => 2, 'complete' => 4];

        return $lignes->sortBy([
            fn ($a, $b) => ($rang[$a['etat']] ?? 3) <=> ($rang[$b['etat']] ?? 3),
            fn ($a, $b) => ($a['pourcentage'] ?? 101) <=> ($b['pourcentage'] ?? 101),
            fn ($a, $b) => strcmp($a['nom'], $b['nom']),
        ])->values();
    }
}
