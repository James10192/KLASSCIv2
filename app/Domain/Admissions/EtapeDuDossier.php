<?php

namespace App\Domain\Admissions;

use App\Enums\StatutReservationRdv;
use App\Enums\StatutVerificationContact;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Ou en est un dossier d'admission, dit en une etape que l'accueil comprend.
 *
 * Rien n'est stocke : l'etape se DEDUIT des statuts qui existent deja (statut
 * du dossier, rendez-vous, verification du contact). Une seule etape par
 * dossier, la premiere qui s'applique dans cet ordre :
 *
 * 1. Inscrit             : le dossier est converti (inscription faite) ;
 * 2. Recu aujourd'hui    : la famille a ete cochee reçue au guichet aujourd'hui ;
 * 3. A finaliser         : candidature acceptee, ou famille reçue un autre jour,
 *                          sans inscription ;
 * 4. Contact a confirmer : le contact n'est pas prouve (pas de convocation
 *                          automatique tant que l'ecole ne l'a pas confirme) ;
 * 5. RDV planifie        : un rendez-vous confirme dont le creneau n'est pas termine ;
 * 6. A examiner          : tout autre dossier ouvert, y compris une famille
 *                          non venue a un rendez-vous passe.
 *
 * Un dossier rejete n'a pas d'etape : il sort du parcours.
 *
 * EtapesEnSql dit exactement la meme chose en SQL, pour compter et filtrer une
 * liste entiere ; les deux sont verifies ensemble par les tests.
 */
enum EtapeDuDossier: string
{
    case AExaminer = 'a_examiner';
    case ContactAConfirmer = 'contact_a_confirmer';
    case RdvPlanifie = 'rdv_planifie';
    case RecuAujourdhui = 'recu_aujourdhui';
    case AFinaliser = 'a_finaliser';
    case Inscrit = 'inscrit';

    public function libelle(): string
    {
        return match ($this) {
            self::AExaminer => 'À examiner',
            self::ContactAConfirmer => 'Contact à confirmer',
            self::RdvPlanifie => 'RDV planifié',
            self::RecuAujourdhui => 'Reçu aujourd\'hui',
            self::AFinaliser => 'À finaliser',
            self::Inscrit => 'Inscrit',
        };
    }

    /** Ce que l'etape attend de l'accueil, en une phrase courte. */
    public function consigne(): string
    {
        return match ($this) {
            self::AExaminer => 'décision ou rendez-vous à proposer',
            self::ContactAConfirmer => 'appeler la famille',
            self::RdvPlanifie => 'famille convoquée',
            self::RecuAujourdhui => 'au guichet en ce moment',
            self::AFinaliser => 'inscription à terminer',
            self::Inscrit => 'campagne en cours',
        };
    }

    /**
     * Suffixe de classe CSS. L'orange signale une action en attente, le vert un
     * resultat acquis : les deux portent un sens, le reste est bleu ou neutre.
     */
    public function ton(): string
    {
        return match ($this) {
            self::AExaminer => 'neutre',
            self::ContactAConfirmer => 'alerte',
            self::RdvPlanifie => 'planifie',
            self::RecuAujourdhui, self::AFinaliser => 'recu',
            self::Inscrit => 'succes',
        };
    }

    /**
     * @param  Model  $dossier  ESBTPCandidature ou ESBTPReinscriptionDemande
     * @param  ESBTPRdvReservation|null  $rdv  la reservation occupante la plus recente, creneau charge
     */
    public static function deduire(Model $dossier, ?ESBTPRdvReservation $rdv, ?CarbonInterface $maintenant = null): ?self
    {
        $maintenant ??= now();
        $statut = (string) $dossier->statut;

        if ($statut === $dossier::STATUT_REJETEE) {
            return null;
        }
        if ($statut === $dossier::STATUT_CONVERTIE) {
            return self::Inscrit;
        }

        $recue = $rdv?->statut === StatutReservationRdv::Honoree;

        return match (true) {
            $recue && $rdv->accueilli_at?->isSameDay($maintenant) === true => self::RecuAujourdhui,
            $recue, $statut === ESBTPCandidature::STATUT_ACCEPTEE => self::AFinaliser,
            in_array($dossier->verification_contact ?? null, StatutVerificationContact::valeursAConfirmer(), true) => self::ContactAConfirmer,
            $rdv?->statut === StatutReservationRdv::Confirmee
                && $rdv->creneau !== null
                && self::finDuCreneau($rdv) > $maintenant->format('Y-m-d H:i:s') => self::RdvPlanifie,
            default => self::AExaminer,
        };
    }

    /** « Y-m-d H:i:s » de la fin du creneau, comparable a la meme chaine en SQL. */
    private static function finDuCreneau(ESBTPRdvReservation $rdv): string
    {
        return $rdv->creneau->date->toDateString().' '.$rdv->creneau->heureFinHi().':00';
    }

    public static function depuis(?string $valeur): ?self
    {
        return $valeur === null ? null : self::tryFrom($valeur);
    }
}
