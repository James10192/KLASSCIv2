<?php

namespace App\Services;

use App\Enums\AppartenancePieceDossier;
use App\Enums\EtatPieceDossier;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPieceDeposee;
use App\Models\ESBTPPieceDossier;
use Illuminate\Support\Collection;

/**
 * Où en est le dossier d'un étudiant, pièce par pièce.
 *
 * Point d'entrée unique de la lecture. Le catalogue dit ce que l'école réclame
 * ({@see CataloguePiecesDossier}) ; ce service dit ce qui manque à qui.
 *
 * LE DISPONIBLE SE CALCULE, IL NE SE DÉCRÉMENTE PAS :
 *
 *     disponible = déposé, validé, non périmé − consommé par les inscriptions retenues
 *
 * Écrit ainsi, l'annulation d'une inscription, sa suppression et la double
 * décrémentation deviennent un `where` — et non trois écritures compensatoires
 * qu'il faudrait penser à faire, dans le bon ordre, à chaque fois. Une
 * inscription annulée cesse de consommer sans qu'aucun code ne s'en occupe.
 */
class DossierPiecesEtudiant
{
    /** Ce que l'école fait quand le stock ne suffit pas. */
    public const EPUISEMENT_BLOQUER = 'bloquer';
    public const EPUISEMENT_SIGNALER = 'signaler';
    public const EPUISEMENT_SILENCE = 'silence';

    public function __construct(private CataloguePiecesDossier $catalogue)
    {
    }

    /**
     * L'état complet du dossier pour une inscription.
     *
     * Une ligne par pièce du catalogue applicable, qu'elle ait été déposée ou
     * non : une pièce jamais apportée doit apparaître, sinon l'écran ne montre
     * que ce qui est déjà là et ne sert à rien.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function pourInscription(ESBTPInscription $inscription): Collection
    {
        $pieces = $this->catalogue->pourInscription($inscription);

        if ($pieces->isEmpty()) {
            return collect();
        }

        $etudiantId = (int) $inscription->etudiant_id;
        $codes = $pieces->pluck('id')->all();

        $depots = ESBTPPieceDeposee::query()
            ->with('document:id,titre,file_name')
            ->where('etudiant_id', $etudiantId)
            ->whereIn('piece_dossier_id', $codes)
            ->orderByDesc('date_depot')
            ->orderByDesc('id')
            ->get()
            ->groupBy('piece_dossier_id');

        $consommations = ESBTPInscriptionPiece::query()
            ->where('etudiant_id', $etudiantId)
            ->whereIn('piece_dossier_id', $codes)
            ->retenues($this->restitueALAnnulation())
            ->get()
            ->groupBy('piece_dossier_id');

        $ligneDeCetteInscription = ESBTPInscriptionPiece::query()
            ->where('inscription_id', $inscription->id)
            ->whereIn('piece_dossier_id', $codes)
            ->get()
            ->keyBy('piece_dossier_id');

        return $pieces
            ->map(fn (ESBTPPieceDossier $piece) => $this->ligne(
                $piece,
                $inscription,
                collect($depots->get($piece->id, [])),
                collect($consommations->get($piece->id, [])),
                $ligneDeCetteInscription->get($piece->id)
            ))
            ->values();
    }

    /**
     * Une ligne du dossier.
     *
     * @param  Collection<int, ESBTPPieceDeposee>  $depots
     * @param  Collection<int, ESBTPInscriptionPiece>  $consommations
     * @return array<string, mixed>
     */
    private function ligne(
        ESBTPPieceDossier $piece,
        ESBTPInscription $inscription,
        Collection $depots,
        Collection $consommations,
        ?ESBTPInscriptionPiece $ligneCourante
    ): array {
        $annuelle = $piece->appartenance === AppartenancePieceDossier::INSCRIPTION;

        // Une pièce annuelle ne regarde que les dépôts de SON année. Une pièce
        // qui dure regarde tout ce que l'étudiant a jamais remis.
        $depotsRetenus = $annuelle
            ? $depots->filter(fn (ESBTPPieceDeposee $d) => (int) $d->inscription_id === (int) $inscription->id)
            : $depots;

        $valides = $depotsRetenus->filter(fn (ESBTPPieceDeposee $d) => $d->compteDansLeStock($piece));
        $perimes = $depotsRetenus->filter(fn (ESBTPPieceDeposee $d) => $d->estPerime($piece));

        $depose = (int) $valides->sum('quantite_deposee');

        // Une pièce annuelle ne se reporte pas : ce qu'elle a consommé ailleurs
        // ne retire rien ici. Seule la ligne de cette inscription compte.
        $consomme = $annuelle
            ? (int) ($ligneCourante?->quantite_consommee ?? 0)
            : (int) $consommations->sum('quantite_consommee');

        $requis = $ligneCourante?->non_applicable
            ? 0
            : max(1, (int) $piece->exemplaires_par_inscription);

        // Ce que cette inscription-ci a déjà pris ne doit pas se retrancher
        // deux fois : le disponible affiché est celui qui resterait SI elle ne
        // consommait rien, moins ce que les autres ont pris.
        $prisAilleurs = $annuelle
            ? 0
            : $consomme - (int) ($ligneCourante?->quantite_consommee ?? 0);

        $disponible = max(0, $depose - $prisAilleurs);
        $manquant = max(0, $requis - $disponible);

        return [
            'piece' => $piece,
            'annuelle' => $annuelle,
            'requis' => $requis,
            'depose' => $depose,
            'consomme' => $consomme,
            'disponible' => $disponible,
            'manquant' => $manquant,
            'satisfaite' => $manquant === 0,
            'non_applicable' => (bool) ($ligneCourante?->non_applicable ?? false),
            'motif_non_applicable' => $ligneCourante?->motif,
            'consommation' => $ligneCourante,
            'depots' => $depotsRetenus->values(),
            'depots_perimes' => $perimes->values(),
            'etat' => $this->etatLisible($depotsRetenus, $manquant, (bool) ($ligneCourante?->non_applicable ?? false)),
        ];
    }

