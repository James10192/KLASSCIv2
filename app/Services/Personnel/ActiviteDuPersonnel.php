<?php

declare(strict_types=1);

namespace App\Services\Personnel;

use App\Domain\AcademicPilotage\Services\SeuilsDePilotage;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPSeanceCours;
use App\Services\TeacherHoursService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * L'activité du personnel, en faits vérifiables — jamais en note.
 *
 * L'ancien score transformait des volumes d'écriture en pourcentage contre des
 * objectifs figés dans le code : une enseignante qui avait émargé 149 séances
 * sur 153 et saisi toutes ses notes sortait « Critique, 49 % ». Ici chaque
 * chiffre compare un attendu à un réalisé que l'on peut ouvrir et vérifier :
 * séances tenues sur séances prévues, notes reçues sur notes attendues,
 * paiements saisis, validés, restés en attente.
 *
 * Aucune dimension ne dépend des permissions de la personne : on constate ce
 * qu'elle a fait, pas ce que son rôle l'autoriserait à faire.
 *
 * Les séances se relient aux émargements par la SÉANCE (`course_id`), jamais
 * par un identifiant de personne : `esbtp_seance_cours.teacher_id` désigne la
 * fiche enseignant, `esbtp_teacher_attendances.teacher_id` le compte
 * utilisateur, et les confondre comptait les séances d'un autre.
 */
final class ActiviteDuPersonnel
{
    public const REGLAGE_ATTENTE_JOURS = 'personnel.paiement_attente_jours';

    public const ATTENTE_JOURS_REPLI = 3;

    /** Rôles qui ne relèvent pas du personnel de l'école. */
    private const HORS_PERSONNEL = ['etudiant', 'parent', 'serviceTechnique'];

    public function __construct(private readonly SeuilsDePilotage $seuils) {}

    /** Jours au-delà desquels un paiement en attente de validation est signalé. */
    public function attenteJours(): int
    {
        $brut = SettingsHelper::get(self::REGLAGE_ATTENTE_JOURS, self::ATTENTE_JOURS_REPLI);

        return is_numeric($brut) && (int) $brut >= 0 && (int) $brut <= 90 ? (int) $brut : self::ATTENTE_JOURS_REPLI;
    }

    /**
     * Le résumé de l'année universitaire en cours pour une personne, posé sur
     * sa fiche de profil. Null si la personne n'a rien à constater.
     *
     * @return array<string, mixed>|null
     */
    public function resume(?\App\Models\User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $ligne = $this->lignes(FenetreDActivite::pour('annee'), (int) $user->id)->first();

        return $ligne === null ? null : $ligne + ['periode' => 'annee'];
    }

    /**
     * Une ligne par personne ayant une activité ou une obligation sur la
     * période. Une personne sans rien à constater n'est pas listée : une
     * rangée de tirets ne dit rien.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function lignes(FenetreDActivite $fenetre, ?int $seulement = null): Collection
    {
        $seances = $this->seances($fenetre, $seulement)->keyBy('user_id');
        $evaluations = $this->evaluations($fenetre, $seulement);
        $saisis = $this->compterParAuteur('esbtp_paiements', 'created_by', 'created_at', $fenetre, $seulement, fn (Builder $q) => $q->whereNull('deleted_at'));
        $valides = $this->compterParAuteur('esbtp_paiements', 'validated_by', 'date_validation', $fenetre, $seulement, fn (Builder $q) => $q->whereNull('deleted_at')->where('status', 'validé'));
        $inscriptions = $this->compterParAuteur('esbtp_inscriptions', 'created_by', 'created_at', $fenetre, $seulement, fn (Builder $q) => $q->whereNull('deleted_at'));
        $attente = $this->paiementsEnAttenteParAuteur($seulement);

        $ids = collect([$seances->keys(), $evaluations->keys(), $saisis->keys(), $valides->keys(), $inscriptions->keys(), $attente->keys()])->flatten()->unique()->filter();
        $personnes = $this->personnes($ids);

        return $personnes->map(function ($p) use ($seances, $evaluations, $saisis, $valides, $inscriptions, $attente) {
            $s = $seances->get($p->id);
            $e = $evaluations->get($p->id);

            return [
                'id' => (int) $p->id,
                'nom' => $p->name,
                'role' => $p->role_label,
                'telephone' => $p->phone,
                'seances_prevues' => (int) ($s->prevues ?? 0),
                'seances_tenues' => (int) ($s->tenues ?? 0),
                'seances_non_emargees' => (int) (($s->prevues ?? 0) - ($s->tenues ?? 0)),
                'retards' => (int) ($s->retards ?? 0),
                'evaluations' => (int) ($e['evaluations'] ?? 0),
                'notes_attendues' => (int) ($e['attendues'] ?? 0),
                'notes_recues' => (int) ($e['recues'] ?? 0),
                'evaluations_en_retard' => (int) ($e['en_retard'] ?? 0),
                'paiements_saisis' => (int) ($saisis[$p->id]->nombre ?? 0),
                'montant_saisi' => (float) ($saisis[$p->id]->montant ?? 0),
                'paiements_valides' => (int) ($valides[$p->id]->nombre ?? 0),
                'paiements_en_attente' => (int) ($attente[$p->id] ?? 0),
                'inscriptions' => (int) ($inscriptions[$p->id]->nombre ?? 0),
            ];
        })->sortBy('nom')->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lignes
     * @return array<string, mixed>
     */
    public function synthese(Collection $lignes): array
    {
        return [
            'seances_prevues' => (int) $lignes->sum('seances_prevues'),
            'seances_tenues' => (int) $lignes->sum('seances_tenues'),
            'enseignants_notes_en_retard' => $lignes->where('evaluations_en_retard', '>', 0)->count(),
            'evaluations_en_retard' => (int) $lignes->sum('evaluations_en_retard'),
            'paiements_en_attente' => $this->paiementsEnAttente(),
            'personnes' => $lignes->count(),
        ];
    }

