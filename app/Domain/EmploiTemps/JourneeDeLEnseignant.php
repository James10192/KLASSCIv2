<?php

namespace App\Domain\EmploiTemps;

use App\Models\ESBTPAttendance;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacherAttendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Ce que l'enseignant a devant lui aujourd'hui : ses cours, leur état, et la
 * liste de ce qui attend un geste de sa part.
 *
 * Les cours du jour se lisent par le JOUR de la semaine, comme l'écran
 * d'émargement et la validation du code : un emploi du temps est une trame
 * hebdomadaire. Le tableau de bord les lisait par `date_seance`, que la trame
 * ne porte pas — il annonçait « Aucun cours aujourd'hui » à un enseignant dont
 * l'écran d'émargement listait trois cours.
 */
final class JourneeDeLEnseignant
{
    public function __construct(private FenetresDEmargement $fenetres)
    {
    }

    /** @return Collection<int, CoursDuJour> triés par heure de début */
    public function coursDuJour(User $user, ?Carbon $maintenant = null): Collection
    {
        $maintenant ??= Carbon::now();
        $profilId = $user->teacherProfile?->id;
        if (! $profilId) {
            return collect();
        }

        $jour = $maintenant->dayOfWeekIso;
        $seances = ESBTPSeanceCours::with(['matiere:id,name', 'classe:id,name', 'emploiTemps.classe:id,name'])
            ->where('teacher_id', $profilId)
            ->where('is_active', true)
            ->whereNotIn('type', [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH])
            ->whereIn('jour', JourDeLaSemaine::ecrituresDe($jour))
            ->get();

        if ($seances->isEmpty()) {
            return collect();
        }

        $ids = $seances->pluck('id')->all();
        $emargements = ESBTPTeacherAttendance::where('teacher_id', $user->id)
            ->whereIn('course_id', $ids)
            ->whereDate('date', $maintenant->toDateString())
            ->get()
            ->groupBy('course_id');
        $appels = ESBTPAttendance::whereIn('seance_cours_id', $ids)
            ->whereDate('date', $maintenant->toDateString())
            ->distinct()
            ->pluck('seance_cours_id')
            ->flip();

        return $seances
            ->map(fn (ESBTPSeanceCours $s) => $this->decrire($s, $emargements->get($s->id, collect()), isset($appels[$s->id]), $maintenant))
            ->filter()
            ->sortBy(fn (CoursDuJour $c) => $c->debut->timestamp)
            ->values();
    }

    /**
     * Ce qui attend l'enseignant, du plus pressé au moins pressé.
     *
     * @param  Collection<int, CoursDuJour>  $cours
     * @return array<int, array{ton:string, icone:string, titre:string, detail:string, action:string, url:string}>
     */
    public function fileDeTravail(Collection $cours, Collection $evaluationsANoter): array
    {
        $urgence = [
            CoursDuJour::MOTIF_REQUIS => 0, CoursDuJour::RETARD => 1, CoursDuJour::OUVERT => 2,
            CoursDuJour::FIN_OUVERTE => 3,
        ];
        $emargement = route('esbtp.teacher-attendance.index');
        $file = [];

        foreach ($cours as $c) {
            $lieu = collect([$c->classe(), $c->salle()])->filter()->implode(' · ');
            if ($c->demandeUnEmargement()) {
                $file[] = [
                    'rang' => $urgence[$c->etat],
                    'ton' => $c->ton(),
                    'icone' => 'fa-signature',
                    'titre' => ($c->etat === CoursDuJour::FIN_OUVERTE ? 'Émarger la fin — ' : 'Émarger — ').$c->matiere(),
                    'detail' => $c->libelle().($lieu ? ' · '.$lieu : ''),
                    'action' => $c->etat === CoursDuJour::FIN_OUVERTE ? 'Émarger la fin' : 'Émarger',
                    'url' => $emargement.'#cours-'.$c->seance->id,
                ];
            }
            if ($c->appelAFaire()) {
                $file[] = [
                    'rang' => 4,
                    'ton' => 'primary',
                    'icone' => 'fa-list-check',
                    'titre' => 'Faire l’appel — '.$c->matiere(),
                    'detail' => 'Cours de '.$c->debut->format('H:i').($lieu ? ' · '.$lieu : ''),
                    'action' => 'Faire l’appel',
                    'url' => route('teacher.select-call-type', $c->seance->id),
                ];
            }
        }

        foreach ($evaluationsANoter as $evaluation) {
            $file[] = [
                'rang' => 5,
                'ton' => 'primary',
                'icone' => 'fa-pen-to-square',
                'titre' => 'Saisir les notes — '.($evaluation->titre ?: ($evaluation->matiere->name ?? 'Évaluation')),
                'detail' => trim(($evaluation->classe->name ?? '').' · passée le '.optional($evaluation->date_evaluation)->format('d/m'), ' ·'),
                'action' => 'Saisir',
                'url' => route('teacher.grades'),
            ];
        }

        usort($file, fn ($a, $b) => $a['rang'] <=> $b['rang']);

        return $file;
    }

