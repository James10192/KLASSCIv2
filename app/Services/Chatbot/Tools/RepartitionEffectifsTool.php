<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPAnneeUniversitaire;
use App\Domain\Students\StudentCountService;

/**
 * Inscrits de l'année répartis par filière, niveau ou classe. La règle d'un
 * inscrit vient de StudentCountService::requeteInscrits() et le total de
 * inscritsDe() : c'est le chiffre du tableau de bord. Les groupes se font par
 * identifiant (deux filières peuvent porter le même nom, seul le code est unique).
 *
 * Sans cet outil, l'agent reconstituait la répartition à partir de listes
 * paginées et chaque modèle donnait un chiffre différent.
 */
class RepartitionEffectifsTool extends ChatbotTool
{
    private const AXES = ['filiere', 'niveau', 'classe'];

    public function name(): string
    {
        return 'repartition_effectifs';
    }

    public function description(): string
    {
        return "Nombre d'inscrits de l'année universitaire en cours (ou d'une année donnée), répartis par filière, niveau ou classe, "
            . "total identique au tableau de bord. Affiche un graphique. À utiliser pour « combien d'étudiants par filière », "
            . "« quelle classe est la plus chargée », « compare les effectifs ». Ne pas reconstituer ces chiffres avec search_inscriptions.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'par' => ['type' => 'string', 'enum' => self::AXES, 'description' => 'Axe de répartition. Par défaut : filiere.'],
                'annee' => ['type' => 'string', 'description' => 'Année universitaire, ex. « 2024-2025 ». Par défaut : l\'année en cours.'],
            ],
        ];
    }

    public function execute(array $args, $user): array
    {
        $par = in_array($args['par'] ?? null, self::AXES, true) ? $args['par'] : 'filiere';
        $annee = !empty($args['annee'])
            ? ESBTPAnneeUniversitaire::where('name', trim((string) $args['annee']))->first()
            : ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (!$annee) {
            return ['results' => [], 'count' => 0, 'message' => "Année universitaire introuvable : précise-la au format 2024-2025."];
        }

        $lignes = $this->groupes($par, $annee->id);

        // Le total est celui du tableau de bord, pas la somme des groupes : un
        // étudiant inscrit dans deux classes la même année compte une fois.
        $total = app(StudentCountService::class)->inscritsDe($annee->id);
        $somme = (int) $lignes->sum('inscrits');

        $nomsEnDouble = $lignes->countBy('nom')->filter(fn ($n) => $n > 1)->keys()->all();
        $resultats = $lignes->map(fn ($l) => [
            $par => in_array($l->nom, $nomsEnDouble, true) && $l->code ? "{$l->nom} ({$l->code})" : $l->nom,
            'inscrits' => (int) $l->inscrits,
            'part' => $somme > 0 ? round($l->inscrits / $somme * 100, 1) . ' %' : '0 %',
        ])->values()->all();

        $noms = ['filiere' => 'filière', 'niveau' => 'niveau', 'classe' => 'classe'];

        return [
            'results' => $resultats,
            'count' => count($resultats),
            'annee' => $annee->name,
            'totaux' => ['inscrits' => $total],
            'remarque' => $somme > $total
                ? "La somme des groupes ({$somme}) dépasse le total ({$total}) : des étudiants sont inscrits dans plusieurs groupes."
                : null,
            'widget' => $resultats === [] ? null : [
                'kind' => 'graphique',
                'type' => 'barres',
                'titre' => "Inscrits {$annee->name} par {$noms[$par]}",
                'libelles' => array_column($resultats, $par),
                'series' => [['nom' => 'Inscrits', 'valeurs' => array_column($resultats, 'inscrits')]],
                'unite' => 'étudiants',
            ],
        ];
    }

    /** Une ligne par groupe réel (identifiant), les inscriptions sans classe sous « Non renseigné ». */
    private function groupes(string $par, int $anneeId)
    {
        $requete = app(StudentCountService::class)->requeteInscrits($anneeId)
            ->leftJoin('esbtp_classes as c', 'c.id', '=', 'esbtp_inscriptions.classe_id');

        $groupe = 'c';
        if ($par !== 'classe') {
            $table = $par === 'niveau' ? 'esbtp_niveau_etudes' : 'esbtp_filieres';
            $cle = $par === 'niveau' ? 'c.niveau_etude_id' : 'c.filiere_id';
            $requete->leftJoin("{$table} as g", 'g.id', '=', $cle);
            $groupe = 'g';
        }

        return $requete->toBase()
            ->selectRaw("{$groupe}.id as gid, COALESCE(MAX({$groupe}.name), 'Non renseigné') as nom, MAX({$groupe}.code) as code, COUNT(DISTINCT esbtp_inscriptions.etudiant_id) as inscrits")
            ->groupBy("{$groupe}.id")
            ->orderByDesc('inscrits')
            ->get();
    }
}