    /** Le taux de séances tenues de la période précédente de même longueur, pour comparer. */
    public function tauxPrecedent(FenetreDActivite $fenetre): ?float
    {
        $jours = $fenetre->debut->diffInDays($fenetre->fin) + 1;
        $precedente = new FenetreDActivite('precedente', $fenetre->debut->copy()->subDays($jours), $fenetre->debut->copy()->subSecond(), '');
        $seances = $this->seances($precedente, null);
        $prevues = (int) $seances->sum('prevues');

        return $prevues > 0 ? round($seances->sum('tenues') / $prevues * 100, 1) : null;
    }

    /** @return array{nombre: int, montant: float} */
    public function paiementsEnAttente(): array
    {
        $ligne = $this->requeteEnAttente()->selectRaw('COUNT(*) as nombre, COALESCE(SUM(montant), 0) as montant')->first();

        return ['nombre' => (int) ($ligne->nombre ?? 0), 'montant' => (float) ($ligne->montant ?? 0), 'jours' => $this->attenteJours()];
    }

    public function requeteEnAttente(): Builder
    {
        return DB::table('esbtp_paiements')
            ->whereNull('deleted_at')
            ->where('status', 'en_attente')
            ->where('created_at', '<', now()->subDays($this->attenteJours()));
    }

    /**
     * Séances prévues et tenues par enseignant (compte utilisateur).
     */
    public function seances(FenetreDActivite $fenetre, ?int $seulement): Collection
    {
        $realises = $this->liste(TeacherHoursService::STATUTS_REALISES);
        $retards = $this->liste(TeacherHoursService::STATUTS_RETARD);

        $emargements = DB::table('esbtp_teacher_attendances')
            ->where('type', 'start')
            ->groupBy('course_id')
            ->selectRaw("course_id, MAX(CASE WHEN LOWER(status) IN ($realises) THEN 1 ELSE 0 END) as tenue, MAX(CASE WHEN LOWER(status) IN ($retards) THEN 1 ELSE 0 END) as retard");

        return DB::table('esbtp_seance_cours as s')
            ->join('esbtp_teachers as t', 't.id', '=', 's.teacher_id')
            ->leftJoinSub($emargements, 'em', 'em.course_id', '=', 's.id')
            ->whereNull('s.deleted_at')
            ->whereNotIn('s.type', [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH])
            ->whereNotNull('s.date_seance')
            ->whereBetween('s.date_seance', [$fenetre->du(), $fenetre->au()])
            ->when($seulement, fn ($q) => $q->where('t.user_id', $seulement))
            ->groupBy('t.user_id')
            ->selectRaw('t.user_id, COUNT(*) as prevues, COALESCE(SUM(em.tenue), 0) as tenues, COALESCE(SUM(em.retard), 0) as retards')
            ->get();
    }

