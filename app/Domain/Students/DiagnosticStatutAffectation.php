<?php

namespace App\Domain\Students;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Services\ApplicableFraisResolver;
use Illuminate\Support\Facades\DB;

/**
 * Recense les inscriptions dont le statut d'affectation MESRS a pu etre ecrase.
 *
 * Jusqu'en septembre 2026, quatre chemins ecrivaient « affecte » en dur : sortie
 * de tronc commun, reinscription (a l'unite et en masse) et pre-inscription au
 * guichet. La ou la scolarite est subventionnee pour un affecte, l'etudiant
 * cessait de la devoir, et son dossier affichait « situation apuree ».
 *
 * Le correctif empeche de nouveaux cas ; il ne repare pas les anciens. Ce
 * service les retrouve, sans rien modifier.
 *
 * Deux signaux, tous deux surs :
 *
 *  1. RETOURNEMENT — l'etudiant etait non affecte (ou reaffecte) l'an dernier et
 *     se retrouve affecte cette annee. Le MESRS ne place personne en cours de
 *     scolarite : c'est un ecrasement, pas une promotion.
 *  2. SPECIALISATION — l'inscription de specialisation ne porte pas le statut de
 *     l'inscription de tronc commun dont elle est issue. Meme personne, meme
 *     annee : la divergence ne peut venir que du code.
 */
class DiagnosticStatutAffectation
{
    public const AFFECTE = 'affecté';

    public function __construct(private ApplicableFraisResolver $resolveur)
    {
    }

    /**
     * @param  int  $limite  Nombre de cas detailles listes ; le total, lui, porte sur tous.
     */
    public function pour(ESBTPAnneeUniversitaire $annee, int $limite = 200): array
    {
        $limite = max(1, $limite);

        $rapport = [
            'annee' => ['id' => $annee->id, 'nom' => $annee->name],
            'repartition' => $this->repartition($annee),
            'retournements' => $this->retournements($annee, $limite),
            'specialisations' => $this->specialisationsDivergentes($annee, $limite),
            'genere_le' => now()->toIso8601String(),
        ];

        $rapport['resume'] = [
            'cas_total' => $rapport['retournements']['total'] + $rapport['specialisations']['total'],
            'manque_a_gagner_fcfa' => round(
                $rapport['retournements']['manque_a_gagner_fcfa']
                + $rapport['specialisations']['manque_a_gagner_fcfa'],
                2
            ),
        ];

        return $rapport;
    }

