<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\ReinscriptionRefuseeException;
use App\Domain\Admissions\FileDesDemandes;
use App\Domain\Admissions\PreparationDInscription;
use App\Domain\Inscriptions\QuestionRedoublant;
use App\Domain\Inscriptions\StatutRedoublant;
use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\Reinscription\ClassesDeReinscription;
use App\Services\ReeinscriptionService;
use App\Services\RendezVous\RendezVousApresInscription;
use App\Services\RendezVous\ReservateurRdv;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Corbeille des demandes de reinscription deposees en ligne.
 *
 * C'est la piece qui evite que le portail public ne soit qu'un formulaire de
 * contact deguise : la scolarite convertit une demande en vraie reinscription
 * d'un clic, sans aucune re-saisie, et la conversion passe par le flux
 * canonique qui gere deja les frais, les reliquats et la cloture de
 * l'inscription precedente.
 */
class ESBTPReinscriptionDemandeController extends Controller
{
    use \App\Http\Controllers\Concerns\RepondEnJsonOuRedirige;

    public function __construct(private readonly ReeinscriptionService $reinscription)
    {
        $this->middleware('permission:reinscriptions.demandes.process')->only(['convertir', 'rejeter', 'proposition']);
    }

    /**
     * Ce que la fenetre « Réinscrire » pre-remplit : la decision que les
     * resultats de l'annee quittee proposent (la meme que la fiche de
     * reinscription), et le statut d'affectation de l'etudiant, qui le suit
     * d'une annee sur l'autre. L'agent reste libre de changer l'un et l'autre.
     */
    public function proposition(Request $request, ESBTPReinscriptionDemande $demande, ClassesDeReinscription $classes): JsonResponse
    {
        $cible = ESBTPAnneeUniversitaire::find($request->integer('annee') ?: $demande->annee_universitaire_id);
        $quittee = ($cible ? $classes->inscriptionQuitteeAvant($demande->etudiant_id, $cible) : null)
            ?? $classes->inscriptionQuittee($demande->etudiant_id);

        if ($quittee === null) {
            return response()->json(['decision' => null, 'affectation_status' => ESBTPInscription::DEFAULT_AFFECTATION_STATUS, 'moyenne' => null, 'annee_quittee' => null]);
        }

        $quittee->loadMissing('anneeUniversitaire', 'classe');
        try {
            $analyse = $this->reinscription->analyserSituationEtudiantParInscription($quittee, (string) $quittee->anneeUniversitaire?->name);
        } catch (\Throwable $e) {
            // Sans resultats lisibles, pas de decision proposee : l'agent choisit.
            Log::warning('Proposition de reinscription indisponible', ['demande_id' => $demande->id, 'erreur' => $e->getMessage()]);
            $analyse = [];
        }
        $decision = $analyse['decision'] ?? null;
        $statut = ESBTPEcheancierRule::normalizeStatus($quittee->affectation_status ?: ESBTPInscription::DEFAULT_AFFECTATION_STATUS);

        return response()->json([
            'decision' => array_key_exists((string) $decision, ESBTPReinscriptionDemande::DECISIONS) ? $decision : null,
            'moyenne' => isset($analyse['moyenne_generale']) ? round((float) $analyse['moyenne_generale'], 2) : null,
            'affectation_status' => $statut === ESBTPEcheancierRule::STATUS_ALL ? ESBTPInscription::DEFAULT_AFFECTATION_STATUS : $statut,
            'annee_quittee' => (string) $quittee->anneeUniversitaire?->name,
            'classe_quittee' => (string) $quittee->classe?->name,
            // Pour proposer « Redoublant ? » d'apres la classe choisie.
            'niveau_avant' => $cible ? (StatutRedoublant::niveauxDeLAnneePrecedente($demande->etudiant_id, [$cible])[(string) $cible->id] ?? null) : null,
        ]);
    }

