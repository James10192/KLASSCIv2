<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Str;

/**
 * Retrouver une filiere, un niveau, une matiere depuis ce qu'a ecrit l'appelant.
 *
 * ON NE DEVINE JAMAIS. Le catalogue d'ESBTP Abidjan compte quatre « Anglais »
 * et quatre « Hydraulique appliquee » : resoudre au plus proche poserait un
 * bulletin faux que personne ne saurait relire. Un libelle ambigu est rendu
 * comme tel, avec ses candidats, et c'est l'appelant qui tranche.
 *
 * BTS uniquement POUR CE QUI ENTRE : une ECUE LMD (`unite_enseignement_id` non
 * nul) est refusee au chargement, qu'elle soit designee par son libelle ou par
 * son identifiant. La garde n'existait que sur le libelle, et l'identifiant
 * passait a cote.
 *
 * MAIS PAS POUR CE QUI SORT. Refuser une ECUE au RETRAIT aussi rendait la
 * ligne fautive inextirpable : le CLI la listait (`lignesDeLaMaquette` ne
 * filtre pas — c'est voulu, c'est le seul endroit ou on peut la VOIR), l'ecran
 * de classification l'ecartait de ses lignes donc n'offrait aucune croix, et
 * `POST /retirer` repondait « ne designe pas une matiere unique » pour un
 * identifiant que l'endpoint voisin venait de rendre. Le retrait est le geste
 * CORRECTEUR : il ne peut pas contaminer une maquette, il ne peut que la
 * nettoyer. D'ou les DEUX methodes publiques : `matiere()` et
 * `matierePourRetrait()`.
 *
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
 * @see .claude/rules/lmd-bts-matieres-single-source.md
 */
final class ResolutionDeMatiere
{
    /**
     * La filiere designee par un identifiant, un code ou un nom.
     *
     * « ON NE DEVINE JAMAIS » vaut ici aussi, et ne valait pas. Deux versions
     * de ces methodes prenaient le PREMIER resultat d'un `where('name', …)`,
     * or `name` n'est unique ni sur `esbtp_filieres` ni sur
     * `esbtp_niveau_etudes` — seul `code` l'est (migrations de mars 2024).
     * Deux filieres homonymes faisaient donc charger une maquette contre
     * l'une des deux, au hasard de l'ordre d'insertion, sans un mot.
     *
     * LES REFLETS LMD SONT ECARTES. `FiliereMiroirLmd` cree des filieres au
     * nom et au code d'un parcours (cf. `classe-lmd-filiere-as-mention.md`) ;
     * une maquette BTS chargee en nommant sa filiere pouvait tomber dessus et
     * n'apparaitre sur aucune classe BTS. Silencieux des deux bouts.
     *
     * @return array{statut: 'ok'|'ambigu'|'introuvable', libelle: string, filiere?: ESBTPFiliere, candidats?: array<int, array{id: int, name: string, code: ?string}>}
     */
    public function filiere(mixed $cle): array
    {
        if (is_numeric($cle)) {
            $trouvee = ESBTPFiliere::horsMiroirLmd()->find((int) $cle);

            return $trouvee
                ? ['statut' => 'ok', 'libelle' => (string) $cle, 'filiere' => $trouvee]
                : ['statut' => 'introuvable', 'libelle' => (string) $cle];
        }

        // Le code d'abord : c'est la seule cle unique des deux.
        $parCode = ESBTPFiliere::horsMiroirLmd()->where('code', $cle)->first();

        if ($parCode) {
            return ['statut' => 'ok', 'libelle' => (string) $cle, 'filiere' => $parCode];
        }

        $parNom = ESBTPFiliere::horsMiroirLmd()->where('name', $cle)->get();

        return $this->uneSeule($parNom, (string) $cle, 'filiere');
    }

    /**
     * Le niveau designe par un identifiant, un code ou un nom.
     *
     * Le code n'etait meme pas essaye, alors que c'est la cle unique.
     *
     * @return array{statut: 'ok'|'ambigu'|'introuvable', libelle: string, niveau?: ESBTPNiveauEtude, candidats?: array<int, array{id: int, name: string, code: ?string}>}
     */
    public function niveau(mixed $cle): array
    {
        if (is_numeric($cle)) {
            $trouve = ESBTPNiveauEtude::find((int) $cle);

            return $trouve
                ? ['statut' => 'ok', 'libelle' => (string) $cle, 'niveau' => $trouve]
                : ['statut' => 'introuvable', 'libelle' => (string) $cle];
        }

        $parCode = ESBTPNiveauEtude::where('code', $cle)->first();

        if ($parCode) {
            return ['statut' => 'ok', 'libelle' => (string) $cle, 'niveau' => $parCode];
        }

        return $this->uneSeule(
            ESBTPNiveauEtude::where('name', $cle)->get(),
            (string) $cle,
            'niveau',
        );
    }

