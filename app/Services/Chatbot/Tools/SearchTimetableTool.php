<?php

namespace App\Services\Chatbot\Tools;

use App\Domain\EmploiTemps\JourDeLaSemaine;
use App\Models\ESBTPEmploiTemps;
use Illuminate\Support\Facades\Route;

class SearchTimetableTool extends ChatbotTool
{
    public function name(): string
    {
        return 'search_timetable';
    }

    public function description(): string
    {
        return 'Rechercher l\'emploi du temps d\'une classe. Retourne les séances de cours organisées par jour (lundi à samedi) avec matière, enseignant, horaire et salle.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => [
                    'type' => 'string',
                    'description' => 'Nom ou code de la classe (ex: "B3 COM", "L1 GC")',
                ],
                'jour' => [
                    'type' => 'string',
                    'description' => 'Jour spécifique: "lundi", "mardi", "mercredi", "jeudi", "vendredi", "samedi". Si non précisé, retourne toute la semaine.',
                ],
            ],
            'required' => ['classe'],
        ];
    }

    public function execute(array $args, $user): array
    {
        if (! $this->isAvailableFor($user)) {
            return $this->unavailableResponse();
        }

        $search = $args['classe'];

        $baseQuery = ESBTPEmploiTemps::query()
            ->with(['classe.filiere', 'seances.matiere', 'seances.teacher.user', 'annee']);
        $this->applyClasseSearch($baseQuery, $search);

        $emploiTemps = (clone $baseQuery)->where('is_current', true)->first();

        if (!$emploiTemps) {
            $emploiTemps = (clone $baseQuery)->orderByDesc('created_at')->first();
        }

        if (!$emploiTemps) {
            return [
                'results' => [],
                'count' => 0,
                'display_type' => 'text',
                'message' => "Aucun emploi du temps trouvé pour la classe \"{$search}\".",
            ];
        }

        $seances = $emploiTemps->seances->filter(fn ($s) => $s->is_active && $s->type === 'course');

        // Filtrer par jour si spécifié.
        //
        // Ce filtre ne rendait JAMAIS rien : sa table était à base zéro
        // (`lundi => 0`) et la comparaison stricte, alors que la colonne `jour`
        // porte soit l'entier à base un, soit le libellé « Lundi ». Ni `1 === 0`
        // ni `'Lundi' === 0` ne tiennent. Demander l'emploi du temps « du
        // mercredi » au chatbot rendait donc une journée vide.
        if (! empty($args['jour'])) {
            $rangDemande = JourDeLaSemaine::rang($args['jour']);
            if ($rangDemande !== null) {
                $seances = $seances->filter(
                    fn ($s) => JourDeLaSemaine::rang($s->jour) === $rangDemande
                );
            }
        }

        $days = $this->journeesDeLaSemaine($seances);

        $classe = $emploiTemps->classe;

        return [
            'results' => $days,
            'count' => $seances->count(),
            'classe' => $classe?->name ?? 'N/A',
            'filiere' => $classe?->filiere?->name ?? 'N/A',
            'semestre' => $emploiTemps->semestre ?? 'N/A',
            'annee' => $emploiTemps->annee?->name ?? 'N/A',
            'periode' => $emploiTemps->date_debut?->format('d/m/Y') . ' — ' . $emploiTemps->date_fin?->format('d/m/Y'),
            'display_type' => 'timetable',
            'deep_link' => Route::has('esbtp.emploi-temps.show')
                ? route('esbtp.emploi-temps.show', $emploiTemps->id) : null,
        ];
    }

    /**
     * Les séances rangées par jour, chaque jour trié par heure.
     *
     * Le groupement se fait par RANG et non par l'écriture brute de `jour` :
     * sans quoi le même mercredi forme deux groupes selon l'écran qui a saisi
     * la séance, l'un écrivant l'entier et l'autre le libellé.
     *
     * Un jour illisible part au rang 99, donc en fin de semaine sous « Jour
     * inconnu », plutôt que de disparaître de la réponse sans que personne
     * s'en aperçoive.
     *
     * `rang()` est à base zéro et `libelle()` attend une base un : d'où le
     * `+ 1`, qui n'est pas un décalage mais la conversion entre les deux.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\ESBTPSeanceCours>  $seances
     * @return list<array{jour: string, slots: list<array<string, string>>}>
     */
    private function journeesDeLaSemaine($seances): array
    {
        $journees = [];

        foreach ($seances->groupBy(fn ($s) => JourDeLaSemaine::rang($s->jour) ?? 99)->sortKeys() as $rang => $duJour) {
            $journees[] = [
                'jour' => JourDeLaSemaine::libelle($rang + 1) ?? 'Jour inconnu',
                'slots' => $duJour
                    ->sortBy(fn ($s) => $s->heure_debut?->format('H:i') ?? '00:00')
                    ->map(fn ($s) => $this->creneau($s))
                    ->values()->toArray(),
            ];
        }

        return $journees;
    }

    /**
     * Un créneau, tel que le chatbot le lit à voix haute.
     *
     * `format('H:i')` et non l'attribut brut : le modèle déclare un accesseur
     * `getHeureDebutAttribute()` qui fait `Carbon::parse()`, donc le lire en
     * contexte chaîne rendrait « 2026-09-15 08:00:00 » au lieu de « 08:00 ».
     * (C'est l'accesseur et non le cast homonyme — piège #14 de
     * `klassci-debugging-discipline.md`.)
     *
     * @return array<string, string>
     */
    private function creneau($seance): array
    {
        $enseignant = $seance->teacher?->user;

        return [
            'horaire' => ($seance->heure_debut?->format('H:i') ?? '?')
                . ' - ' . ($seance->heure_fin?->format('H:i') ?? '?'),
            'matiere' => $seance->matiere?->name ?? $seance->matiere?->nom ?? 'N/A',
            'enseignant' => $enseignant
                ? trim($enseignant->name ?? '')
                : ($seance->teacher?->specialization ?? 'N/A'),
            'salle' => $seance->salle ?? 'N/A',
        ];
    }
}
