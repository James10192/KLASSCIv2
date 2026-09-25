<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * La présence aux cours, agrégée en SQL pour le tableau de bord pédagogique.
 *
 * On compte les appels RÉELLEMENT FAITS : une séance sans appel n'est ni une
 * présence ni une absence, c'est une mesure manquante. Un retard compte comme
 * présent, une absence justifiée reste une absence — l'étudiant n'était pas là.
 *
 * Un appel de début et un appel de fin portent deux lignes pour le même
 * étudiant et la même séance. Seul l'appel de début (ou l'appel fusionné)
 * compte, sinon chaque séance doublerait le dénominateur.
 */
final class PresenceDuPerimetre
{
    private const PRESENTS = ['present', 'présent', 'retard', 'late', 'delayed'];

    private const APPELS_COMPTES = ['start', 'merged'];

    /** Au-dessous de ce nombre d'appels, un taux individuel ne veut rien dire. */
    private const APPELS_MINIMUM_ETUDIANT = 5;

    /**
     * La fenêtre de dates de la période : celle des emplois du temps du
     * semestre quand il y en a, l'année entière sinon.
     *
     * @return array{debut: string, fin: string, source: string}
     */
    public function fenetre(int $anneeId, string $periode): array
    {
        $annee = DB::table('esbtp_annee_universitaires')->where('id', $anneeId)->first(['start_date', 'end_date']);
        $anneeFenetre = [
            'debut' => (string) ($annee->start_date ?? now()->startOfYear()->toDateString()),
            'fin' => (string) ($annee->end_date ?? now()->endOfYear()->toDateString()),
            'source' => 'annee',
        ];

        if ($periode === 'annuel') {
            return $anneeFenetre;
        }

        $edt = DB::table('esbtp_emploi_temps')
            ->where('annee_universitaire_id', $anneeId)
            ->where('semestre', $periode)
            ->whereNull('deleted_at')
            ->selectRaw('MIN(date_debut) as debut, MAX(date_fin) as fin')
            ->first();

        return $edt && $edt->debut && $edt->fin
            ? ['debut' => (string) $edt->debut, 'fin' => (string) $edt->fin, 'source' => 'emploi_du_temps']
            : $anneeFenetre;
    }

    /**
     * @return array<int, array{taux: float|null, appels: int, presents: int}>
     */
    public function parClasse(int $anneeId, string $periode, Collection $classeIds): array
    {
        if ($classeIds->isEmpty()) {
            return [];
        }

        return $this->appels($anneeId, $periode, $classeIds)
            ->groupBy('classe_id')
            ->selectRaw('classe_id, COUNT(*) as appels, '.$this->sommeDesPresents().' as presents')
            ->get()
            ->mapWithKeys(fn ($ligne) => [(int) $ligne->classe_id => [
                'appels' => (int) $ligne->appels,
                'presents' => (int) $ligne->presents,
                'taux' => $ligne->appels > 0 ? round($ligne->presents / $ligne->appels * 100, 1) : null,
            ]])
            ->all();
    }

