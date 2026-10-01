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

        $du = $inscription ? SoldeDeReinscription::du((int) $inscription->id) : 0.0;
        $paye = $inscription ? SoldeDeReinscription::paye((int) $inscription->id) : 0.0;
        $solde = round($du - $paye, 2);
        $tolerance = self::tolerance();

        $etat = match (true) {
            $inscription === null => null,
            $existante !== null => self::DEJA_INSCRIT,
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
