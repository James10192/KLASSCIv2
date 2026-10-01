<?php

namespace App\Services\Reinscription;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Services\Inscriptions\NormalisationTypeInscription;

/**
 * Peut-on réinscrire cet élève ? UNE réponse, lue par la fiche de réinscription,
 * la page de finalisation, la garde du service et Nanan.
 *
 * Avant, quatre lectures se contredisaient :
 *  - la fiche bloquait dès 1 FCFA dû, la garde tolérait `reinscription.tolerance_solde` ;
 *  - la garde regardait la dernière inscription ACTIVE, la fiche celle « quittée » ;
 *  - la fiche ignorait une inscription de l'année courante d'un autre type que
 *    « réinscription », et comptait une réinscription annulée ;
 *  - Nanan disait « non bloquée » à un superadministrateur, parce qu'il mêlait
 *    l'état du dossier et le pouvoir du lecteur.
 *
 * L'ÉTAT est objectif (il ne dépend pas de qui regarde) ; la DÉROGATION, elle,
 * dépend du lecteur et se lit à part.
 */
final class EligibiliteReinscription
{
    public const DEJA_INSCRIT = 'deja_inscrit';
    public const SOLDEE = 'soldee';
    public const DANS_TOLERANCE = 'dans_tolerance';
    public const IMPAYE = 'impaye';
    /** Une inscription non annulée existe entre l'année quittée et l'année visée. */
    public const ANNEE_INTERMEDIAIRE = 'annee_intermediaire';

    public function __construct(private ClassesDeReinscription $classes)
    {
    }

    /**
     * @param  int|null  $anneeCibleId  l'année où l'élève doit entrer, quand la
     *   personne l'a choisie (page de finalisation, garde). Sinon : l'année
     *   courante. On ne devine pas « l'année suivante » d'après les années
     *   créées : une école peut les créer d'avance sans ouvrir de campagne.
     *
     * @return array{
     *   inscription: ?ESBTPInscription, annee_cible: ?ESBTPAnneeUniversitaire,
     *   annee_suivante: ?ESBTPAnneeUniversitaire, inscription_annee_cible: ?ESBTPInscription,
     *   du: float, paye: float, solde: float, tolerance: float,
     *   etat: ?string, autorisee: bool, peut_deroger: bool, peut_poursuivre: bool, peut_rejouer: bool
     * }
     */
    public function pour(int $etudiantId, $lecteur = null, ?int $anneeCibleId = null): array
    {
        $anneeCible = $anneeCibleId
            ? ESBTPAnneeUniversitaire::find($anneeCibleId)
            : ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // L'inscription qu'on quitte pour entrer dans l'année cible. Un élève
        // entré cette année n'en a pas d'antérieure : sa dernière inscription
        // est celle qu'il quittera (campagne ouverte avant la bascule), et elle
        // ne compte pas comme « déjà inscrit ».
        $inscription = ($anneeCible ? $this->classes->inscriptionQuitteeAvant($etudiantId, $anneeCible) : null)
            ?? $this->classes->inscriptionQuittee($etudiantId);
        // On ne se réinscrit jamais dans l'année qu'on quitte : un élève dont la
        // seule inscription est sur l'année courante vise la suivante (s'il n'y
        // en a pas encore, il n'y a pas d'année où le réinscrire).
        if (! $anneeCibleId && $inscription && $anneeCible && (int) $inscription->annee_universitaire_id === (int) $anneeCible->id) {
            $anneeCible = $this->anneeApres($anneeCible);
        }
        $existante = $anneeCible && $inscription
            ? $this->inscriptionDeLAnnee($etudiantId, (int) $anneeCible->id, (int) $inscription->id)
            : null;
        $intermediaire = $anneeCible && $inscription && $existante === null
            ? $this->inscriptionIntermediaire($etudiantId, $inscription, $anneeCible)
            : null;

        $du = $inscription ? SoldeDeReinscription::du((int) $inscription->id) : 0.0;
        $paye = $inscription ? SoldeDeReinscription::paye((int) $inscription->id) : 0.0;
        $solde = round($du - $paye, 2);
        $tolerance = self::tolerance();

        $etat = match (true) {
            $inscription === null => null,
            $existante !== null => self::DEJA_INSCRIT,
            // Viser plus loin partirait de l'année d'avant et laisserait ce
            // dossier à côté : aucun solde, aucune dérogation n'y change rien.
            $intermediaire !== null => self::ANNEE_INTERMEDIAIRE,
            $solde <= 0 => self::SOLDEE,
            $solde <= $tolerance => self::DANS_TOLERANCE,
            default => self::IMPAYE,
        };
        $autorisee = in_array($etat, [self::SOLDEE, self::DANS_TOLERANCE], true);
        $deroge = $lecteur !== null && method_exists($lecteur, 'isSuperAdmin') && $lecteur->isSuperAdmin();

        return [
            'inscription' => $inscription,
            'annee_cible' => $anneeCible,
            'annee_suivante' => $anneeCible ? $this->anneeApres($anneeCible) : null,
            'inscription_annee_cible' => $existante,
            'inscription_intermediaire' => $intermediaire,
            'message_intermediaire' => $intermediaire ? self::messageIntermediaire($intermediaire, $anneeCible) : null,
            'du' => $du,
            'paye' => $paye,
            'solde' => $solde,
            'tolerance' => $tolerance,
            'etat' => $etat,
            'autorisee' => $autorisee,
            'peut_deroger' => $etat === self::IMPAYE && $deroge,
            'peut_poursuivre' => $autorisee || ($etat === self::IMPAYE && $deroge),
            // Rejouer une réinscription déjà faite (correction de classe) :
            // effectuerReinscription la gère, la fiche l'offre à qui peut déroger.
            'peut_rejouer' => $etat === self::DEJA_INSCRIT && $deroge,
        ];
    }

