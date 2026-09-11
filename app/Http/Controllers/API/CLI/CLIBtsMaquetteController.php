<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\Controller;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMaquettePlaceSemestre;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Charger la maquette d'un couple filiere x niveau depuis le bulletin officiel
 * de l'ecole.
 *
 * L'ecran /esbtp/matieres/classification sait poser une place et un semestre,
 * mais seulement sur une liaison qui EXISTE deja : son enregistrement fait un
 * update, jamais un insert. Or l'ecole arrive avec un bulletin papier dont la
 * moitie des matieres n'est rattachee a rien. Rattacher a la main se fait une
 * fois, matiere par matiere, et ne se rejoue sur aucune autre instance.
 *
 * Cet endpoint fait les deux d'un coup et se rejoue : il cree la liaison
 * manquante, pose la place au bulletin et le semestre, et ne valide la maquette
 * que si on le lui demande.
 *
 * Il ne DEVINE jamais quelle matiere designe un libelle. Le catalogue d'Abidjan
 * compte quatre « Anglais » et quatre « Hydraulique appliquee » : resoudre au
 * plus proche poserait un bulletin faux que personne ne saurait relire. Un
 * libelle ambigu est refuse, avec ses candidats.
 */
class CLIBtsMaquetteController extends Controller
{
    public function charger(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return response()->json(['success' => false, 'message' => 'Token missing cli:admin ability'], 403);
        }

        $valide = $request->validate([
            'filiere' => 'required',
            'niveau' => 'required',
            'semestre' => 'required|integer|in:1,2',
            'matieres' => 'required|array|min:1',
            'matieres.*' => 'required',
            'valider' => 'sometimes|boolean',
            // Une ecriture ne part jamais sans avoir ete demandee : le defaut
            // est la simulation, pour qu'on lise ce qui sera fait avant.
            'appliquer' => 'sometimes|boolean',
        ]);

        $filiere = $this->resoudreFiliere($valide['filiere']);
        if (! $filiere) {
            return response()->json(['success' => false, 'message' => "Filiere introuvable : {$valide['filiere']}"], 404);
        }

        $niveau = $this->resoudreNiveau($valide['niveau']);
        if (! $niveau) {
            return response()->json(['success' => false, 'message' => "Niveau introuvable : {$valide['niveau']}"], 404);
        }

        $appliquer = (bool) ($valide['appliquer'] ?? false);
        $valider = (bool) ($valide['valider'] ?? false);
        $semestre = (int) $valide['semestre'];

        $lignes = [];
        $ambigus = [];
        $introuvables = [];

        foreach (array_values($valide['matieres']) as $index => $entree) {
            $place = $index + 1;
            $resolution = $this->resoudreMatiere($entree, $filiere->id, $niveau->id);

            if ($resolution['statut'] === 'ambigu') {
                $ambigus[] = ['libelle' => $resolution['libelle'], 'place' => $place, 'candidats' => $resolution['candidats']];
                continue;
            }
            if ($resolution['statut'] === 'introuvable') {
                $introuvables[] = ['libelle' => $resolution['libelle'], 'place' => $place];
                continue;
            }

            $matiere = $resolution['matiere'];
            $existante = ESBTPMatiereFilierNiveau::query()
                ->where('filiere_id', $filiere->id)
                ->where('niveau_etude_id', $niveau->id)
                ->where('matiere_id', $matiere->id)
                ->first();

            $placeSemestre = ESBTPMaquettePlaceSemestre::query()
                ->where('filiere_id', $filiere->id)
                ->where('niveau_etude_id', $niveau->id)
                ->where('semestre', $semestre)
                ->where('matiere_id', $matiere->id)
                ->value('ordre_bulletin');

            $lignes[] = [
                'place' => $place,
                'matiere_id' => $matiere->id,
                'matiere' => $matiere->name,
                'liaison' => $existante ? 'existante' : 'a_creer',
                'ordre_avant' => $existante?->ordre_bulletin,
                'semestre_avant' => $existante?->semestre,
                'place_semestre_avant' => $placeSemestre,
            ];
        }