    /**
     * Les étudiants sous le seuil de l'école, le plus bas d'abord.
     *
     * @return list<array<string, mixed>>
     */
    public function etudiantsSousLeSeuil(int $anneeId, string $periode, Collection $classeIds, int $seuil, int $limite = 12): array
    {
        if ($classeIds->isEmpty()) {
            return [];
        }

        $lignes = $this->appels($anneeId, $periode, $classeIds)
            ->groupBy('a.etudiant_id', 'a.classe_id')
            ->selectRaw('a.etudiant_id, a.classe_id, COUNT(*) as appels, '.$this->sommeDesPresents().' as presents,
                SUM(CASE WHEN a.statut IN ('.$this->marqueurs(self::PRESENTS).') OR a.is_justified = 1 THEN 0 ELSE 1 END) as non_justifiees')
            ->havingRaw('COUNT(*) >= ?', [self::APPELS_MINIMUM_ETUDIANT])
            ->havingRaw($this->sommeDesPresents().' * 100 < COUNT(*) * ?', [$seuil])
            ->orderByRaw($this->sommeDesPresents().' / COUNT(*)')
            ->limit($limite)
            ->get();

        $etudiants = DB::table('esbtp_etudiants')->whereIn('id', $lignes->pluck('etudiant_id'))->get(['id', 'nom', 'prenoms', 'matricule'])->keyBy('id');
        $classes = DB::table('esbtp_classes')->whereIn('id', $lignes->pluck('classe_id'))->pluck('name', 'id');

        return $lignes->map(fn ($l) => [
            'id' => (int) $l->etudiant_id,
            'nom' => trim(($etudiants[$l->etudiant_id]->nom ?? '').' '.($etudiants[$l->etudiant_id]->prenoms ?? '')),
            'matricule' => $etudiants[$l->etudiant_id]->matricule ?? null,
            'classe' => $classes[$l->classe_id] ?? null,
            'taux' => round($l->presents / $l->appels * 100, 1),
            'absences' => (int) $l->appels - (int) $l->presents,
            'non_justifiees' => (int) $l->non_justifiees,
            'appels' => (int) $l->appels,
        ])->values()->all();
    }

    /**
     * Présence et notes saisies, mois par mois, sur le périmètre.
     * Un mois sans appel reste vide : zéro dirait que personne n'est venu.
     *
     * @return array{labels: list<string>, presence: list<float|null>, notes: list<int>}
     */
    public function tendanceMensuelle(int $anneeId, Collection $classeIds): array
    {
        $fenetre = $this->fenetre($anneeId, 'annuel');
        $debut = Carbon::parse($fenetre['debut'])->startOfMonth();
        $fin = Carbon::parse($fenetre['fin'])->min(now())->startOfMonth();

        $presence = $classeIds->isEmpty() ? collect() : DB::table('esbtp_attendances as a')
            ->where('a.annee_universitaire_id', $anneeId)
            ->whereIn('a.classe_id', $classeIds)
            ->whereIn('a.call_type', self::APPELS_COMPTES)
            ->whereNull('a.deleted_at')
            ->groupByRaw("DATE_FORMAT(a.date, '%Y-%m')")
            ->selectRaw("DATE_FORMAT(a.date, '%Y-%m') as mois, COUNT(*) as appels, ".$this->sommeDesPresents().' as presents')
            ->get()->keyBy('mois');

        $notes = $classeIds->isEmpty() ? collect() : DB::table('esbtp_notes as n')
            ->join('esbtp_evaluations as e', 'e.id', '=', 'n.evaluation_id')
            ->where('e.annee_universitaire_id', $anneeId)
            ->whereIn('e.classe_id', $classeIds)
            ->whereNull('n.deleted_at')
            ->whereNull('e.deleted_at')
            ->groupByRaw("DATE_FORMAT(n.created_at, '%Y-%m')")
            ->selectRaw("DATE_FORMAT(n.created_at, '%Y-%m') as mois, COUNT(*) as total")
            ->pluck('total', 'mois');

        $sortie = ['labels' => [], 'presence' => [], 'notes' => []];
        for ($mois = $debut->copy(); $mois->lte($fin); $mois->addMonth()) {
            $cle = $mois->format('Y-m');
            $ligne = $presence->get($cle);
            $sortie['labels'][] = ucfirst($mois->locale('fr')->translatedFormat('M y'));
            $sortie['presence'][] = $ligne && $ligne->appels > 0 ? round($ligne->presents / $ligne->appels * 100, 1) : null;
            $sortie['notes'][] = (int) ($notes[$cle] ?? 0);
        }

        return $sortie;
    }

    private function appels(int $anneeId, string $periode, Collection $classeIds): Builder
    {
        $fenetre = $this->fenetre($anneeId, $periode);

        return DB::table('esbtp_attendances as a')
            ->where('a.annee_universitaire_id', $anneeId)
            ->whereIn('a.classe_id', $classeIds)
            ->whereIn('a.call_type', self::APPELS_COMPTES)
            ->whereNull('a.deleted_at')
            ->whereBetween('a.date', [$fenetre['debut'], $fenetre['fin']]);
    }

    private function sommeDesPresents(): string
    {
        return 'SUM(CASE WHEN a.statut IN ('.$this->marqueurs(self::PRESENTS).') THEN 1 ELSE 0 END)';
    }

    /** Les statuts sont des constantes de cette classe, jamais une saisie : on peut les citer. */
    private function marqueurs(array $valeurs): string
    {
        return implode(',', array_map(fn (string $v) => DB::getPdo()->quote($v), $valeurs));
    }
}
