<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPPlanificationAcademique;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Qui relancer quand les notes d'une matiere manquent.
 *
 * Isole a dessein : c'est la SEULE lecture du planning general dans le calcul
 * de la couverture, et elle est strictement informative. Ni les matieres
 * attendues, ni le semestre, ni aucun compteur n'en dependent — le planning
 * n'entre pas dans le denominateur, il donne un nom et un numero.
 *
 * DEUX SOURCES, DANS CET ORDRE.
 *
 * 1. Le planning general (`esbtp_planifications_academiques`), qui porte un
 *    vrai utilisateur : nom ET telephone, donc un contact appelable.
 * 2. A defaut, les noms saisis dans « Editer les professeurs » du bulletin.
 *
 * Le repli n'est pas un luxe. Mesure sur ESBTP Abidjan, classe 2BTS GBAT B :
 * l'instance n'a AUCUNE planification pour ce couple filiere x niveau, donc les
 * onze matieres affichaient « Enseignant a confirmer » — y compris celles dont
 * l'ecole avait saisi le professeur sur le bulletin, juste a cote. Le libelle
 * accusait une donnee manquante qui, elle, etait bien la.
 *
 * Ce que le repli ne rend pas : un telephone. Le bulletin stocke un NOM en
 * texte, pas une fiche. L'affichage doit donc tolerer un contact sans numero.
 *
 * Une requete par source pour toute la classe, jamais une par matiere.
 */
final class CoverageTeacherContactResolver
{
    /**
     * @param  Collection<int, \App\Models\ESBTPMatiere>  $matieres
     * @return array<int, array<string, mixed>|null> matiere_id => contact (null = indetermine)
     */
    public function pourLaClasse(ESBTPClasse $classe, int $anneeId, ?int $semestre, Collection $matieres): array
    {
        if ($matieres->isEmpty()) {
            return [];
        }

        // Le planning est facultatif : sans filiere, sans niveau, ou sans table,
        // on saute cette source et on passe au bulletin. Rendre `[]` ici — ce que
        // faisait la version precedente — privait du repli les cas memes ou il
        // sert le plus.
        $carte = $this->planning($classe, $anneeId, $semestre, $matieres);

        return $this->completerParLesBulletins($carte, $classe, $anneeId, $semestre, $matieres);
    }

    /**
     * Les enseignants que le planning general designe, quand il y en a un.
     *
     * @param  Collection<int, \App\Models\ESBTPMatiere>  $matieres
     * @return array<int, array<string, mixed>|null>
     */
    private function planning(ESBTPClasse $classe, int $anneeId, ?int $semestre, Collection $matieres): array
    {
        if (! $classe->filiere_id || ! $classe->niveau_etude_id) {
            return [];
        }

        if (! Schema::hasTable('esbtp_planifications_academiques')) {
            return [];
        }

        $requete = ESBTPPlanificationAcademique::query()
            ->where('annee_universitaire_id', $anneeId)
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('is_active', true)
            ->whereNotNull('enseignant_principal_id')
            ->whereIn('matiere_id', $matieres->pluck('id')->all());

        // Periode annuelle : pas de semestre a imposer, on prend les deux.
        if ($semestre !== null) {
            $requete->where('semestre', $semestre);
        }

        $lignes = $requete->with('enseignantPrincipal:id,name,phone')
            ->get(['matiere_id', 'enseignant_principal_id']);

        $carte = [];

        foreach ($lignes->groupBy('matiere_id') as $matiereId => $duMemeSujet) {
            $carte[(int) $matiereId] = $this->contactUnique($duMemeSujet);
        }

        return $carte;
    }

    /**
     * Comble les matieres que le planning ne nomme pas, avec ce que l'ecole a
     * saisi sur le bulletin.
     *
     * Le planning garde la priorite : lui seul porte un telephone. On ne
     * remplace donc jamais un contact deja trouve, on ne comble que les trous.
     *
     * @param  array<int, array<string, mixed>|null>  $carte
     * @param  Collection<int, \App\Models\ESBTPMatiere>  $matieres
     * @return array<int, array<string, mixed>|null>
     */
    private function completerParLesBulletins(
        array $carte,
        ESBTPClasse $classe,
        int $anneeId,
        ?int $semestre,
        Collection $matieres,
    ): array {
        $aCombler = $matieres->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => ($carte[$id] ?? null) !== null);

        if ($aCombler->isEmpty() || ! Schema::hasTable('esbtp_bulletins')) {
            return $carte;
        }

        $bulletins = ESBTPBulletin::query()
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $anneeId)
            ->when($semestre !== null, fn ($q) => $q->where('periode', 'semestre' . $semestre))
            ->whereNotNull('professeurs')
            ->pluck('professeurs');

        if ($bulletins->isEmpty()) {
            return $carte;
        }

        $noms = $this->nomsParMatiere($bulletins, $aCombler->all());

        foreach ($noms as $matiereId => $nom) {
            $carte[$matiereId] = [
                'id' => null,
                'name' => $nom,
                // Le bulletin ne stocke qu'un nom : pas de numero a proposer.
                'phone' => null,
                'source' => 'bulletin',
            ];
        }

        return $carte;
    }

    /**
     * Les noms sur lesquels TOUS les bulletins de la classe s'accordent.
     *
     * Chaque bulletin porte sa propre copie ; l'ecran d'edition sait les
     * propager a la classe, mais rien ne le garantit. Deux noms differents pour
     * la meme matiere, c'est la meme situation que deux enseignants au planning :
     * on ne tranche pas au hasard.
     *
     * @param  Collection<int, string|null>  $bulletins
     * @param  list<int>  $matieresVoulues
     * @return array<int, string>
     */
    private function nomsParMatiere(Collection $bulletins, array $matieresVoulues): array
    {
        $vus = [];

        foreach ($bulletins as $brut) {
            $decode = is_string($brut) ? json_decode($brut, true) : $brut;

            if (! is_array($decode)) {
                continue;
            }

            foreach ($decode as $matiereId => $nom) {
                $matiereId = (int) $matiereId;
                $nom = is_string($nom) ? trim($nom) : '';

                if ($nom === '' || ! in_array($matiereId, $matieresVoulues, true)) {
                    continue;
                }

                $vus[$matiereId][$nom] = true;
            }
        }

        $retenus = [];

        foreach ($vus as $matiereId => $candidats) {
            if (count($candidats) === 1) {
                $retenus[$matiereId] = (string) array_key_first($candidats);
            }
        }

        return $retenus;
    }

    /**
     * Deux enseignants differents sur la meme matiere : on ne tranche pas au
     * hasard. Designer la mauvaise personne ferait relancer quelqu'un qui n'y
     * peut rien ; un contact indetermine se voit et se corrige.
     *
     * @return array<string, mixed>|null
     */
    private function contactUnique(Collection $lignes): ?array
    {
        if ($lignes->pluck('enseignant_principal_id')->unique()->count() !== 1) {
            return null;
        }

        $enseignant = $lignes->first()->enseignantPrincipal;

        if (! $enseignant) {
            return null;
        }

        return [
            'id' => (int) $enseignant->id,
            'name' => (string) $enseignant->name,
            // `users` porte bien `phone` — jamais `telephone`, qui n'existe que
            // sur les etudiants et les parents et rendrait null en silence.
            'phone' => $enseignant->phone,
            'source' => 'planning',
        ];
    }
}
