<?php

namespace App\Services\ESBTP;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Models\ESBTPResultat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Périmètre académique BTS de la fiche étudiant.
 *
 * Le semestre 1 peut appartenir à la classe de tronc commun alors que le
 * semestre 2 appartient à la classe de spécialisation. Ne jamais filtrer
 * l'année entière sur inscription.classe_id.
 *
 * Ne modifie ni les résultats ni les bulletins officiels.
 */
final class BtsStudentAcademicRecords
{
    public function __construct(private BtsAnnualClassMapResolver $classMapResolver)
    {
    }

    /** @return array{inscription_id:int|null,source_model:string,semestre1_classe_id:int,semestre2_classe_id:int} */
    public function classMap(ESBTPInscription $inscription): array
    {
        return $this->classMapResolver->resolveForInscription(
            $inscription,
            (int) $inscription->annee_universitaire_id,
            (int) $inscription->classe_id
        );
    }

    /** @return Collection<int, ESBTPBulletin> */
    public function bulletins(int $etudiantId, ESBTPInscription $inscription): Collection
    {
        return $this->parcours(
            ESBTPBulletin::query()
                ->where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $inscription->annee_universitaire_id),
            $this->classMap($inscription)
        )->orderBy('periode')->get();
    }

    /** @return Collection<int, ESBTPResultat> */
    public function resultats(int $etudiantId, ESBTPInscription $inscription): Collection
    {
        return $this->parcours(
            ESBTPResultat::query()
                ->where('etudiant_id', $etudiantId)
                ->where('annee_universitaire_id', $inscription->annee_universitaire_id),
            $this->classMap($inscription)
        )->with('matiere')->get();
    }

    /**
     * La classe change entre S1 et S2, pas l'année de rattachement.
     * Un resultat d'une autre classe ou d'une autre période ne doit jamais
     * rejoindre le dossier, même si le même étudiant y possède des notes.
     *
     * @param array{semestre1_classe_id:int,semestre2_classe_id:int} $map
     */
    private function parcours(Builder $query, array $map): Builder
    {
        return $query->where(function (Builder $parcours) use ($map) {
            $parcours->where(function (Builder $s1) use ($map) {
                $s1->where('classe_id', $map['semestre1_classe_id'])
                    ->whereIn('periode', ['semestre1', '1']);
            })->orWhere(function (Builder $s2) use ($map) {
                $s2->where('classe_id', $map['semestre2_classe_id'])
                    ->whereIn('periode', ['semestre2', '2']);
            });
        });
    }
}