        // Un chargement partiel poserait une maquette trouee, et « maquette
        // renseignee » vaudrait alors pour un bulletin incomplet. On refuse
        // l'ensemble tant qu'un libelle n'est pas resolu.
        if ($ambigus !== [] || $introuvables !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Des libelles ne designent pas une matiere unique : rien n a ete ecrit.',
                'data' => [
                    'filiere' => $filiere->name,
                    'niveau' => $niveau->name,
                    'ambigus' => $ambigus,
                    'introuvables' => $introuvables,
                    'resolus' => count($lignes),
                ],
            ], 422);
        }

        if (! $appliquer) {
            return response()->json([
                'success' => true,
                'message' => 'Simulation : rien n a ete ecrit. Renvoyez avec appliquer=true pour ecrire.',
                'data' => $this->rapport($filiere, $niveau, $semestre, $valider, $lignes, false),
            ]);
        }

        DB::transaction(function () use ($lignes, $filiere, $niveau, $semestre, $valider): void {
            foreach ($lignes as $ligne) {
                $attributs = [
                    'ordre_bulletin' => $ligne['place'],
                    'semestre' => $semestre,
                ];
                if ($valider) {
                    $attributs['semestre_renseigne'] = true;
                }

                ESBTPMatiereFilierNiveau::updateOrCreate(
                    [
                        'filiere_id' => $filiere->id,
                        'niveau_etude_id' => $niveau->id,
                        'matiere_id' => $ligne['matiere_id'],
                    ],
                    $attributs
                );

                // La place PAR SEMESTRE, qui seule sait qu'une matiere
                // enseignee aux deux semestres n'y occupe pas le meme rang. Le
                // pivot, unique sur (matiere, filiere, niveau), ne peut en
                // retenir qu'une : charger le second semestre ecrasait le
                // premier.
                ESBTPMaquettePlaceSemestre::updateOrCreate(
                    [
                        'filiere_id' => $filiere->id,
                        'niveau_etude_id' => $niveau->id,
                        'semestre' => $semestre,
                        'matiere_id' => $ligne['matiere_id'],
                    ],
                    ['ordre_bulletin' => $ligne['place']]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Maquette chargee pour '.count($lignes).' matiere(s).',
            'data' => $this->rapport($filiere, $niveau, $semestre, $valider, $lignes, true),
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lignes
     * @return array<string, mixed>
     */
    private function rapport(ESBTPFiliere $filiere, ESBTPNiveauEtude $niveau, int $semestre, bool $valider, array $lignes, bool $ecrit): array
    {
        return [
            'filiere' => $filiere->name,
            'filiere_id' => $filiere->id,
            'niveau' => $niveau->name,
            'niveau_id' => $niveau->id,
            'semestre' => $semestre,
            'semestres_valides' => $valider,
            'ecrit' => $ecrit,
            'liaisons_a_creer' => count(array_filter($lignes, fn ($l) => $l['liaison'] === 'a_creer')),
            'liaisons_existantes' => count(array_filter($lignes, fn ($l) => $l['liaison'] === 'existante')),
            'lignes' => $lignes,
        ];
    }

    private function resoudreFiliere(mixed $cle): ?ESBTPFiliere
    {
        if (is_numeric($cle)) {
            return ESBTPFiliere::find((int) $cle);
        }

        return ESBTPFiliere::where('code', $cle)->first() ?? ESBTPFiliere::where('name', $cle)->first();
    }

    private function resoudreNiveau(mixed $cle): ?ESBTPNiveauEtude
    {
        if (is_numeric($cle)) {
            return ESBTPNiveauEtude::find((int) $cle);
        }

        return ESBTPNiveauEtude::where('name', $cle)->first();
    }

    /**
     * Un identifiant est pris tel quel ; un libelle doit designer UNE matiere
     * BTS et une seule.
     *
     * @return array{statut: string, libelle: string, matiere?: ESBTPMatiere, candidats?: array<int, array<string, mixed>>}
     */
    private function resoudreMatiere(mixed $entree, int $filiereId, int $niveauId): array
    {
        $libelle = is_array($entree) ? (string) ($entree['nom'] ?? $entree['id'] ?? '') : (string) $entree;

        $id = is_array($entree) ? ($entree['id'] ?? null) : (is_numeric($entree) ? $entree : null);
        if ($id !== null) {
            $matiere = ESBTPMatiere::find((int) $id);

            return $matiere
                ? ['statut' => 'ok', 'libelle' => $matiere->name, 'matiere' => $matiere]
                : ['statut' => 'introuvable', 'libelle' => (string) $id];
        }

        $cible = $this->normaliser($libelle);
        $candidats = ESBTPMatiere::query()
            ->whereNull('unite_enseignement_id') // BTS : une ECUE LMD n a rien a faire ici
            ->get(['id', 'name', 'code'])
            ->filter(fn ($m) => $this->normaliser($m->name) === $cible)
            ->values();

        if ($candidats->isEmpty()) {
            return ['statut' => 'introuvable', 'libelle' => $libelle];
        }
        if ($candidats->count() === 1) {
            return ['statut' => 'ok', 'libelle' => $libelle, 'matiere' => $candidats->first()];
        }

        // Un doublon deja rattache a CE couple filiere x niveau n en est plus
        // un : l ecole a deja tranche, on suit sa decision.
        $dejaLiees = ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->whereIn('matiere_id', $candidats->pluck('id'))
            ->pluck('matiere_id');

        if ($dejaLiees->count() === 1) {
            return ['statut' => 'ok', 'libelle' => $libelle, 'matiere' => $candidats->firstWhere('id', $dejaLiees->first())];
        }

        return [
            'statut' => 'ambigu',
            'libelle' => $libelle,
            'candidats' => $candidats->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->code])->all(),
        ];
    }

    private function normaliser(?string $valeur): string
    {
        $sansAccent = \Illuminate\Support\Str::ascii((string) $valeur);

        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($sansAccent, 'UTF-8')) ?? '';
    }
}
