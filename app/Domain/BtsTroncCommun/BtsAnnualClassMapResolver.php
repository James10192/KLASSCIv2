<?php

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;

/**
 * Résolveur stateless du class-map annuel BTS (Tronc Commun → Spécialité).
 *
 * Pour un (étudiant, année, classe demandée), détermine quelle classe porte les
 * notes du Semestre 1 et du Semestre 2 — en supportant à la fois le modèle phases
 * (ESBTPInscriptionPhase) et le modèle legacy double-inscription
 * (inscription_origine_id + type_changement).
 *
 * Extrait verbatim de BtsCurrentResultSnapshotService::resolveAnnualClassMap pour
 * être partagé par le snapshot ET BulletinService (BTS uniquement, LMD intouché).
 */
class BtsAnnualClassMapResolver
{
    /**
     * Resultats deja calcules, indexes par « etudiant:classe:annee ».
     * Portee requete, comme l'instance du service.
     *
     * @var array<string, array{inscription_id: int|null, source_model: string, semestre1_classe_id: int, semestre2_classe_id: int}>
     */
    private array $resolveCache = [];

    public function __construct(
        private BtsPhaseResolver $btsPhaseResolver,
        private ClasseOuvertureResolver $ouvertures,
    ) {
    }

    /**
     * @return array{inscription_id: int|null, source_model: string, semestre1_classe_id: int, semestre2_classe_id: int}
     */
    public function resolve(int $etudiantId, int $requestedClasseId, int $anneeUniversitaireId): array
    {
        // Memoisation par triplet d'arguments, portee requete. La generation en
        // masse appelait ce resolveur plusieurs fois par etudiant et par
        // etudiant de la cohorte, soit un cout quadratique pour un resultat
        // strictement identique a arguments egaux.
        $cacheKey = $etudiantId.':'.$requestedClasseId.':'.$anneeUniversitaireId;

        if (array_key_exists($cacheKey, $this->resolveCache)) {
            return $this->resolveCache[$cacheKey];
        }

        return $this->resolveCache[$cacheKey] = $this->resolveUncached(
            $etudiantId,
            $requestedClasseId,
            $anneeUniversitaireId
        );
    }

    /**
     * Meme carte, pour un appelant qui a deja elu son inscription.
     *
     * Deux elections dans le meme systeme se contredisent : le service de
     * contexte choisit la sienne (statut, date), le resolveur refaisait la
     * sienne (classe demandee, statut, date). Sur un dossier reinscrit deux
     * fois la meme annee, le contexte pouvait retourner une inscription et une
     * classe effective venues de deux inscriptions differentes.
     *
     * @return array{inscription_id: int|null, source_model: string, semestre1_classe_id: int, semestre2_classe_id: int}
     */
    public function resolveForInscription(
        ESBTPInscription $inscription,
        int $anneeUniversitaireId,
        ?int $classeDemandee = null,
    ): array {
        // La classe demandee doit suivre : c'est elle qui departage les
        // ex aequo. Sans la propager, les deux preferences du departage
        // s'effondraient sur la meme valeur et la page pouvait designer une
        // autre classe que le bulletin pour le meme semestre.
        $demandee = $classeDemandee ?? (int) $inscription->classe_id;
        $cle = 'i'.$inscription->id.':'.$demandee.':'.$anneeUniversitaireId;

        return $this->resolveCache[$cle] ??= $this->carte($inscription, $demandee, $anneeUniversitaireId);
    }

    /**
     * @return array{inscription_id: int|null, source_model: string, semestre1_classe_id: int, semestre2_classe_id: int}
     */
    private function resolveUncached(int $etudiantId, int $requestedClasseId, int $anneeUniversitaireId): array
    {
        $inscription = ESBTPInscription::query()
            ->with([
                'filiere',
                'phases.classe.filiere',
                'inscriptionOrigine.classe.filiere',
                'inscriptionSpecialisation.classe.filiere',
            ])
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->orderByRaw('CASE WHEN classe_id = ? THEN 0 ELSE 1 END', [$requestedClasseId])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('date_inscription')
            ->orderByDesc('id')
            ->first();

        if (! $inscription) {
            return [
                'inscription_id' => null,
                'source_model' => 'phase_based',
                'semestre1_classe_id' => $requestedClasseId,
                'semestre2_classe_id' => $requestedClasseId,
            ];
        }

        return $this->carte($inscription, $requestedClasseId, $anneeUniversitaireId);
    }