    /**
     * Convertit une demande en reinscription reelle.
     *
     * La classe est choisie ICI, par la scolarite. Celle portee par la demande
     * n'est qu'un point de depart : l'etudiant ne decide pas de son affectation.
     */
    public function convertir(Request $request, ESBTPReinscriptionDemande $demande): RedirectResponse|JsonResponse
    {
        $valide = $request->validate([
            // La fenetre ne propose que les classes actives ; sans ce filtre,
            // un envoi forge affecterait un etudiant a une classe archivee.
            'classe_id' => ['required', Rule::exists('esbtp_classes', 'id')->where('is_active', true)],
            'decision' => ['required', Rule::in(array_keys(ESBTPReinscriptionDemande::DECISIONS))],
            'observations' => ['nullable', 'string', 'max:1000'],
            // Seulement les annees que la fenetre propose (precedente, courante,
            // suivantes, et celle de la demande) : un envoi forge n'en vise pas d'autre.
            'annee_universitaire_id' => ['nullable', 'integer', Rule::in(array_column(
                app(PreparationDInscription::class)->annees($demande->annee_universitaire_id), 'id'
            ))],
            'annee_echue_confirmee' => ['nullable', 'boolean'],
            'affectation_status' => ['nullable', Rule::in(array_keys(ESBTPEcheancierRule::STATUTS_INSCRIPTION))],
        ] + QuestionRedoublant::regles());

        $annee = $this->anneeDeConversion($valide, $demande);
        if (is_string($annee)) {
            return $this->repondre($request, false, $annee);
        }

        // Avant la reservation : un motif manquant ne doit rien bloquer.
        $redoublant = QuestionRedoublant::reponse($request);
        QuestionRedoublant::exigerLeMotif(
            $redoublant,
            QuestionRedoublant::proposition($demande->etudiant_id, ESBTPClasse::findOrFail($valide['classe_id']), $annee),
            $valide['redoublant_motif'] ?? null,
        );

        // Du temps a pu passer entre le depot et cette conversion : l'ecole a
        // pu inscrire cet etudiant au guichet entre-temps. Convertir malgre
        // tout creerait une seconde inscription, donc un second jeu de frais
        // pour la meme famille.
        if (ESBTPInscription::aUneInscriptionVivantePour($demande->etudiant_id, $annee->id)) {
            return $this->repondre($request, false, "Cet étudiant a déjà une inscription pour cette année. Rejetez la demande plutôt que de la convertir.");
        }


        // Reservation atomique AVANT tout travail. Lire le statut puis agir
        // laisserait passer deux agents qui cliquent « Convertir » sur la meme
        // demande a une seconde d'intervalle — cas banal en periode de rush,
        // la page ne se rafraichissant pas — et produirait deux inscriptions.
        // Ici, un seul des deux voit une ligne affectee.
        $reserve = ESBTPReinscriptionDemande::whereKey($demande->id)
            ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
            // `classe_souhaitee_id` n'est PAS reecrite : elle porte le point de
            // depart de l'etudiant. La classe reellement affectee vit sur
            // l'inscription creee, liee par `inscription_id`.
            ->update([
                'statut' => ESBTPReinscriptionDemande::STATUT_CONVERTIE,
                'traite_par' => auth()->id(),
                'traite_at' => now(),
            ]);

        if ($reserve === 0) {
            return $this->repondre($request, false, 'Cette demande a déjà été traitée.');
        }

        try {
            $inscription = $this->reinscription->effectuerReinscription(
                etudiantId: $demande->etudiant_id,
                nouvelleClasseId: $valide['classe_id'],
                decision: $valide['decision'],
                observations: $valide['observations'] ?? null,
                // Sans choix, le service reprend le statut de l'inscription quittee.
                affectationStatus: $valide['affectation_status'] ?? null,
                anneeUniversitaireId: $annee->id,
                redoublant: $redoublant,
                redoublantMotif: $valide['redoublant_motif'] ?? null,
            );
        } catch (\Throwable $e) {
            // La reservation n'a pas ete honoree : on la rend, sinon la demande
            // resterait marquee « convertie » sans inscription derriere, et
            // personne ne pourrait plus la reprendre. La liberation couvre
            // TOUT ce qui echoue, y compris une erreur de typage — d'ou
            // \Throwable ici, alors que seul le refus metier est raconte a
            // l'agent juste en dessous.
            ESBTPReinscriptionDemande::whereKey($demande->id)->update([
                'statut' => ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
                'traite_par' => null,
                'traite_at' => null,
            ]);

            // Le flux canonique refuse pour des raisons metier legitimes, le
            // solde non regle en premier lieu : celles-la se racontent. Une
            // panne technique, elle, remonte au gestionnaire d'erreurs — la
            // presenter comme un refus metier afficherait un message SQL a un
            // agent de scolarite et masquerait l'incident.
            if (! $e instanceof ReinscriptionRefuseeException) {
                throw $e;
            }

            Log::warning('Conversion de demande de reinscription refusee', [
                'demande_id' => $demande->id,
                'motif' => $e->getMessage(),
            ]);

            return $this->repondre($request, false, 'Réinscription impossible : '.$e->getMessage());
        }

        ESBTPReinscriptionDemande::whereKey($demande->id)
            ->update(['inscription_id' => $inscription->id]);

        app(RendezVousApresInscription::class)->clore($demande, auth()->id());


        Log::info('Demande de reinscription convertie', [
            'demande_id' => $demande->id,
            'etudiant_id' => $demande->etudiant_id,
            'classe_id' => $valide['classe_id'],
            'annee_universitaire_id' => $annee->id,
            'annee_echue_confirmee' => ! empty($valide['annee_echue_confirmee']),
            'affectation_status' => $valide['affectation_status'] ?? null,
            'traite_par' => auth()->id(),
        ]);

        // Le statut change par une requete directe (garde contre le double
        // clic), qui ne declenche pas les evenements du modele : le compteur
        // de la file s'oublie donc ici.
        FileDesDemandes::oublierLesCompteurs();

        return $this->repondre($request, true, 'Réinscription effectuée. La demande est clôturée.');
    }