    private function anneeApres(ESBTPAnneeUniversitaire $annee): ?ESBTPAnneeUniversitaire
    {
        return ESBTPAnneeUniversitaire::where('is_active', true)
            ->where('start_date', '>', $annee->start_date)
            ->orderBy('start_date')
            ->first();
    }

    /**
     * L'inscription posée sur une année entre celle qu'on quitte et celle
     * qu'on vise, hors annulées. Une inscription « terminée » compte aussi :
     * la laisser de côté ferait repartir la réinscription de l'année d'avant,
     * avec ses soldes, sans que personne ne l'ait décidé.
     */
    private function inscriptionIntermediaire(int $etudiantId, ESBTPInscription $quittee, ESBTPAnneeUniversitaire $anneeCible): ?ESBTPInscription
    {
        $anneeQuittee = $quittee->anneeUniversitaire;
        if (! $anneeQuittee) {
            return null;
        }

        return ESBTPInscription::query()
            ->select('esbtp_inscriptions.*')
            ->join('esbtp_annee_universitaires as annee', 'annee.id', '=', 'esbtp_inscriptions.annee_universitaire_id')
            ->where('esbtp_inscriptions.etudiant_id', $etudiantId)
            ->where('esbtp_inscriptions.id', '!=', $quittee->id)
            ->where('esbtp_inscriptions.status', '!=', 'annulée')
            ->where('annee.start_date', '>', $anneeQuittee->start_date)
            ->where('annee.start_date', '<', $anneeCible->start_date)
            ->orderBy('annee.start_date')
            ->with(['anneeUniversitaire', 'classe'])
            ->first();
    }

    /** Ce qu'il faut faire, selon l'état du dossier qui bloque. */
    public static function messageIntermediaire(ESBTPInscription $inscription, ?ESBTPAnneeUniversitaire $anneeCible): string
    {
        $annee = $inscription->anneeUniversitaire->name ?? 'l\'année précédente';
        $visee = $anneeCible->name ?? 'l\'année visée';

        return $inscription->status === 'terminée'
            ? "L'inscription de {$annee} est terminée sans être la dernière inscription suivie : réinscrire en {$visee} partirait de l'année d'avant. Corrigez d'abord l'inscription de {$annee}."
            : "Le dossier de {$annee} n'est pas finalisé : terminez-le ou annulez-le avant de préparer {$visee}.";
    }

    /** Reste dû jusqu'auquel une réinscription reste permise (réglage d'école, 0 par défaut). */
    public static function tolerance(): float
    {
        return max(0.0, (float) SettingsHelper::get('reinscription.tolerance_solde', 0));
    }

    /**
     * L'inscription de l'élève sur l'année cible, quel que soit son type, hors
     * annulées. Une réinscription en cours (dossier non finalisé) compte : on ne
     * relance pas une seconde réinscription par-dessus.
     */
    private function inscriptionDeLAnnee(int $etudiantId, int $anneeId, int $saufId): ?ESBTPInscription
    {
        return ESBTPInscription::query()
            ->where('id', '!=', $saufId)
            ->with(['classe.filiere', 'classe.niveau', 'anneeUniversitaire', 'reinscriptionValidatedBy'])
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', '!=', 'annulée')
            ->orderByRaw('CASE WHEN type_inscription = ? THEN 0 ELSE 1 END', [NormalisationTypeInscription::REINSCRIPTION])
            ->latest('id')
            ->first();
    }
}
