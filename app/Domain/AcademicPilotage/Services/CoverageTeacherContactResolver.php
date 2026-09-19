<?php

declare(strict_types=1);

namespace App\Domain\AcademicPilotage\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPPlanificationAcademique;
use App\Services\BulletinInlineConfigurationService;
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
 * 2. A defaut, les noms saisis dans « Editer les professeurs », lus par le
 *    lecteur canonique `BulletinInlineConfigurationService::loadProfesseursTemplate()`.
 *
 * Ce lecteur n'est pas un detail d'implementation : les noms vivent d'abord
 * dans un REGLAGE (`bulletin_professeurs_template.{classe}.{annee}.{periode}`),
 * et seulement ensuite dans `esbtp_bulletins.professeurs`, qui n'est renseigne
 * qu'a la GENERATION d'un bulletin. Or l'ecole consulte ce bandeau AVANT de
 * generer. Une premiere version lisait la table directement : sur Abidjan, le
 * reglage de la classe 46 portait 17 professeurs pour le semestre 2 — dont
 * « M TOURE » pour Pathologie — et le bandeau affichait quand meme « aucun
 * enseignant ». Ne pas reecrire ce lecteur : l'appeler.
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
    public function __construct(
        private readonly BulletinInlineConfigurationService $configuration,
    ) {}

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

        return $this->completerParLaConfiguration($carte, $classe, $anneeId, $semestre, $matieres);
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
     * saisi dans la configuration des bulletins.
     *
     * Le planning garde la priorite : lui seul porte un telephone.
     *
     * DECISION ASSUMEE : on comble aussi quand `contactUnique()` a rendu `null`
     * DELIBEREMENT, c'est-a-dire quand deux enseignants differents sont
     * declares au planning. Ce n'est pas un trou, c'est un refus de trancher —
     * mais un nom que l'ecole a elle-meme saisi vaut mieux que rien, et
     * l'infobulle dit d'ou il vient. Ne pas « corriger » dans un sens ou dans
     * l'autre sans relire cette ligne.
     *
     * @param  array<int, array<string, mixed>|null>  $carte
     * @param  Collection<int, \App\Models\ESBTPMatiere>  $matieres
     * @return array<int, array<string, mixed>|null>
     */
    private function completerParLaConfiguration(
        array $carte,
        ESBTPClasse $classe,
        int $anneeId,
        ?int $semestre,
        Collection $matieres,
    ): array {
        $aCombler = $matieres->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => ($carte[$id] ?? null) !== null);

        if ($aCombler->isEmpty()) {
            return $carte;
        }

        $template = $this->configuration->loadProfesseursTemplate(
            (int) $classe->id,
            $anneeId,
            $semestre === null ? 'annuel' : 'semestre' . $semestre,
        );

        foreach ($aCombler as $matiereId) {
            // Les cles du reglage viennent de JSON : numeriques en chaines.
            $nom = trim((string) ($template[$matiereId] ?? $template[(string) $matiereId] ?? ''));

            if ($nom === '') {
                continue;
            }

            $carte[$matiereId] = [
                'id' => null,
                'name' => $nom,
                // La configuration ne stocke qu'un nom : pas de numero.
                'phone' => null,
                'source' => 'bulletin',
            ];
        }

        return $carte;
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