    /**
     * L'état montré au guichet.
     *
     * Il ne se déduit pas d'un seul dépôt : un étudiant peut avoir une pièce
     * refusée ET une autre validée pour le même besoin. On regarde donc ce qui
     * manque d'abord, et le refus ensuite — parce qu'un refus déjà remplacé
     * n'est plus un problème, alors qu'un refus non remplacé en est un, et que
     * l'écran doit dire lequel des deux.
     */
    private function etatLisible(Collection $depots, int $manquant, bool $nonApplicable): EtatPieceDossier
    {
        // Rien ne manque : la ligne est soldée. Le cas « écartée cette année »
        // se distingue à l'écran par son drapeau `non_applicable` et son motif,
        // pas par un état — l'état décrit le dépôt, et une pièce écartée n'en a
        // pas.
        if ($nonApplicable || $manquant === 0) {
            return EtatPieceDossier::VALIDEE;
        }

        $refuse = $depots->first(fn (ESBTPPieceDeposee $d) => $d->etat === EtatPieceDossier::REFUSEE);

        if ($refuse !== null) {
            return EtatPieceDossier::REFUSEE;
        }

        $enAttenteDeRelecture = $depots->first(fn (ESBTPPieceDeposee $d) => $d->etat === EtatPieceDossier::DEPOSEE);

        return $enAttenteDeRelecture !== null
            ? EtatPieceDossier::DEPOSEE
            : EtatPieceDossier::ATTENDUE;
    }

    /**
     * L'état des dossiers de PLUSIEURS inscriptions, en quelques requêtes.
     *
     * La page de pilotage regarde une promotion entière. Appeler
     * {@see pourInscription()} en boucle ferait quatre requêtes par étudiant :
     * deux mille inscriptions, huit mille requêtes, et un écran qui n'aboutit
     * pas. Ici, les dépôts et les consommations de toute la page sont chargés
     * d'un coup, et le catalogue une fois par couple (filière, niveau) — ils se
     * comptent sur les doigts, là où les étudiants se comptent par milliers.
     *
     * @param  Collection<int, ESBTPInscription>  $inscriptions
     * @return array<int, array<string, mixed>> synthèses, indexées par inscription
     */
    public function syntheseParInscription(Collection $inscriptions): array
    {
        if ($inscriptions->isEmpty()) {
            return [];
        }

        // La classe porte la filiere et le niveau, donc la portee du catalogue.
        // La charger ici plutot que de compter sur l'appelant : oublier
        // l'eager-load ne se verrait pas a l'ecran, seulement dans le temps de
        // reponse — une requete par inscription, sur une promotion entiere.
        $inscriptions->loadMissing('classe:id,filiere_id,niveau_etude_id');

        $etudiantIds = $inscriptions->pluck('etudiant_id')->filter()->unique()->all();

        $depots = ESBTPPieceDeposee::query()
            ->whereIn('etudiant_id', $etudiantIds)
            ->get()
            ->groupBy(fn (ESBTPPieceDeposee $d) => $d->etudiant_id.':'.$d->piece_dossier_id);

        $consommations = ESBTPInscriptionPiece::query()
            ->whereIn('etudiant_id', $etudiantIds)
            ->retenues($this->restitueALAnnulation())
            ->get()
            ->groupBy(fn (ESBTPInscriptionPiece $c) => $c->etudiant_id.':'.$c->piece_dossier_id);

        $lignesCourantes = ESBTPInscriptionPiece::query()
            ->whereIn('inscription_id', $inscriptions->pluck('id')->all())
            ->get()
            ->keyBy(fn (ESBTPInscriptionPiece $c) => $c->inscription_id.':'.$c->piece_dossier_id);

        $cataloguesParScope = [];
        $resultat = [];

        foreach ($inscriptions as $inscription) {
            $filiereId = $inscription->classe?->filiere_id ?? $inscription->filiere_id;
            $niveauId = $inscription->classe?->niveau_etude_id ?? $inscription->niveau_id;
            $cle = ($filiereId ?: 0).':'.($niveauId ?: 0);

            $cataloguesParScope[$cle] ??= $this->catalogue->pourScope(
                $filiereId ? (int) $filiereId : null,
                $niveauId ? (int) $niveauId : null
            );

            $lignes = $cataloguesParScope[$cle]->map(fn (ESBTPPieceDossier $piece) => $this->ligne(
                $piece,
                $inscription,
                collect($depots->get($inscription->etudiant_id.':'.$piece->id, [])),
                collect($consommations->get($inscription->etudiant_id.':'.$piece->id, [])),
                $lignesCourantes->get($inscription->id.':'.$piece->id)
            ));

            $resultat[$inscription->id] = $this->synthese($lignes);
        }

        return $resultat;
    }