    /**
     * L'annee sur laquelle reinscrire. Sans choix explicite, celle de la
     * demande — a condition qu'elle soit encore l'annee en cours : une demande
     * deposee l'an dernier ne doit pas reinscrire sur une annee close sans
     * qu'on l'ait choisie. Une annee terminee choisie se confirme.
     *
     * Rend le message a afficher quand la conversion ne peut pas partir.
     */
    private function anneeDeConversion(array $valide, ESBTPReinscriptionDemande $demande): ESBTPAnneeUniversitaire|string
    {
        $explicite = isset($valide['annee_universitaire_id']);
        $annee = $explicite
            ? ESBTPAnneeUniversitaire::find($valide['annee_universitaire_id'])
            : $demande->anneeUniversitaire;

        if ($annee === null) {
            return "Choisissez l'année de la réinscription.";
        }
        if (! $explicite && ! $annee->is_current) {
            return "Cette demande vise une année qui n'est plus l'année en cours. Choisissez l'année de la réinscription.";
        }
        if ($explicite && $annee->estTerminee() && empty($valide['annee_echue_confirmee'])) {
            return "L'année {$annee->name} est terminée : confirmez la réinscription sur cette année.";
        }

        return $annee;
    }

    public function rejeter(Request $request, ESBTPReinscriptionDemande $demande, ReservateurRdv $reservateur): RedirectResponse|JsonResponse
    {
        $valide = $request->validate([
            // Un rejet sans motif est un rejet qu'on ne saura pas expliquer a
            // la famille qui appellera.
            'motif_rejet' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        // Meme reservation atomique que la conversion : deux agents ne doivent
        // pas pouvoir clore la meme demande avec deux motifs differents.
        // Le creneau se libere dans la meme transaction que le rejet : sinon la
        // famille pourrait deplacer ou reprendre sa place entre les deux.
        [$traite, $liberee] = DB::transaction(function () use ($demande, $valide, $reservateur) {
            $traite = ESBTPReinscriptionDemande::whereKey($demande->id)
                ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
                ->update([
                    'statut' => ESBTPReinscriptionDemande::STATUT_REJETEE,
                    'motif_rejet' => $valide['motif_rejet'],
                    'traite_par' => auth()->id(),
                    'traite_at' => now(),
                ]);

            return [$traite, $traite > 0 ? $reservateur->liberer($demande) : null];
        });

        if ($traite === 0) {
            return $this->repondre($request, false, 'Cette demande a déjà été traitée.');
        }
        // Requete directe, sans evenement de modele : voir convertir().
        FileDesDemandes::oublierLesCompteurs();


        return $this->repondre($request, true, 'Demande rejetée.'.ReservateurRdv::phraseLiberation($liberee));
    }
}
