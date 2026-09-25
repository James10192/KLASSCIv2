<?php

namespace App\Services\Chatbot\Tools;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Facades\DB;

/**
 * Inscrits de l'année répartis par filière, niveau ou classe, avec la même règle
 * de comptage que le tableau de bord (StudentCountService) : inscription active,
 * dossier étudiant créé, un étudiant compté une fois par groupe.
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
            . "mêmes chiffres que le tableau de bord. Affiche un graphique. À utiliser pour « combien d'étudiants par filière », "
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

        [$jointure, $libelle] = match ($par) {
            'niveau' => ['esbtp_niveau_etudes', 'n.name'],
            'classe' => [null, 'c.name'],
            default => ['esbtp_filieres', 'n.name'],
        };

        $requete = DB::table('esbtp_inscriptions as i')
            ->join('esbtp_classes as c', 'c.id', '=', 'i.classe_id')
            ->where('i.annee_universitaire_id', $annee->id)
            ->where('i.status', 'active')
            ->where('i.workflow_step', 'etudiant_cree')
            ->whereNull('i.deleted_at');

        if ($jointure) {
            $requete->leftJoin("{$jointure} as n", 'n.id', '=', $par === 'niveau' ? 'c.niveau_etude_id' : 'c.filiere_id');
        }

        $lignes = $requete
            ->selectRaw("COALESCE({$libelle}, 'Non renseigné') as groupe, COUNT(DISTINCT i.etudiant_id) as inscrits")
            ->groupBy('groupe')
            ->orderByDesc('inscrits')
            ->get();

        $total = (int) $lignes->sum('inscrits');
        $resultats = $lignes->map(fn ($l) => [
            $par => $l->groupe,
            'inscrits' => (int) $l->inscrits,
            'part' => $total > 0 ? round($l->inscrits / $total * 100, 1) . ' %' : '0 %',
        ])->values()->all();

        $noms = ['filiere' => 'filière', 'niveau' => 'niveau', 'classe' => 'classe'];

        return [
            'results' => $resultats,
            'count' => count($resultats),
            'annee' => $annee->name,
            'totaux' => ['inscrits' => $total],
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
}
