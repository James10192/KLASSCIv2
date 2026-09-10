<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Domain\BtsTroncCommun\BtsMaquette;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use Illuminate\Support\Collection;

/**
 * Quelles matieres sont ATTENDUES pour une classe, une annee et une periode.
 *
 * C'est le denominateur de la couverture des notes : « 7 matieres sur 12 ».
 * Se tromper ici ne produit aucune erreur — seulement un chiffre faux, ce qui
 * est pire, parce qu'une ecole s'en sert pour decider si elle peut generer ses
 * bulletins.
 *
 * DEUX DEFAUTS QUE CE SERVICE CORRIGE, cote BTS :
 *
 * 1. La couverture interrogeait le pivot du seul couple (filiere, niveau) de la
 *    classe. Elle ignorait donc l'union avec le tronc commun parent : une
 *    classe de specialite ne voyait pas les matieres sur lesquelles ses
 *    etudiants avaient ete notes pendant la phase de tronc commun. Et elle ne
 *    filtrait pas les matieres classees « specialite » rattachees par erreur a
 *    un combo de tronc commun.
 * 2. Elle ignorait le semestre, faute qu'il existe. Quand la maquette du couple
 *    a ete validee, une matiere prevue au seul semestre 2 ne doit plus etre
 *    attendue au semestre 1.
 *
 * LE LMD RESTE INCHANGE. Il tient ses matieres par parcours -> UE -> ECUE, et
 * la couverture LMD n'a montre aucun des deux defauts ci-dessus. Lui appliquer
 * le resolver BTS melangerait deux systemes que le depot separe strictement, et
 * changerait un comportement qu'aucune regle metier validee ne demande de
 * changer.
 *
 * @see .claude/rules/lmd-bts-bulletin-separation.md
 * @see .claude/rules/lmd-bts-matieres-single-source.md
 */
final class ExpectedSubjectsResolver
{
    public function __construct(
        private readonly BtsBulletinSubjectResolver $btsResolver,
        private readonly BtsMaquette $maquette,
        private readonly AcademicPeriodNormalizer $periods,
        private readonly AcademicSystemNormalizer $systems,
    ) {}

    /**
     * @return array{
     *     subjects: Collection<int, ESBTPMatiere>,
     *     maquette_renseignee: bool,
     *     maquette_etat: string,
     *     semestre: int|null,
     *     systeme: string
     * }
     */
    public function forClasse(ESBTPClasse $classe, string $periode): array
    {
        $systeme = $this->systems->normalize($classe->systeme_academique);
        $semestre = $this->periods->semesterNumber($periode);

        if ($systeme !== AcademicSystemNormalizer::BTS) {
            return $this->reponse($this->matieresHistoriques($classe), BtsMaquette::ETAT_AUCUN, $semestre, $systeme);
        }

        $etat = $this->maquette->etatPourClasse($classe);

        // Periode annuelle : le semestre ne discrimine pas, on attend l'union.
        // Un etat autre que COMPLET laisse la maquette de cote et rend la liste
        // entiere : on prefere attendre une matiere de trop qu'en oublier une
        // sur laquelle des notes existent.
        if ($semestre === null || $etat !== BtsMaquette::ETAT_COMPLET) {
            return $this->reponse($this->btsResolver->subjectsForClasse($classe), $etat, $semestre, $systeme);
        }

        return $this->reponse(
            $this->maquette->subjectsForClasseAndSemestre($classe, $semestre),
            $etat,
            $semestre,
            $systeme
        );
    }

    /**
     * Comportement d'avant, conserve pour le LMD : les matieres rattachees au
     * couple (filiere, niveau) de la classe, sans union ni filtre.
     *
     * @return Collection<int, ESBTPMatiere>
     */
    private function matieresHistoriques(ESBTPClasse $classe): Collection
    {
        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return collect();
        }

        return ESBTPMatiere::query()
            ->where('is_active', true)
            ->whereHas('liaisonsFilieresNiveaux', function ($query) use ($classe): void {
                $query->where('filiere_id', $classe->filiere_id)
                    ->where('niveau_etude_id', $classe->niveau_etude_id);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * @param  Collection<int, ESBTPMatiere>  $matieres
     * @return array<string, mixed>
     */
    private function reponse(Collection $matieres, string $etat, ?int $semestre, string $systeme): array
    {
        return [
            'subjects' => $matieres,
            'maquette_renseignee' => $etat === BtsMaquette::ETAT_COMPLET,
            'maquette_etat' => $etat,
            'semestre' => $semestre,
            'systeme' => $systeme,
        ];
    }
}