    /**
     * Évaluations passées par enseignant désigné, avec les notes reçues.
     *
     * @return Collection<int, array{evaluations:int, attendues:int, recues:int, en_retard:int}>
     */
    private function evaluations(FenetreDActivite $fenetre, ?int $seulement): Collection
    {
        $limite = now()->startOfDay()->subDays($this->seuils->relanceApresJours());

        return $this->evaluationsPassees($fenetre, $seulement)->get()
            ->groupBy('enseignant_id')
            ->map(fn (Collection $evals) => [
                'evaluations' => $evals->count(),
                'attendues' => (int) $evals->sum('attendues'),
                'recues' => (int) $evals->sum(fn ($e) => min($e->recues, $e->attendues)),
                'en_retard' => $evals->filter(fn ($e) => $e->recues < $e->attendues && $e->date_evaluation <= $limite)->count(),
            ]);
    }

    /**
     * La requête commune à la synthèse et au détail d'une personne.
     * « Attendues » = inscrits actifs de la classe cette année-là.
     */
    public function evaluationsPassees(FenetreDActivite $fenetre, ?int $seulement): Builder
    {
        return DB::table('esbtp_evaluations as e')
            ->whereNull('e.deleted_at')
            ->whereNotNull('e.enseignant_id')
            ->where('e.status', '!=', 'cancelled')
            ->whereBetween('e.date_evaluation', [$fenetre->debut, $fenetre->fin])
            ->when($seulement, fn ($q) => $q->where('e.enseignant_id', $seulement))
            ->select(['e.id', 'e.enseignant_id', 'e.classe_id', 'e.matiere_id', 'e.titre', 'e.date_evaluation'])
            ->selectSub(fn ($q) => $q->from('esbtp_inscriptions as i')->selectRaw('COUNT(*)')
                ->whereColumn('i.classe_id', 'e.classe_id')->whereColumn('i.annee_universitaire_id', 'e.annee_universitaire_id')
                ->where('i.status', 'active')->whereNull('i.deleted_at'), 'attendues')
            ->selectSub(fn ($q) => $q->from('esbtp_notes as n')->selectRaw('COUNT(DISTINCT n.etudiant_id)')
                ->whereColumn('n.evaluation_id', 'e.id')->whereNull('n.deleted_at'), 'recues');
    }

    private function compterParAuteur(string $table, string $colonne, string $date, FenetreDActivite $fenetre, ?int $seulement, callable $portee): Collection
    {
        $requete = DB::table($table)->whereNotNull($colonne)->whereBetween($date, [$fenetre->debut, $fenetre->fin]);
        $portee($requete);

        return $requete
            ->when($seulement, fn ($q) => $q->where($colonne, $seulement))
            ->groupBy($colonne)
            ->selectRaw($colonne.' as auteur, COUNT(*) as nombre'.($table === 'esbtp_paiements' ? ', COALESCE(SUM(montant), 0) as montant' : ''))
            ->get()
            ->keyBy('auteur');
    }

    private function paiementsEnAttenteParAuteur(?int $seulement): Collection
    {
        return $this->requeteEnAttente()
            ->whereNotNull('created_by')
            ->when($seulement, fn ($q) => $q->where('created_by', $seulement))
            ->groupBy('created_by')
            ->selectRaw('created_by, COUNT(*) as nombre')
            ->pluck('nombre', 'created_by');
    }

    private function personnes(Collection $ids): Collection
    {
        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('users as u')
            ->whereIn('u.id', $ids)
            ->whereNull('u.deleted_at')
            ->whereNotExists(fn ($q) => $q->from('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->whereColumn('mr.model_id', 'u.id')->where('mr.model_type', \App\Models\User::class)
                ->whereIn('r.name', self::HORS_PERSONNEL))
            ->select(['u.id', 'u.name', 'u.phone'])
            ->selectSub(fn ($q) => $q->from('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
                ->whereColumn('mr.model_id', 'u.id')->where('mr.model_type', \App\Models\User::class)
                ->selectRaw('COALESCE(r.label_fr, r.name)')->limit(1), 'role_label')
            ->get();
    }

    /** Les statuts viennent des constantes du service des heures, jamais d'une saisie. */
    private function liste(array $valeurs): string
    {
        return implode(',', array_map(fn (string $v) => DB::getPdo()->quote(mb_strtolower($v, 'UTF-8')), $valeurs));
    }
}
