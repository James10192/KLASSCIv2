<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Domain\BtsTroncCommun\BtsBulletinSubjectResolver;
use App\Domain\BtsTroncCommun\BtsMaquette;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
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
 * LE LMD A SA PROPRE SOURCE : la maquette du parcours de la classe, unite par
 * unite, pour le semestre demande. Il lisait jusqu'en octobre 2026 le pivot BTS
 * (filiere, niveau), que l'import LMD n'ecrit pas : une classe LMD n'avait donc
 * aucun element attendu, et le suivi ne pouvait dire ni ce qui manquait ni ce
 * qui etait note. Le resolver BTS ne s'y applique pas, les deux systemes
 * restent separes.
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

        if ($systeme === AcademicSystemNormalizer::LMD && $classe->parcours_id) {
            return $this->reponse($this->elementsDeLaMaquetteLmd($classe, $semestre), BtsMaquette::ETAT_COMPLET, $semestre, $systeme);
        }

        if ($systeme !== AcademicSystemNormalizer::BTS) {
            // Classe LMD sans parcours (tronc commun d'une mention) : pas de
            // maquette a lire, on garde l'ancienne lecture.
            return $this->reponse($this->matieresHistoriques($classe), BtsMaquette::ETAT_AUCUN, $semestre, $systeme);
        }

        $etat = $this->maquette->etatPourClasse($classe);

        // Periode annuelle : le semestre ne discrimine pas, on attend l'union.
        if ($semestre === null) {
            return $this->reponse($this->btsResolver->subjectsForClasse($classe), $etat, $semestre, $systeme);
        }

        // BTS1 Yakro : apres le semestre de tronc commun, la classe de
        // specialite porte son PROPRE referentiel. Tant que la maquette
        // semestrielle n'est pas totalement validee, l'ancien repli rendait
        // l'union [specialite + parent TC] et le suivi annoncait les matieres
        // du S1 « sans evaluation » au S2, alors que leurs evaluations vivent
        // legitimement dans la classe de tronc commun.
        //
        // Une maquette COMPLETE reste souveraine : elle sait expliciter qu'une
        // matiere du parent continue au S2. Le repli phase-aware ne s'applique
        // donc qu'a l'etat AUCUN/PARTIEL. BTS2 et les filieres sans TC restent
        // strictement inchanges.
        if ($etat !== BtsMaquette::ETAT_COMPLET) {
            $matieres = $this->estSpecialiteBts1ApresTroncCommun($classe, $semestre)
                ? $this->matieresDuComboDeClasse($classe)
                : $this->btsResolver->subjectsForClasse($classe);

            return $this->reponse($matieres, $etat, $semestre, $systeme);
        }

        return $this->reponse(
            $this->maquette->subjectsForClasseAndSemestre($classe, $semestre),
            $etat,
            $semestre,
            $systeme
        );
    }

    /**
     * Une classe de specialite de BTS1 a depasse sa phase de tronc commun.
     *
     * `semestres_tronc_commun` est porte par la filiere parente. On limite
     * volontairement cette regle au niveau 1 : au BTS2, « semestre 1 » designe
     * le premier semestre de la DEUXIEME annee, pas le semestre 1 du cursus.
     */
    private function estSpecialiteBts1ApresTroncCommun(ESBTPClasse $classe, int $semestre): bool
    {
        if ((int) ($classe->niveau?->year ?? 0) !== 1) {
            return false;
        }

        $filiere = $classe->filiere;
        $parent = $filiere?->parent;

        if (! $filiere || ! $parent || ! $parent->isTroncCommun()) {
            return false;
        }

        $finTroncCommun = max(1, (int) ($parent->semestres_tronc_commun ?: 1));

        return $semestre > $finTroncCommun;
    }

    /**
     * Referentiel du combo physique de la classe, sans l'union TC parente.
     *
     * Les matieres explicitement rattachees a la specialite restent donc
     * attendues. Une evaluation sur une autre matiere reste visible comme
     * « hors maquette », ce qui est preferable a fabriquer une fausse dette de
     * saisie sur tout le tronc commun du semestre precedent.
     *
     * @return Collection<int, ESBTPMatiere>
     */
    private function matieresDuComboDeClasse(ESBTPClasse $classe): Collection
    {
        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return collect();
        }

        $ids = ESBTPMatiereFilierNiveau::query()
            ->forCombo($classe->filiere_id, $classe->niveau_etude_id)
            ->whereHas('matiere', fn ($query) => $query->where('is_active', true)->btsOnly())
            ->pluck('matiere_id')
            ->unique()
            ->values();

        return ESBTPMatiere::query()
            ->whereIn('id', $ids)
            ->btsOnly()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    /**
     * Comportement d'avant, conserve pour le LMD : les matieres rattachees au
     * couple (filiere, niveau) de la classe, sans union ni filtre.
     *
     * @return Collection<int, ESBTPMatiere>
     */
    /**
     * Les elements de la maquette LMD de la classe : unites de son parcours
     * pour le semestre (tous ses semestres en vue annuelle), dans l'ordre du
     * bulletin, puis leurs elements par `getEcuesEffectifs()`, la lecture
     * canonique qui tient compte des elements reserves a un parcours.
     *
     * Chaque element porte `ue_libelle` (non persiste) : le suivi regroupe
     * par unite, comme la maquette.
     *
     * @return Collection<int, ESBTPMatiere>
     */
    private function elementsDeLaMaquetteLmd(ESBTPClasse $classe, ?int $semestre): Collection
    {
        $parcours = $classe->parcours;
        if (! $parcours) {
            return collect();
        }

        $semestres = $semestre !== null ? [$semestre] : $classe->getSemestresLMD();

        return $parcours->unitesEnseignement()
            ->wherePivotIn('semestre', $semestres)
            ->where('esbtp_unites_enseignement.is_active', true)
            ->with(['ecues', 'matieres'])
            ->orderBy('esbtp_lmd_parcours_ue.semestre')
            ->orderBy('esbtp_lmd_parcours_ue.ordre')
            ->get()
            ->flatMap(fn ($ue) => $ue->getEcuesEffectifs((int) $parcours->id)
                ->each(fn (ESBTPMatiere $ecue) => $ecue->setAttribute('ue_libelle', trim(($ue->code_affiche ?? '').' — '.$ue->name, ' —'))))
            ->unique('id')
            ->values();
    }

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