    /**
     * Un nom qui designe plusieurs lignes n'en designe aucune.
     *
     * Rendre les candidats plutot que `null` : l'appelant disait « introuvable »
     * pour un nom qui, au contraire, repondait deux fois.
     *
     * @param  \Illuminate\Support\Collection<int, ESBTPFiliere|ESBTPNiveauEtude>  $candidats
     * @return array{statut: 'ok'|'ambigu'|'introuvable', libelle: string, filiere?: ESBTPFiliere, niveau?: ESBTPNiveauEtude, candidats?: array<int, array{id: int, name: string, code: ?string}>}
     */
    private function uneSeule($candidats, string $libelle, string $quoi): array
    {
        if ($candidats->count() === 1) {
            return ['statut' => 'ok', 'libelle' => $libelle, $quoi => $candidats->first()];
        }

        if ($candidats->isEmpty()) {
            return ['statut' => 'introuvable', 'libelle' => $libelle];
        }

        return [
            'statut' => 'ambigu',
            'libelle' => $libelle,
            'candidats' => $candidats
                ->map(fn ($x) => ['id' => $x->id, 'name' => $x->name, 'code' => $x->code])
                ->all(),
        ];
    }

    /**
     * Resolution STRICTE : une ECUE LMD n'est pas trouvee.
     *
     * C'est le geste du chargement, celui qui contamine.
     *
     * @return array{statut: 'ok'|'ambigu'|'introuvable', libelle: string, matiere?: ESBTPMatiere, candidats?: array<int, array{id: int, name: string, code: ?string}>}
     */
    public function matiere(mixed $entree, int $filiereId, int $niveauId): array
    {
        return $this->resoudre($entree, $filiereId, $niveauId, accepteUneEcue: false);
    }

    /**
     * Resolution OUVERTE : une ECUE LMD est trouvee, elle aussi.
     *
     * Le retrait est le SEUL geste qui peut enlever une ligne posee par erreur.
     * Refuser des deux cotes rendrait la ligne inextirpable — c'est le defaut
     * que ce chantier corrige, pas celui qu'il doit reproduire.
     *
     * DEUX METHODES PUBLIQUES PLUTOT QU'UN DRAPEAU, et c'est la consigne du
     * depot : `lmd-bts-matieres-single-source.md` interdit nommement le
     * parametre booleen sur ce genre d'API, et `MatiereTreeBuilder` l'a deja
     * resolu ainsi (`buildForPlanning()` / `buildWithVolumeBudget()`). Le
     * drapeau faisait basculer la garde centrale du chantier : un appelant qui
     * l'oublie doit se voir, pas se deviner.
     *
     * @return array{statut: 'ok'|'ambigu'|'introuvable', libelle: string, matiere?: ESBTPMatiere, candidats?: array<int, array{id: int, name: string, code: ?string}>}
     */
    public function matierePourRetrait(mixed $entree, int $filiereId, int $niveauId): array
    {
        return $this->resoudre($entree, $filiereId, $niveauId, accepteUneEcue: true);
    }

    /**
     * @return array{statut: 'ok'|'ambigu'|'introuvable', libelle: string, matiere?: ESBTPMatiere, candidats?: array<int, array{id: int, name: string, code: ?string}>}
     */
    private function resoudre(mixed $entree, int $filiereId, int $niveauId, bool $accepteUneEcue): array
    {
        $libelle = is_array($entree) ? (string) ($entree['nom'] ?? $entree['id'] ?? '') : (string) $entree;

        $id = is_array($entree) ? ($entree['id'] ?? null) : (is_numeric($entree) ? $entree : null);
        if ($id !== null) {
            $matiere = ESBTPMatiere::query()
                ->unless($accepteUneEcue, fn ($q) => $q->btsOnly())
                ->find((int) $id);

            return $matiere
                ? ['statut' => 'ok', 'libelle' => $matiere->name, 'matiere' => $matiere]
                : ['statut' => 'introuvable', 'libelle' => (string) $id];
        }

        $cible = $this->normaliser($libelle);
        $candidats = ESBTPMatiere::query()
            ->unless($accepteUneEcue, fn ($q) => $q->btsOnly())
            ->get(['id', 'name', 'code'])
            ->filter(fn ($m) => $this->normaliser($m->name) === $cible)
            ->values();

        if ($candidats->isEmpty()) {
            return ['statut' => 'introuvable', 'libelle' => $libelle];
        }
        if ($candidats->count() === 1) {
            return ['statut' => 'ok', 'libelle' => $libelle, 'matiere' => $candidats->first()];
        }

        // Un doublon deja rattache a CE couple filiere x niveau n'en est plus
        // un : l'ecole a deja tranche, on suit sa decision.
        $dejaLiees = ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->whereIn('matiere_id', $candidats->pluck('id'))
            ->pluck('matiere_id');

        if ($dejaLiees->count() === 1) {
            return [
                'statut' => 'ok',
                'libelle' => $libelle,
                'matiere' => $candidats->firstWhere('id', $dejaLiees->first()),
            ];
        }

        return [
            'statut' => 'ambigu',
            'libelle' => $libelle,
            'candidats' => $candidats->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->code])->all(),
        ];
    }

    /**
     * Rapproche deux libelles qui designent la meme matiere.
     *
     * Sans accent, sans casse, sans espace ni ponctuation : « Hydraulique
     * appliquee », « HYDRAULIQUE APPLIQUÉE » et « Hydraulique-appliquee » sont
     * le meme mot pour une ecole qui recopie son bulletin papier.
     */
    private function normaliser(?string $valeur): string
    {
        $sansAccent = Str::ascii((string) $valeur);

        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($sansAccent, 'UTF-8')) ?? '';
    }
}