    /** Combien d'inscriptions portent chaque statut, cette annee. */
    private function repartition(ESBTPAnneeUniversitaire $annee): array
    {
        return DB::table('esbtp_inscriptions')
            ->select('affectation_status', DB::raw('COUNT(*) as total'))
            ->where('annee_universitaire_id', $annee->id)
            ->whereNull('deleted_at')
            ->groupBy('affectation_status')
            ->pluck('total', 'affectation_status')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * Etudiants passes de « non affecte » ou « reaffecte » a « affecte » d'une
     * annee sur l'autre.
     */
    private function retournements(ESBTPAnneeUniversitaire $annee, int $limite): array
    {
        $cas = [];
        $manque = 0.0;

        $inscriptions = ESBTPInscription::query()
            // La classe est chargee ENTIERE, pas en colonnes choisies : le
            // bareme se resout sur sa filiere, son niveau et son systeme
            // academique. Une relation tronquee les rendrait nuls, aucune
            // configuration ne correspondrait, et l'ecart tomberait a zero
            // sans la moindre erreur.
            ->with(['etudiant:id,nom,prenoms,matricule', 'classe'])
            ->where('annee_universitaire_id', $annee->id)
            ->where('affectation_status', self::AFFECTE)
            ->get();

        foreach ($inscriptions as $inscription) {
            $precedente = ESBTPInscription::precedantAnnee($inscription->etudiant_id, $annee);
            if (! $precedente || ! $precedente->affectation_status) {
                continue;
            }
            if ($precedente->affectation_status === self::AFFECTE) {
                continue;
            }

            $ecart = $this->ecartDeTarif($inscription, $precedente->affectation_status);
            $manque += $ecart;

            $cas[] = [
                'inscription_id' => $inscription->id,
                'matricule' => $inscription->etudiant->matricule ?? null,
                'etudiant' => trim(($inscription->etudiant->nom ?? '').' '.($inscription->etudiant->prenoms ?? '')),
                'classe' => $inscription->classe->name ?? null,
                'statut_precedent' => $precedente->affectation_status,
                'statut_actuel' => self::AFFECTE,
                'annee_precedente_inscription_id' => $precedente->id,
                'manque_a_gagner_fcfa' => $ecart,
            ];
        }

        return $this->trierEtBorner($cas, $manque, $limite);
    }

    /**
     * Specialisations dont le statut ne correspond pas a celui du tronc commun
     * d'origine.
     */
    private function specialisationsDivergentes(ESBTPAnneeUniversitaire $annee, int $limite): array
    {
        $cas = [];
        $manque = 0.0;

        $inscriptions = ESBTPInscription::query()
            ->with(['etudiant:id,nom,prenoms,matricule', 'classe'])
            ->where('annee_universitaire_id', $annee->id)
            ->whereNotNull('inscription_origine_id')
            ->get();

        foreach ($inscriptions as $inscription) {
            $origine = ESBTPInscription::withTrashed()->find($inscription->inscription_origine_id);
            if (! $origine || ! $origine->affectation_status) {
                continue;
            }
            if ($origine->affectation_status === $inscription->affectation_status) {
                continue;
            }

            // Seul le sens « devenu affecte » coute de l'argent a l'ecole.
            $ecart = $inscription->affectation_status === self::AFFECTE
                ? $this->ecartDeTarif($inscription, $origine->affectation_status)
                : 0.0;
            $manque += $ecart;

            $cas[] = [
                'inscription_id' => $inscription->id,
                'matricule' => $inscription->etudiant->matricule ?? null,
                'etudiant' => trim(($inscription->etudiant->nom ?? '').' '.($inscription->etudiant->prenoms ?? '')),
                'classe' => $inscription->classe->name ?? null,
                'statut_origine' => $origine->affectation_status,
                'statut_actuel' => $inscription->affectation_status,
                'inscription_origine_id' => $origine->id,
                'manque_a_gagner_fcfa' => $ecart,
            ];
        }

        return $this->trierEtBorner($cas, $manque, $limite);
    }

    /**
     * Ce que l'ecole ne reclame pas : la difference entre ce que l'etudiant
     * devrait aux tarifs de son ancien statut, et ce qu'il doit aux tarifs
     * appliques aujourd'hui. Passe par le resolveur habituel — memes
     * categories, memes regles, meme configuration de filiere et de niveau.
     */
    private function ecartDeTarif(ESBTPInscription $inscription, string $statutAttendu): float
    {
        $somme = fn (string $statut) => (float) $this->resolveur
            ->resolveMandatoryFeesForInscription($inscription, $statut)
            ->sum('amount');

        $actuel = $inscription->affectation_status ?: self::AFFECTE;

        return round(max(0.0, $somme($statutAttendu) - $somme($actuel)), 2);
    }

    /** Les plus couteux d'abord ; le total porte sur tous les cas, la liste sur les premiers. */
    private function trierEtBorner(array $cas, float $manque, int $limite): array
    {
        usort($cas, fn ($a, $b) => $b['manque_a_gagner_fcfa'] <=> $a['manque_a_gagner_fcfa']);

        return [
            'total' => count($cas),
            'manque_a_gagner_fcfa' => round($manque, 2),
            'cas' => array_slice($cas, 0, $limite),
            'cas_tronques' => max(0, count($cas) - $limite),
        ];
    }
}