    /**
     * Évaluations passées de l'année en cours, sans aucune note saisie.
     *
     * @return Collection<int, ESBTPEvaluation>
     */
    public function evaluationsANoter(User $user, ?int $anneeId, int $limite = 5): Collection
    {
        if (! $anneeId) {
            return collect();
        }

        return ESBTPEvaluation::query()
            ->where(fn ($q) => $q->where('enseignant_id', $user->id)->orWhere('created_by', $user->id))
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->whereDate('date_evaluation', '<=', Carbon::today())
            ->whereDoesntHave('notes')
            ->with(['matiere:id,name', 'classe:id,name'])
            ->orderBy('date_evaluation')
            ->limit($limite)
            ->get();
    }

    /**
     * Les prochains cours de la semaine, après aujourd'hui.
     *
     * @return Collection<int, array{jour:string, debut:string, fin:string, matiere:string, classe:?string, salle:?string}>
     */
    public function prochainsCours(User $user, ?Carbon $maintenant = null, int $limite = 5): Collection
    {
        $maintenant ??= Carbon::now();
        $profilId = $user->teacherProfile?->id;
        if (! $profilId) {
            return collect();
        }

        $seances = ESBTPSeanceCours::with(['matiere:id,name', 'classe:id,name', 'emploiTemps.classe:id,name'])
            ->where('teacher_id', $profilId)
            ->where('is_active', true)
            ->whereNotIn('type', [ESBTPSeanceCours::TYPE_BREAK, ESBTPSeanceCours::TYPE_LUNCH])
            ->get();

        $aujourdhui = $maintenant->dayOfWeekIso;

        return $seances
            ->map(function (ESBTPSeanceCours $s) use ($aujourdhui) {
                $rang = JourDeLaSemaine::rang($s->jour);
                $debut = HeureDeSeance::hi($s->getAttributes()['heure_debut'] ?? null);
                if ($rang === null || ! $debut) {
                    return null;
                }
                // Jours restants avant ce cours : demain vaut 1, la semaine suivante boucle.
                $ecart = ($rang + 1 - $aujourdhui + 7) % 7;

                return [
                    'ecart' => $ecart === 0 ? 7 : $ecart,
                    'jour' => JourDeLaSemaine::libelle($s->jour) ?? '',
                    'debut' => $debut,
                    'fin' => HeureDeSeance::hi($s->getAttributes()['heure_fin'] ?? null) ?? '--:--',
                    'matiere' => $s->matiere->name ?? 'Matière non définie',
                    'classe' => $s->emploiTemps->classe->name ?? $s->classe->name ?? null,
                    'salle' => CoursDuJour::libelleSalle($s->salle),
                ];
            })
            ->filter()
            ->sortBy(fn ($c) => sprintf('%02d %s', $c['ecart'], $c['debut']))
            ->take($limite)
            ->values();
    }

    private function decrire(ESBTPSeanceCours $s, Collection $emargements, bool $appelFait, Carbon $maintenant): ?CoursDuJour
    {
        $hDebut = HeureDeSeance::hi($s->getAttributes()['heure_debut'] ?? null);
        $hFin = HeureDeSeance::hi($s->getAttributes()['heure_fin'] ?? null);
        if (! $hDebut || ! $hFin) {
            return null;
        }

        $debut = $maintenant->copy()->setTimeFromTimeString($hDebut);
        [$finOuverture, $finFermeture] = $this->fenetres->fenetreDeFin($s, $maintenant->copy()->startOfDay());
        $emDebut = $emargements->firstWhere('type', 'start');
        $emFin = $emargements->firstWhere('type', 'end');

        return new CoursDuJour(
            seance: $s,
            debut: $debut,
            fin: $maintenant->copy()->setTimeFromTimeString($hFin),
            ouverture: $this->fenetres->ouvertureDebut($debut),
            limitePresent: $this->fenetres->limitePresent($debut),
            limiteRetard: $this->fenetres->limiteRetard($debut),
            finOuverture: $finOuverture,
            finFermeture: $finFermeture,
            emargementDebut: $emDebut,
            emargementFin: $emFin,
            appelFait: $appelFait,
            etat: $this->etat($debut, $finOuverture, $finFermeture, $emDebut, $emFin, $maintenant),
        );
    }

    private function etat(Carbon $debut, Carbon $finOuverture, Carbon $finFermeture, ?ESBTPTeacherAttendance $emDebut, ?ESBTPTeacherAttendance $emFin, Carbon $maintenant): string
    {
        if ($emDebut?->status === 'absent') {
            return CoursDuJour::ABSENT;
        }
        if ($emDebut && $emFin) {
            return CoursDuJour::TERMINE;
        }
        if ($emDebut) {
            return match (true) {
                $maintenant->lt($finOuverture) => CoursDuJour::EN_COURS,
                $maintenant->lte($finFermeture) => CoursDuJour::FIN_OUVERTE,
                default => CoursDuJour::FIN_MANQUEE,
            };
        }

        return match ($this->fenetres->classerDebut($maintenant, $debut)) {
            MomentDEmargement::TropTot => CoursDuJour::A_VENIR,
            MomentDEmargement::Present => CoursDuJour::OUVERT,
            MomentDEmargement::Retard => CoursDuJour::RETARD,
            MomentDEmargement::Depasse => $this->fenetres->marqueAbsentDOffice() ? CoursDuJour::DEPASSE : CoursDuJour::MOTIF_REQUIS,
        };
    }
}