    /**
     * Le résumé d'un dossier, pour un bandeau ou un compteur.
     *
     * @param  Collection<int, array<string, mixed>>  $lignes
     * @return array<string, mixed>
     */
    public function synthese(Collection $lignes): array
    {
        $obligatoires = $lignes->filter(fn (array $l) => $l['piece']->is_obligatoire && ! $l['non_applicable']);
        $manquantes = $obligatoires->filter(fn (array $l) => ! $l['satisfaite']);
        $aRelire = $lignes->filter(fn (array $l) => $l['etat'] === EtatPieceDossier::DEPOSEE);

        return [
            'total' => $lignes->count(),
            'obligatoires' => $obligatoires->count(),
            'manquantes' => $manquantes->count(),
            'a_relire' => $aRelire->count(),
            'complet' => $manquantes->isEmpty(),
            'libelles_manquants' => $manquantes->map(fn (array $l) => $l['piece']->libelle)->values()->all(),
        ];
    }

    /**
     * Ce que l'école fait d'un dossier incomplet.
     *
     * Le défaut est « signaler » : bloquer par défaut arrêterait le guichet dès
     * la première rentrée chez une école qui n'a rien configuré, et se taire
     * rendrait la fonctionnalité invisible.
     */
    public function epuisement(): string
    {
        $valeur = strtolower(trim((string) SettingsHelper::get(
            CataloguePiecesDossier::REGLAGE_EPUISEMENT,
            self::EPUISEMENT_SIGNALER
        )));

        return in_array($valeur, [self::EPUISEMENT_BLOQUER, self::EPUISEMENT_SIGNALER, self::EPUISEMENT_SILENCE], true)
            ? $valeur
            : self::EPUISEMENT_SIGNALER;
    }

    /** Une inscription annulée rend-elle ce qu'elle avait consommé ? */
    public function restitueALAnnulation(): bool
    {
        $valeur = SettingsHelper::get(CataloguePiecesDossier::REGLAGE_RESTITUTION_ANNULATION, true);

        if ($valeur === null || $valeur === '') {
            return true;
        }

        if (is_bool($valeur)) {
            return $valeur;
        }

        return ! in_array(strtolower(trim((string) $valeur)), ['0', 'false', 'non', 'no', 'off'], true);
    }

    /**
     * Cocher une pièce vaut-il validation, ou faut-il qu'un autre la relise ?
     *
     * Le défaut est « un geste suffit ». L'inverse — cocher marque seulement
     * « remise » — n'a de sens que là où le guichet et le contrôle sont deux
     * personnes. Exiger deux gestes d'une seule ne rend rien plus sûr : le
     * dossier ne se solde jamais, et l'écran signale un manque que personne ne
     * viendra lever.
     */
    public function exigeUneRelecture(): bool
    {
        $valeur = SettingsHelper::get(CataloguePiecesDossier::REGLAGE_RELECTURE, false);

        if ($valeur === null || $valeur === '') {
            return false;
        }

        if (is_bool($valeur)) {
            return $valeur;
        }

        return in_array(strtolower(trim((string) $valeur)), ['1', 'true', 'oui', 'yes', 'on'], true);
    }

    /** L'état que prend un dépôt au moment où le guichet coche la case. */
    public function etatApresCoche(): EtatPieceDossier
    {
        return $this->exigeUneRelecture()
            ? EtatPieceDossier::DEPOSEE
            : EtatPieceDossier::VALIDEE;
    }

    /** Le catalogue est-il configuré ? Sinon aucun écran ne montre quoi que ce soit. */
    public function estConfigure(): bool
    {
        return $this->catalogue->estConfigure();
    }
}
