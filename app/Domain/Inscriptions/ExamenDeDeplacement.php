<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Services\ClassStudentService;

/**
 * Peut-on changer cet étudiant de classe cette année, et que faut-il savoir
 * avant ? Une seule lecture, partagée par la CLI (`inscriptions/move`) et par
 * Nanan. L'écriture, elle, passe dans les deux cas par
 * ClassStudentService::addStudents(), le chemin de l'écran de la classe.
 *
 * Rend un tableau :
 *  - erreur : code machine qui interdit le déplacement (null sinon) ;
 *  - saute  : le déplacement est sans objet (déjà dans la classe visée) ;
 *  - inscription, depuis, vers : les modèles lus ;
 *  - donnees : notes / résultats / bulletins déjà rattachés à la classe de départ.
 */
class ExamenDeDeplacement
{
    public function __construct(private ClassStudentService $classes)
    {
    }

    public function examiner(int $etudiantId, int $depuisId, int $versId, int $anneeId): array
    {
        $vide = ['erreur' => null, 'saute' => null, 'inscription' => null, 'depuis' => null, 'vers' => null, 'donnees' => null];

        $inscription = ESBTPInscription::where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('classe_id', $depuisId)
            ->first();
        if (! $inscription) {
            return ['erreur' => 'no_inscription_in_source_class'] + $vide;
        }
        if ($inscription->status !== 'active') {
            return ['erreur' => 'inscription_not_active', 'inscription' => $inscription] + $vide;
        }

        $dejaLa = ESBTPInscription::where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('classe_id', $versId)
            ->exists();
        if ($dejaLa) {
            return ['saute' => 'already_in_target_class', 'inscription' => $inscription] + $vide;
        }

        $depuis = ESBTPClasse::find($depuisId);
        $vers = ESBTPClasse::find($versId);
        if (! $depuis || ! $vers) {
            return ['erreur' => 'classe_not_found', 'inscription' => $inscription] + $vide;
        }

        $controle = $this->classes->checkStudentData($depuis, [$etudiantId]);

        return [
            'erreur' => null,
            'saute' => null,
            'inscription' => $inscription,
            'depuis' => $depuis,
            'vers' => $vers,
            'donnees' => ($controle['has_any_data'] ?? false) ? ($controle['students'][0] ?? []) : null,
        ];
    }
}
