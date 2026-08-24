<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPInscription;

/**
 * Effectif BTS aligne sur l'historique de phases, pas seulement classe_id courant.
 *
 * S1 d'un etudiant oriente : compte dans la classe TC qui portait le semestre.
 * S2 apres changement de specialite : la phase active du semestre l'emporte.
 */
final class BtsClassCohortCounter
{
    /**
     * Cohortes deja resolues, indexees par « annee:semestre » puis par classe.
     * Portee requete : le service est resolu par le conteneur, l'instance vit
     * le temps de la requete HTTP ou de la commande.
     *
     * @var array<string, array<int, array<int, true>>>
     */
    private array $cohortCache = [];

    public function __construct(private BtsPhaseResolver $phaseResolver)
    {
    }

    /**
     * Cohorte d'UN semestre. Volontairement privee : c'est la porte derobee
     * par laquelle le bug reviendrait. Son nom est le plus evident des deux,
     * et l'appeler avec « annuel » rend le semestre 2 sans le moindre signal,
     * donc une classe de tronc commun vide. La seule question qu'on pose de
     * l'exterieur est celle d'une periode, et `etudiantIdsPourPeriode` y repond.
     *
     * @return list<int>
     */
    private function etudiantIds(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        // Perimetre STRICT : les vrais inscrits. C'est ce filtre qui donne au
        // compteur son sens d'effectif, il n'est pas negociable ici.
        return $this->appartenance(
            $classeId,
            $anneeUniversitaireId,
            $this->semesterNumber($periode),
            'effectif',
            fn ($q) => $q->where('status', 'active')->where('workflow_step', 'etudiant_cree')
        );
    }

    /**
     * Meme regle d'appartenance que l'effectif, perimetre elargi a TOUTES les
     * inscriptions de l'annee, quel que soit leur statut.
     *
     * Pour les LISTES uniquement -- « inclure les inscriptions inactives » de
     * la page de resultats. Un etudiant oriente porte `terminee` sur son
     * inscription d'origine ; un abandon porte `annulee`. Les exclure les
     * faisait disparaitre de l'ecran, mais les rattraper par une requete
     * directe sur les phases posait l'AUTRE question -- « cette classe a-t-elle
     * un jour ete mentionnee » -- et ramenait l'etudiant sur son ancienne
     * specialite corrigee, voire dans deux classes au meme semestre. Ici la
     * meme election de phase s'applique : une seule classe par semestre.
     *
     * @return list<int>
     */
    public function etudiantIdsToutesInscriptions(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        return $this->appartenance(
            $classeId,
            $anneeUniversitaireId,
            $this->semesterNumber($periode),
            'toutes',
            fn ($q) => $q
        );
    }

    /**
     * Perimetre intermediaire pour les listes quand les inactifs sont exclus :
     * les inscriptions actives, workflow compris ou non. Le filtre
     * `workflow_step` de l'effectif ecarterait les dossiers « en attente »,
     * que la page de resultats a toujours montres.
     *
     * @return list<int>
     */
    public function etudiantIdsInscriptionsActives(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        return $this->appartenance(
            $classeId,
            $anneeUniversitaireId,
            $this->semesterNumber($periode),
            'actives',
            fn ($q) => $q->where('status', 'active')
        );
    }

    /**
     * La regle d'appartenance, unique : resoudre la classe de rattachement de
     * chaque inscription du perimetre pour ce semestre, par l'election de
     * phase. Seul le perimetre d'inscriptions varie ; la regle, jamais.
     *
     * Le balayage porte volontairement sur toute l'annee : la classe de
     * rattachement depend des phases et ne peut pas etre filtree en SQL. On
     * memoise par (annee, semestre, perimetre) le temps de la requete -- la
     * generation en masse appelait cette methode plusieurs fois par etudiant,
     * rechargeant a chaque fois plus de 2000 inscriptions. Le perimetre fait
     * partie de la cle : un appel en mode liste ne doit jamais empoisonner le
     * mode effectif dans la meme requete.
     *
     * @param  \Closure(\Illuminate\Database\Eloquent\Builder): mixed  $perimetre
     * @return list<int>
     */
    private function appartenance(int $classeId, int $anneeUniversitaireId, int $semester, string $modePerimetre, \Closure $perimetre): array
    {
        $cacheKey = $anneeUniversitaireId.':'.$semester.':'.$modePerimetre;

        if (! array_key_exists($cacheKey, $this->cohortCache)) {
            $requete = ESBTPInscription::query()
                ->with([
                    'filiere',
                    'classe.filiere',
                    'phases.classe.filiere',
                    'inscriptionOrigine.classe.filiere',
                    'inscriptionSpecialisation.classe.filiere',
                ])
                ->where('annee_universitaire_id', $anneeUniversitaireId);

            $perimetre($requete);

            $parClasse = [];
            foreach ($requete->get() as $inscription) {
                $resolved = $this->resolveClasseId($inscription, $semester);
                if ($resolved !== null) {
                    $parClasse[$resolved][(int) $inscription->etudiant_id] = true;
                }
            }

            $this->cohortCache[$cacheKey] = $parClasse;
        }

        $ids = array_keys($this->cohortCache[$cacheKey][$classeId] ?? []);
        sort($ids);

        return $ids;
    }

    /**
     * Qui appartient a cette classe sur cette periode. Point d'entree unique.
     *
     * INVARIANT : un etudiant appartient a AU PLUS UNE classe par periode.
     * `resolveSemesterPhase` elit une seule phase par semestre, et tous les
     * consommateurs sont ecrits la-dessus : la generation reclame ses etudiants
     * a la classe, le rang les classe entre eux, l'effectif les compte.
     *
     * Une version de cette methode rendait, pour « annuel », l'union des deux
     * semestres. Elle voulait donner un rang annuel aux classes de tronc
     * commun, dont les etudiants passent en specialite au semestre 2. Elle
     * abandonnait l'exclusivite : le meme etudiant appartenait a la cohorte
     * annuelle du tronc commun ET de sa specialite, et la generation annuelle
     * lancee sur les deux classes lui creait DEUX bulletins annuels, que la
     * cle unique (qui porte `classe_id`) laisse passer.
     *
     * Si l'ecole veut un rang annuel sur le tronc commun, la decision doit
     * rester exclusive -- un proprietaire annuel par etudiant, et un seul --
     * et se prendre dans BtsAnnualClassMapResolver, qui est deja le domicile
     * de cette carte. Pas dans une union.
     *
     * @return list<int>
     */
    public function etudiantIdsPourPeriode(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        return $this->etudiantIds($classeId, $anneeUniversitaireId, $periode);
    }

    public function countPourPeriode(int $classeId, int $anneeUniversitaireId, string $periode): int
    {
        return count($this->etudiantIdsPourPeriode($classeId, $anneeUniversitaireId, $periode));
    }

    private function resolveClasseId(ESBTPInscription $inscription, int $semester): ?int
    {
        $phase = $this->phaseResolver->resolveSemesterPhase($inscription, $semester);
        if (is_array($phase) && ! empty($phase['classe_id'])) {
            return (int) $phase['classe_id'];
        }

        return $inscription->classe_id ? (int) $inscription->classe_id : null;
    }

    private function semesterNumber(string $periode): int
    {
        return match ($periode) {
            '2', 'semestre2', 'annuel' => 2,
            default => 1,
        };
    }
}
