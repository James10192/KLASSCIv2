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
     * @return array{
     *   inscription: ?ESBTPInscription, annee_cible: ?ESBTPAnneeUniversitaire,
     *   inscription_annee_cible: ?ESBTPInscription,
     *   du: float, paye: float, solde: float, tolerance: float,
     *   etat: ?string, autorisee: bool, peut_deroger: bool, peut_poursuivre: bool
     * }
     */
    public function pour(int $etudiantId, $lecteur = null): array
    {
        $inscription = $this->classes->inscriptionQuittee($etudiantId);
        $anneeCible = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $existante = $anneeCible ? $this->inscriptionDeLAnnee($etudiantId, (int) $anneeCible->id) : null;

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
        $peutDeroger = $etat === self::IMPAYE && $lecteur !== null
            && method_exists($lecteur, 'isSuperAdmin') && $lecteur->isSuperAdmin();

        return [
            'inscription' => $inscription,
            'annee_cible' => $anneeCible,
            'inscription_annee_cible' => $existante,
            'du' => $du,
            'paye' => $paye,
            'solde' => $solde,
            'tolerance' => $tolerance,
            'etat' => $etat,
            'autorisee' => $autorisee,
            'peut_deroger' => $peutDeroger,
            'peut_poursuivre' => $autorisee || $peutDeroger,
        ];
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
    private function inscriptionDeLAnnee(int $etudiantId, int $anneeId): ?ESBTPInscription
    {
        return ESBTPInscription::query()
            ->with(['classe.filiere', 'classe.niveau', 'anneeUniversitaire', 'reinscriptionValidatedBy'])
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('status', '!=', 'annulée')
            ->orderByRaw('CASE WHEN type_inscription = ? THEN 0 ELSE 1 END', [NormalisationTypeInscription::REINSCRIPTION])
            ->latest('id')
            ->first();
    }
}