    /**
     * @return array{inscription_id: int|null, source_model: string, semestre1_classe_id: int, semestre2_classe_id: int}
     */
    private function carte(ESBTPInscription $inscription, int $requestedClasseId, int $anneeUniversitaireId): array
    {
        $etudiantId = (int) $inscription->etudiant_id;

        $journey = $this->btsPhaseResolver->buildJourney($inscription);
        $semestre1Phase = $this->btsPhaseResolver->resolveSemesterPhase($inscription, 1);
        $semestre2Phase = $this->btsPhaseResolver->resolveSemesterPhase($inscription, 2);

        // Repli sur les notes, et seulement pour la population concernee.
        //
        // Un etudiant passe en specialite sans que son parcours ait ete saisi
        // garde une seule inscription, sur la specialite, et aucune phase : la
        // carte rendait alors cette classe pour les DEUX semestres, et le
        // semestre passe en tronc commun paraissait vide -- zero de moyenne,
        // bulletin sans matieres, alors que les notes existaient.
        //
        // La garde compte autant que le repli : sans phase, la chronologie est
        // vide pour TOUT etudiant dont la filiere n'est pas un tronc commun,
        // c'est-a-dire la population ordinaire des six ecoles. On ne se replie
        // donc que sur une inscription en filiere fille, la seule ou « le
        // parcours existe mais n a pas ete saisi » a un sens.
        $parNotes = $this->doitLireLesNotes($inscription, $semestre1Phase, $semestre2Phase)
            ? $this->classesPortantLesNotes($etudiantId, $anneeUniversitaireId, $requestedClasseId, (int) $inscription->classe_id)
            : [1 => null, 2 => null];

        return [
            'inscription_id' => $inscription->id,
            'source_model' => $journey['source_model'] ?? 'phase_based',
            'semestre1_classe_id' => $semestre1Phase['classe_id'] ?? $parNotes[1] ?? $inscription->classe_id,
            'semestre2_classe_id' => $semestre2Phase['classe_id'] ?? $parNotes[2] ?? $inscription->classe_id,
        ];
    }

    /**
     * Faut-il interroger les notes ?
     *
     * Trois conditions, et il faut les trois : un semestre sans reponse du
     * modele de phases, aucune phase saisie du tout, et une inscription sur une
     * filiere fille. La troisieme est la garde : sans elle, la chronologie vide
     * de tout etudiant BTS ordinaire ferait du vote l'arbitre par defaut de la
     * classe porteuse -- pour les rangs, les coefficients et les absences des
     * six ecoles.
     */
    private function doitLireLesNotes(ESBTPInscription $inscription, ?array $semestre1Phase, ?array $semestre2Phase): bool
    {
        if ($semestre1Phase !== null && $semestre2Phase !== null) {
            return false;
        }

        if ($inscription->phases->isNotEmpty()) {
            return false;
        }

        return $inscription->filiere?->parent_id !== null;
    }

    /**
     * La classe qui porte les notes de chaque semestre, d'apres les evaluations.
     *
     * Meme lecture que la generation des bulletins : evaluations annulees
     * exclues, et classes pas encore ouvertes au semestre demande ecartees --
     * sans quoi une evaluation mal datee dans une classe de specialite pourrait
     * se faire elire porteuse du semestre 1, ce qui est le bug « Securite »
     * pris par l'autre bout.
     *
     * Le depart est explicite : a egalite de notes, la classe demandee prime,
     * puis celle de l'inscription, puis le plus petit identifiant. Un
     * GROUP BY sans ordre rendait le rang d'un bulletin dependant de l'ordre de
     * retour de la base.
     *
     * @return array{1: int|null, 2: int|null}
     */
    private function classesPortantLesNotes(
        int $etudiantId,
        int $anneeUniversitaireId,
        int $classeDemandee,
        int $classeInscription,
    ): array {
        $lignes = ESBTPNote::query()
            ->join('esbtp_evaluations', 'esbtp_evaluations.id', '=', 'esbtp_notes.evaluation_id')
            ->where('esbtp_notes.etudiant_id', $etudiantId)
            ->whereNull('esbtp_notes.deleted_at')
            ->whereNull('esbtp_evaluations.deleted_at')
            ->where('esbtp_evaluations.annee_universitaire_id', $anneeUniversitaireId)
            ->where('esbtp_evaluations.status', '!=', 'cancelled')
            ->whereNotNull('esbtp_evaluations.classe_id')
            ->selectRaw('esbtp_evaluations.classe_id as classe_id, esbtp_evaluations.periode as periode, COUNT(*) as total')
            ->groupBy('esbtp_evaluations.classe_id', 'esbtp_evaluations.periode')
            ->orderByDesc('total')
            ->orderBy('esbtp_evaluations.classe_id')
            ->toBase()
            ->get();

        $porteuse = function (int $semestre) use ($lignes, $classeDemandee, $classeInscription): ?int {
            // La connaissance des valeurs de la colonne vit sur le modele qui la porte.
            $alias = ESBTPEvaluation::aliasDePeriode('semestre'.$semestre);

            $candidates = $lignes
                ->filter(fn ($ligne) => in_array((string) $ligne->periode, $alias, true))
                ->filter(fn ($ligne) => $this->ouvertures->estOuverteAu((int) $ligne->classe_id, $semestre))
                ->values();

            if ($candidates->isEmpty()) {
                return null;
            }

            $meilleur = (int) $candidates->max('total');
            $exaequo = $candidates->filter(fn ($ligne) => (int) $ligne->total === $meilleur);

            foreach ([$classeDemandee, $classeInscription] as $prefere) {
                $trouve = $exaequo->firstWhere('classe_id', $prefere);
                if ($trouve) {
                    return (int) $trouve->classe_id;
                }
            }

            return (int) $exaequo->sortBy('classe_id')->first()->classe_id;
        };

        return [
            1 => $porteuse(1),
            2 => $porteuse(2),
        ];
    }

}
