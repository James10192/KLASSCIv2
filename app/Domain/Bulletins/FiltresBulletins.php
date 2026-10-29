<?php

namespace App\Domain\Bulletins;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * L'état filtrable de la liste des bulletins : les choix offerts et la
 * sélection, normalisée une fois pour toutes.
 *
 * Le tableau se filtrait depuis l'URL, côté serveur, pendant que l'export
 * lisait le formulaire, côté navigateur. Deux sources pour un même ensemble :
 * quand l'URL portait une valeur que le sélecteur ne sait pas afficher, la
 * page filtrait dessus tout en affichant « Toutes les périodes », le tableau
 * se vidait, et l'export — qui suivait le formulaire — emportait soixante-dix
 * bulletins pendant qu'on en voyait zéro.
 *
 * Les référentiels et la sélection vivent donc ensemble : la liste qui remplit
 * le sélecteur est celle qui valide. Le sélecteur ne peut plus recevoir un
 * état qu'il ne sait pas rendre.
 *
 * Deux écarts assumés, l'un et l'autre visibles à l'écran :
 *
 * - une année hors liste, ou absente, retombe sur l'année en cours : une liste
 *   de bulletins bornée à aucune année n'aurait pas de sens. Le sélecteur
 *   d'année ne propose donc pas de choix vide ;
 * - les classes offertes sont les classes actives non LMD. Une classe archivée
 *   n'est pas filtrable — ses bulletins restent atteignables par l'année et la
 *   recherche.
 */
final class FiltresBulletins
{
    /**
     * Les périodes offertes, seule déclaration.
     *
     * « Annuel » y figure sans condition : l'exposer toujours, quitte à ce que
     * l'option soit parfois vide, coûte moins cher qu'un filtre qui ment.
     * L'annuel n'est pas une période isolée mais l'agrégation S1+S2 ; on le
     * garde pour retrouver les artefacts legacy en base.
     */
    public const PERIODES = [
        'semestre1' => 'Premier Semestre',
        'semestre2' => 'Deuxième Semestre',
        'annuel' => 'Annuel (legacy)',
    ];

    private function __construct(
        public readonly Collection $classes,
        public readonly Collection $annees,
        public readonly ?int $classeId,
        public readonly ?int $anneeId,
        public readonly ?string $periode,
        public readonly ?int $publie,
        public readonly string $recherche,
    ) {}

    public static function depuis(Request $requete): self
    {
        $classes = self::classesOffertes();
        $annees = self::anneesOffertes();

        $periode = (string) $requete->input('periode_id');
        $publie = (string) $requete->input('published');

        return new self(
            classes: $classes,
            annees: $annees,
            classeId: self::parmi($requete->input('classe_id'), $classes),
            anneeId: self::parmi($requete->input('annee_universitaire_id'), $annees)
                ?? self::anneeParDefaut($annees),
            periode: array_key_exists($periode, self::PERIODES) ? $periode : null,
            publie: in_array($publie, ['0', '1'], true) ? (int) $publie : null,
            recherche: trim((string) $requete->input('search', '')),
        );
    }

    /** @return array<string, string> */
    public function periodes(): array
    {
        return self::PERIODES;
    }

    /**
     * Colonnes qualifiées `esbtp_bulletins.` : l'export joint esbtp_classes,
     * qui porte aussi une colonne legacy annee_universitaire_id — sans le
     * préfixe, le WHERE devient ambigu (erreur 1052).
     */
    public function appliquerA(Builder $query): void
    {
        if ($this->classeId !== null) {
            $query->where('esbtp_bulletins.classe_id', $this->classeId);
        }
        if ($this->anneeId !== null) {
            $query->where('esbtp_bulletins.annee_universitaire_id', $this->anneeId);
        }
        if ($this->periode !== null) {
            $query->where('esbtp_bulletins.periode', $this->periode);
        }
        if ($this->publie !== null) {
            $query->where('esbtp_bulletins.is_published', $this->publie);
        }
        if ($this->recherche !== '') {
            $like = '%'.$this->recherche.'%';
            $query->whereHas('etudiant', function ($q) use ($like) {
                $q->where('matricule', 'like', $like)
                    ->orWhere('nom', 'like', $like)
                    ->orWhere('prenoms', 'like', $like);
            });
        }
    }

    /** Classes offertes : BTS actives, hors LMD qui a ses propres bulletins. */
    private static function classesOffertes(): Collection
    {
        return ESBTPClasse::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('systeme_academique')->orWhere('systeme_academique', '!=', 'LMD'))
            ->orderBy('name')->get();
    }

    private static function anneesOffertes(): Collection
    {
        return ESBTPAnneeUniversitaire::orderBy('annee_debut', 'desc')->get();
    }

    /** Année en cours, repli sur la plus récente année ouverte. */
    private static function anneeParDefaut(Collection $annees): ?int
    {
        return $annees->firstWhere('is_current', true)?->id
            ?? $annees->firstWhere('is_active', true)?->id;
    }

    /** Retient la valeur seulement si elle fait partie des choix offerts. */
    private static function parmi(mixed $valeur, Collection $offerts): ?int
    {
        if (! is_scalar($valeur) || $valeur === '') {
            return null;
        }

        return $offerts->contains(fn ($modele) => (int) $modele->id === (int) $valeur)
            ? (int) $valeur
            : null;
    }
}
