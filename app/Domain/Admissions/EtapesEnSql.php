<?php

namespace App\Domain\Admissions;

use App\Enums\StatutReservationRdv;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPCandidature;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * L'etape d'un dossier (EtapeDuDossier) en une expression SQL, pour compter et
 * filtrer une liste entiere sans charger une ligne de trop.
 *
 * Un seul CASE, dans l'ordre de EtapeDuDossier::deduire() : la premiere branche
 * qui s'applique gagne, donc un dossier n'est jamais compte deux fois. Les
 * valeurs injectees sont des constantes du code et des dates formatees par le
 * serveur, jamais une saisie.
 *
 * Seule l'etape « Inscrit » est bornee a l'annee de la campagne : sans borne,
 * elle compterait toutes les inscriptions depuis l'ouverture du portail. Les
 * dossiers ouverts, eux, restent visibles quelle que soit leur annee, comme
 * dans le compteur « a traiter » du menu.
 */
final class EtapesEnSql
{
    public function __construct(private readonly ?int $anneeCampagne = null)
    {
    }

    /** Restreint la requete aux dossiers d'une etape. */
    public function filtrer(Builder $q, EtapeDuDossier $etape, ?CarbonInterface $maintenant = null): Builder
    {
        $q->whereRaw('('.$this->expression($q, $maintenant).') = ?', [$etape->value]);

        if ($etape === EtapeDuDossier::Inscrit && $this->anneeCampagne !== null) {
            $q->where($q->getModel()->qualifyColumn('annee_universitaire_id'), $this->anneeCampagne);
        }

        return $q;
    }

    /**
     * Le nombre de dossiers de chaque etape, en une requete.
     *
     * @return array<string, int> indexe par EtapeDuDossier::value, toutes les etapes presentes
     */
    public function compter(Builder $q, ?CarbonInterface $maintenant = null): array
    {
        $modele = $q->getModel();
        $statut = $modele->qualifyColumn('statut');

        // Seules les lignes qui peuvent porter une etape sont lues : ni les
        // rejetees, ni les inscriptions des campagnes passees. Sans ce filtre,
        // chaque affichage parcourait tout l'historique.
        $q->where($statut, '<>', $modele::STATUT_REJETEE)
            ->when($this->anneeCampagne !== null, fn (Builder $w) => $w->where(fn (Builder $o) => $o
                ->where($statut, '<>', $modele::STATUT_CONVERTIE)
                ->orWhere($modele->qualifyColumn('annee_universitaire_id'), $this->anneeCampagne)));

        $lignes = $q->toBase()
            ->selectRaw('('.$this->expression($q, $maintenant).') AS etape, COUNT(*) AS n')
            ->groupBy('etape')
            ->get();

        $comptes = array_fill_keys(array_column(EtapeDuDossier::cases(), 'value'), 0);
        foreach ($lignes as $ligne) {
            if (array_key_exists((string) $ligne->etape, $comptes)) {
                $comptes[(string) $ligne->etape] += (int) $ligne->n;
            }
        }

        return $comptes;
    }

    /** Le CASE lui-meme, pour la table de la requete. Une valeur `rejete` sort de toutes les etapes. */
    public function expression(Builder $q, ?CarbonInterface $maintenant = null): string
    {
        $modele = $q->getModel();
        $t = $modele->getTable();
        $cle = $modele instanceof ESBTPCandidature ? 'candidature_id' : 'reinscription_demande_id';
        $maintenant ??= now();
        $jour = $maintenant->copy()->startOfDay()->format('Y-m-d H:i:s');
        $demain = $maintenant->copy()->addDay()->startOfDay()->format('Y-m-d H:i:s');
        $aujourdhui = $maintenant->format('Y-m-d');
        $heure = $maintenant->format('H:i:s');
        $honoree = StatutReservationRdv::Honoree->value;
        $confirmee = StatutReservationRdv::Confirmee->value;
        $aConfirmer = self::liste(StatutVerificationContact::valeursAConfirmer());
        $reservation = "SELECT 1 FROM esbtp_rdv_reservations r WHERE r.{$cle} = {$t}.id";
        $acceptee = $modele instanceof ESBTPCandidature ? " OR {$t}.statut = '".ESBTPCandidature::STATUT_ACCEPTEE."'" : '';

        return 'CASE'
            ." WHEN {$t}.statut = '".$modele::STATUT_REJETEE."' THEN 'rejete'"
            ." WHEN {$t}.statut = '".$modele::STATUT_CONVERTIE."' THEN '".EtapeDuDossier::Inscrit->value."'"
            ." WHEN EXISTS ({$reservation} AND r.statut = '{$honoree}' AND r.accueilli_at >= '{$jour}' AND r.accueilli_at < '{$demain}') THEN '".EtapeDuDossier::RecuAujourdhui->value."'"
            ." WHEN EXISTS ({$reservation} AND r.statut = '{$honoree}'){$acceptee} THEN '".EtapeDuDossier::AFinaliser->value."'"
            ." WHEN {$t}.verification_contact IN ({$aConfirmer}) THEN '".EtapeDuDossier::ContactAConfirmer->value."'"
            ." WHEN EXISTS ({$reservation} AND r.statut = '{$confirmee}' AND EXISTS (SELECT 1 FROM esbtp_rdv_creneaux c WHERE c.id = r.creneau_id AND (c.date > '{$aujourdhui}' OR (c.date = '{$aujourdhui}' AND c.heure_fin > '{$heure}')))) THEN '".EtapeDuDossier::RdvPlanifie->value."'"
            ." ELSE '".EtapeDuDossier::AExaminer->value."' END";
    }

    /** @param  list<string>  $valeurs */
    private static function liste(array $valeurs): string
    {
        return implode(', ', array_map(fn (string $v) => "'".$v."'", $valeurs));
    }
}
