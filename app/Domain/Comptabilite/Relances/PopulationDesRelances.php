<?php

namespace App\Domain\Comptabilite\Relances;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPRelance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Qui l'ecole relance : la seule definition, partagee par la liste des
 * relances, ses compteurs, ses exports et les envois (planification
 * automatique, planification manuelle, et tout envoi d'une relance
 * enregistree : NotificationService::envoyerRelance()).
 *
 * Une inscription est relancable quand son parcours d'inscription a abouti
 * (workflow_step = etudiant_cree). Reste la question de son STATUT : un eleve
 * parti (inscription annulee, terminee, en attente...) garde un solde, mais
 * l'ecole ne veut generalement pas le relancer. C'est une politique
 * d'etablissement, donc un reglage d'instance :
 *
 *  - decoche (defaut) : seules les inscriptions au statut « active » ;
 *  - coche : toutes, quel que soit le statut.
 *
 * Le reglage est relu en base a chaque appel, sans cache, comme les autres
 * reglages du groupe « relances » (ecrits par updateOrInsert, sans passer par
 * Setting::set) : un changement se voit a la requete suivante.
 */
final class PopulationDesRelances
{
    public const CLE_REGLAGE = 'relances.inclure_inscriptions_inactives';

    public const ETAPE_ABOUTIE = 'etudiant_cree';

    public const STATUT_ACTIF = 'active';

    public static function inclutLesInactives(): bool
    {
        $valeur = DB::table('settings')->where('key', self::CLE_REGLAGE)->value('value');

        return filter_var($valeur, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Restreint une requete sur esbtp_inscriptions a la population relancable.
     *
     * @param  bool|null  $inclureInactives  null = lire le reglage de l'ecole
     */
    public static function restreindre(Builder $requete, ?bool $inclureInactives = null): Builder
    {
        $inclureInactives ??= self::inclutLesInactives();
        $table = $requete->getModel()->getTable();

        return $requete
            ->where("$table.workflow_step", self::ETAPE_ABOUTIE)
            ->when(! $inclureInactives, fn (Builder $q) => $q->where("$table.status", self::STATUT_ACTIF));
    }

    /**
     * La meme regle que restreindre(), sur une inscription deja chargee : pour
     * les calculs qui parcourent deja toutes les inscriptions (tableau de bord).
     * Les deux expressions vivent ici, cote a cote, et le test les confronte.
     */
    public static function admet(ESBTPInscription $inscription, ?bool $inclureInactives = null): bool
    {
        $inclureInactives ??= self::inclutLesInactives();

        return $inscription->workflow_step === self::ETAPE_ABOUTIE
            && ($inclureInactives || $inscription->status === self::STATUT_ACTIF);
    }

    /**
     * Une relance enregistree vise-t-elle encore un debiteur relancable ?
     *
     * Par son inscription quand elle la porte ; sinon par l'eleve, sur l'annee
     * universitaire courante (la relance n'a pas d'annee). Sans inscription
     * retrouvee, la reponse est non : on n'envoie pas une relance dont on ne
     * sait plus a quelle dette elle se rapporte.
     */
    public static function couvreLaRelance(ESBTPRelance $relance): bool
    {
        $requete = ESBTPInscription::query();

        if ($relance->inscription_id) {
            $requete->whereKey($relance->inscription_id);
        } else {
            $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');
            if (! $anneeCourante || ! $relance->etudiant_id) {
                return false;
            }
            $requete->where('etudiant_id', $relance->etudiant_id)->where('annee_universitaire_id', $anneeCourante);
        }

        return self::restreindre($requete)->exists();
    }
}
