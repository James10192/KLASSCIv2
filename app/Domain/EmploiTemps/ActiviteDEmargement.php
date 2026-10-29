<?php

namespace App\Domain\EmploiTemps;

use App\Models\ESBTPTeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Ce que les émargements d'un enseignant disent de son activité dans le temps.
 *
 * Tout part des émargements de DÉBUT, pas des séances planifiées : la trame
 * hebdomadaire ne porte pas de date, donc « séances prévues ce mois » serait
 * une estimation. Un émargement, lui, est un fait daté. Le tableau de bord
 * affichait un taux de présence de 0 % calculé sur des `date_seance` que la
 * trame n'a pas : un chiffre faux, présenté comme une mesure.
 */
final class ActiviteDEmargement
{
    /**
     * Le mois de `$mois` comparé au mois précédent.
     *
     * @return array{present:int, retard:int, absent:int, total:int, ponctualite:?int, heures:float,
     *               precedent: array{ponctualite:?int, heures:float, total:int}, libelle_precedent:string}
     */
    public function bilanDuMois(User $user, Carbon $mois): array
    {
        $debut = $mois->copy()->startOfMonth();
        $courant = $this->resumer($this->emargements($user, $debut, $mois->copy()->endOfMonth()));
        $precedentDebut = $debut->copy()->subMonthNoOverflow();
        $precedent = $this->resumer($this->emargements($user, $precedentDebut, $precedentDebut->copy()->endOfMonth()));

        return $courant + [
            'precedent' => ['ponctualite' => $precedent['ponctualite'], 'heures' => $precedent['heures'], 'total' => $precedent['total']],
            'libelle_precedent' => $precedentDebut->locale('fr')->isoFormat('MMMM'),
        ];
    }

    /**
     * Heures émargées par semaine, de la plus ancienne à la courante.
     *
     * @return array<int, array{libelle:string, du:string, heures:float}>
     */
    public function tendance(User $user, Carbon $maintenant, int $semaines = 8): array
    {
        $premiere = $maintenant->copy()->startOfWeek()->subWeeks($semaines - 1);
        $parSemaine = $this->emargements($user, $premiere, $maintenant->copy()->endOfWeek())
            ->reject(fn ($e) => $e->status === 'absent')
            ->groupBy(fn ($e) => $e->date->copy()->startOfWeek()->toDateString());

        $serie = [];
        for ($i = 0; $i < $semaines; $i++) {
            $lundi = $premiere->copy()->addWeeks($i);
            $serie[] = [
                'libelle' => $lundi->format('d/m'),
                'du' => $lundi->locale('fr')->isoFormat('D MMM'),
                'heures' => round($parSemaine->get($lundi->toDateString(), collect())->sum(fn ($e) => $this->duree($e)), 1),
            ];
        }

        return $serie;
    }

    /** Les derniers émargements, pour la colonne « récemment ». */
    public function derniers(User $user, int $limite = 6): Collection
    {
        return ESBTPTeacherAttendance::where('teacher_id', $user->id)
            ->where('type', 'start')
            ->with(['course:id,matiere_id,classe_id,heure_debut,heure_fin', 'course.matiere:id,name', 'course.classe:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('validated_at')
            ->limit($limite)
            ->get();
    }

    private function emargements(User $user, Carbon $du, Carbon $au): Collection
    {
        return ESBTPTeacherAttendance::where('teacher_id', $user->id)
            ->where('type', 'start')
            ->whereBetween('date', [$du->toDateString(), $au->toDateString()])
            ->with('course:id,heure_debut,heure_fin')
            ->get();
    }

    /** @return array{present:int, retard:int, absent:int, total:int, ponctualite:?int, heures:float} */
    private function resumer(Collection $emargements): array
    {
        $present = $emargements->where('status', 'present')->count();
        $retard = $emargements->where('status', 'late')->count();
        $absent = $emargements->where('status', 'absent')->count();
        $total = $present + $retard + $absent;

        return [
            'present' => $present,
            'retard' => $retard,
            'absent' => $absent,
            'total' => $total,
            // Sans émargement, il n'y a pas de taux : null, et l'écran dit « — », pas « 0 % ».
            'ponctualite' => $total > 0 ? (int) round($present * 100 / $total) : null,
            'heures' => round($emargements->reject(fn ($e) => $e->status === 'absent')->sum(fn ($e) => $this->duree($e)), 1),
        ];
    }

    private function duree(ESBTPTeacherAttendance $e): float
    {
        $attributs = $e->course?->getAttributes() ?? [];
        $debut = HeureDeSeance::hi($attributs['heure_debut'] ?? null);
        $fin = HeureDeSeance::hi($attributs['heure_fin'] ?? null);
        if (! $debut || ! $fin) {
            return 0.0;
        }
        [$hd, $md] = array_map('intval', explode(':', $debut));
        [$hf, $mf] = array_map('intval', explode(':', $fin));

        return max(0, ($hf * 60 + $mf) - ($hd * 60 + $md)) / 60;
    }
}
