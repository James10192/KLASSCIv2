<?php

namespace App\Http\Controllers\Comptabilite;

use App\Http\Controllers\Concerns\VerrouilleLesPeriodesComptables;
use App\Http\Controllers\Controller;
use App\Models\ESBTPPaiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Corrige le mode de reglement d'un versement deja valide.
 *
 * Un versement valide ne se reecrit plus depuis l'ecran de saisie, et c'est la
 * bonne regle : une ecriture comptable ne se retouche pas.
 *
 * Le mode de reglement, lui, n'est pas une ecriture : c'est une etiquette. Il
 * ne change ni le montant, ni le frais, ni l'etudiant, ni ce que l'ecole doit
 * ou a recu. Un cheque coche a la place d'especes au guichet n'avait pourtant
 * d'autre issue qu'une session de reconciliation complete — disproportionne
 * pour un champ, donc rarement fait, donc un journal de caisse qui ment.
 *
 * Ce qui reste hors de portee est deliberé : le montant, le frais et la date
 * changent ce que l'etudiant a paye. Ceux-la relevent bien de la
 * reconciliation, avec son comptage et sa double validation.
 */
class ModeReglementController extends Controller
{
    use VerrouilleLesPeriodesComptables;

    /**
     * Les modes acceptes, ecrits EXACTEMENT comme l'ecran de saisie les
     * enregistre.
     *
     * La colonne stocke le libelle, pas un code : le formulaire d'encaissement
     * pose `Especes`, `Cheque`, `Mobile Money`… La correction doit donc ecrire
     * la meme chaine, au caractere pres. « especes » en minuscules serait une
     * TROISIEME valeur pour le journal de caisse, qui regroupe par ce champ :
     * la ligne corrigee disparaitrait de la colonne Especes pour en fonder une
     * autre, que personne ne rapproche ensuite.
     *
     * Liste fermee, donc, et alignee sur `$allModeOptions` de la vue
     * `esbtp/paiements/create.blade.php`. Un test la fige.
     */
    public const MODES = [
        'Espèces' => 'Espèces',
        'Chèque' => 'Chèque',
        'Virement' => 'Virement',
        'Mobile Money' => 'Mobile Money',
        'Orange Money' => 'Orange Money',
        'MTN Money' => 'MTN Money',
        'Moov Money' => 'Moov Money',
        'Wave' => 'Wave',
        'Carte bancaire' => 'Carte bancaire',
    ];

    public function __invoke(Request $request, ESBTPPaiement $paiement)
    {
        abort_unless($request->user()?->can('paiements.correct_mode'), 403);

        $valide = $request->validate([
            'mode_paiement' => ['required', 'string', 'in:'.implode(',', array_keys(self::MODES))],
            // Dix caracteres : « erreur » ou « ok » n'expliquent rien a qui
            // relira le journal dans six mois.
            'motif' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        if ($paiement->isAvoir()) {
            return $this->repondre($request, 'error', "Le mode d'un avoir suit celui du versement qu'il annule : il ne se corrige pas séparément.");
        }

        if ($paiement->status !== 'validé') {
            return $this->repondre($request, 'error', "Ce versement n'est pas validé : corrigez-le depuis l'écran de modification habituel.");
        }

        // Une fois la caisse rapprochee, le mode a servi a construire le
        // comptage : le changer apres coup ferait mentir un rapprochement deja
        // signe. La reconciliation, elle, sait rouvrir sa session.
        if ($paiement->reconciliation_locked_at) {
            return $this->repondre($request, 'error', 'Ce versement a été rapproché en caisse. Sa correction passe par une session de réconciliation.');
        }

        if ($blocage = $this->assertPeriodNotLocked($paiement)) {
            return $this->repondre($request, 'error', $blocage['message']);
        }

        $ancien = (string) $paiement->mode_paiement;

        if ($ancien === $valide['mode_paiement']) {
            return $this->repondre($request, 'info', 'Le mode de règlement est déjà celui-là : rien n\'a été modifié.', $ancien);
        }

        // `mode_paiement` figure dans $auditInclude du modele : l'ancienne et la
        // nouvelle valeur, l'auteur, l'heure et l'adresse sont inscrits sans
        // qu'on ait a les journaliser ici. Le motif, lui, n'est pas une colonne
        // du versement — d'ou la trace explicite ci-dessous.
        $paiement->update(['mode_paiement' => $valide['mode_paiement']]);

        Log::warning('Mode de règlement corrigé sur un versement validé', [
            'paiement_id' => $paiement->id,
            'numero_recu' => $paiement->numero_recu,
            'ancien_mode' => $ancien,
            'nouveau_mode' => $valide['mode_paiement'],
            'motif' => $valide['motif'],
            'par' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->repondre($request, 'success', sprintf(
            'Mode de règlement corrigé : « %s » devient « %s ». La correction est inscrite au journal d\'audit.',
            self::MODES[$ancien] ?? $ancien,
            self::MODES[$valide['mode_paiement']]
        ), $valide['mode_paiement']);
    }

    /**
     * Le formulaire de bureau attend un retour a la page avec un message flash ;
     * la fiche mobile corrige le mode en fetch JSON sans recharger. Meme
     * verdict, deux enveloppes : un refus vaut 422 en JSON, jamais un 200 qui
     * dirait « corrige » a un ecran qui ne relit pas le flash.
     */
    private function repondre(Request $request, string $niveau, string $message, ?string $mode = null)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => $niveau !== 'error',
                'message' => $message,
                'mode_paiement' => $mode,
            ], $niveau === 'error' ? 422 : 200);
        }

        return back()->with($niveau, $message);
    }
}
