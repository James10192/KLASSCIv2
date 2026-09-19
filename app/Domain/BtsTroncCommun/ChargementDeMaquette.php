<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPMaquettePlaceSemestre;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Facades\DB;

/**
 * Charger une maquette BTS depuis la liste ordonnee d'un bulletin officiel.
 *
 * EN DEUX TEMPS, ET C'EST LE POINT. `preparer()` ne touche a rien : il resout
 * les libelles, constate les conflits de semestre et rend ce qui SERAIT ecrit.
 * `appliquer()` ecrit. Le controleur peut donc rendre exactement la meme
 * lecture avant et apres, et la simulation n'est pas une branche parallele qui
 * finit par mentir sur ce que fait l'ecriture.
 *
 * Deux refus, jamais une devinette :
 *
 * - un libelle qui designe plusieurs matieres — le lot entier s'arrete, avec
 *   ses candidats. Un chargement partiel poserait une maquette trouee, et
 *   « maquette renseignee » vaudrait alors pour un bulletin incomplet ;
 * - un semestre deja ecrit que le chargement contredirait. Voir
 *   `SemestreDeMaquette::estUnConflit()`, qui porte le pourquoi.
 *
 * @see SemestreDeMaquette
 */
final class ChargementDeMaquette
{
    public function __construct(
        private readonly ResolutionDeMatiere $resolution,
        private readonly LiaisonsDeMatiere $liaisons,
    ) {}

    /**
     * Ce que ce chargement ferait, sans rien ecrire.
     *
     * @param  array<int, mixed>  $matieres  liste ORDONNEE : la position est la place au bulletin
     * @return array{lignes: array<int, array<string, mixed>>, ambigus: array<int, array<string, mixed>>, introuvables: array<int, array<string, mixed>>, conflits: array<int, array<string, mixed>>}
     */
    public function preparer(
        ESBTPFiliere $filiere,
        ESBTPNiveauEtude $niveau,
        ?int $semestreDuLot,
        array $matieres,
    ): array {
        $plan = ['lignes' => [], 'ambigus' => [], 'introuvables' => [], 'conflits' => []];

        foreach (array_values($matieres) as $index => $entree) {
            $place = $index + 1;
            $resolue = $this->resolution->matiere($entree, (int) $filiere->id, (int) $niveau->id);

            if ($resolue['statut'] === 'ambigu') {
                $plan['ambigus'][] = [
                    'libelle' => $resolue['libelle'],
                    'place' => $place,
                    'candidats' => $resolue['candidats'],
                ];

                continue;
            }
            if ($resolue['statut'] === 'introuvable') {
                $plan['introuvables'][] = ['libelle' => $resolue['libelle'], 'place' => $place];

                continue;
            }

            $ligne = $this->ligne($filiere, $niveau, $semestreDuLot, $entree, $resolue['matiere'], $place);

            if (isset($ligne['conflit'])) {
                $plan['conflits'][] = $ligne['conflit'];

                continue;
            }

            $plan['lignes'][] = $ligne;
        }

        return $plan;
    }

    /**
     * Ecrit le plan. Tout ou rien.
     *
     * @param  array<int, array<string, mixed>>  $lignes
     */
    public function appliquer(
        ESBTPFiliere $filiere,
        ESBTPNiveauEtude $niveau,
        array $lignes,
        bool $valider,
    ): void {
        DB::transaction(function () use ($lignes, $filiere, $niveau, $valider): void {
            foreach ($lignes as $ligne) {
                // Cree la liaison si elle manque, et ajoute le couple aux
                // pivots plats sans jamais en retirer.
                //
                // L'ORDRE COMPTE : c'est `poser()` qui porte le garde contre
                // les ECUE LMD, et l'`updateOrCreate` ci-dessous ecrit le
                // meme pivot sans garde. Deplacer cet appel APRES lui rouvrirait
                // la porte. La resolution en amont ecarte deja les ECUE, donc
                // ce chemin en a deux ; ne comptez pas sur une seule.
                $this->liaisons->poser((int) $ligne['matiere_id'], (int) $filiere->id, (int) $niveau->id);

                $attributs = [
                    'ordre_bulletin' => $ligne['place'],
                    'semestre' => $ligne['semestre_apres'],
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

                $this->poserLesPlacesParSemestre($filiere, $niveau, $ligne);
            }
        });
    }

    /**
     * Une ligne du plan, ou le conflit qui l'empeche.
     *
     * @return array<string, mixed>
     */
    private function ligne(
        ESBTPFiliere $filiere,
        ESBTPNiveauEtude $niveau,
        ?int $semestreDuLot,
        mixed $entree,
        mixed $matiere,
        int $place,
    ): array {
        $existante = ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->where('matiere_id', $matiere->id)
            ->first();

        // Le semestre pose sur la ligne prime sur celui du lot : c'est la
        // seule facon de charger une maquette dont une matiere est aux deux
        // semestres quand les autres n'y sont qu'a un. C'est aussi ainsi que
        // l'appelant tranche un conflit.
        $poseSurLaLigne = is_array($entree) && array_key_exists('semestre', $entree);
        $semestreVoulu = $poseSurLaLigne
            ? SemestreDeMaquette::depuisLaSaisie($entree['semestre'])
            : $semestreDuLot;

        if (! $poseSurLaLigne && SemestreDeMaquette::estUnConflit(
            $existante?->semestre,
            (bool) $existante?->semestre_renseigne,
            $semestreVoulu,
        )) {
            return [
                'conflit' => [
                    'matiere_id' => $matiere->id,
                    'matiere' => $matiere->name,
                    'place' => $place,
                    'declare' => SemestreDeMaquette::libelle($existante?->semestre),
                    'demande' => SemestreDeMaquette::libelle($semestreVoulu),
                ],
            ];
        }

        return [
            'place' => $place,
            'matiere_id' => $matiere->id,
            'matiere' => $matiere->name,
            'liaison' => $existante ? 'existante' : 'a_creer',
            'ordre_avant' => $existante?->ordre_bulletin,
            'semestre_avant' => $existante?->semestre,
            'semestre_apres' => $semestreVoulu,
            'semestre_libelle' => SemestreDeMaquette::libelle($semestreVoulu),
            'places_semestre_avant' => ESBTPMaquettePlaceSemestre::query()
                ->where('filiere_id', $filiere->id)
                ->where('niveau_etude_id', $niveau->id)
                ->where('matiere_id', $matiere->id)
                ->pluck('ordre_bulletin', 'semestre')
                ->all(),
        ];
    }

    /**
     * La place au bulletin, par semestre.
     *
     * Elle seule sait qu'une matiere enseignee aux deux semestres n'y occupe
     * pas le meme rang. Le pivot, unique sur (matiere, filiere, niveau), ne
     * peut en retenir qu'une : charger le second semestre ecrasait le premier.
     *
     * @param  array<string, mixed>  $ligne
     */
    private function poserLesPlacesParSemestre(
        ESBTPFiliere $filiere,
        ESBTPNiveauEtude $niveau,
        array $ligne,
    ): void {
        foreach (SemestreDeMaquette::semestresCouverts($ligne['semestre_apres']) as $semestre) {
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
    }
}
