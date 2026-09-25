<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\ReinscriptionRefuseeException;
use App\Http\Controllers\Controller;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\ReeinscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
    public function __construct(private readonly ReeinscriptionService $reinscription)
    {
        $this->middleware('permission:reinscriptions.demandes.view')->only('index');
        $this->middleware('permission:reinscriptions.demandes.process')->only(['convertir', 'rejeter']);
    }

    public function index(Request $request): View
    {
        // Un statut inconnu rendrait une page vide sans rien expliquer : on
        // retombe sur « tous » plutot que de filtrer sur une valeur qui
        // n'existe pas.
        $statut = $request->string('statut')->toString();
        if (! in_array($statut, ESBTPReinscriptionDemande::STATUTS, true)) {
            $statut = '';
        }

        // Un dossier precis, depuis l'accueil du jour : la liste pagine par 25
        // sans recherche, la famille y serait a une page quelconque.
        $reference = app(\App\Services\Portail\ReferencePublique::class)->normaliser($request->string('reference')->toString());

        $demandes = ESBTPReinscriptionDemande::query()
            ->with(['etudiant:id,nom,prenoms,matricule,email,email_personnel,telephone', 'anneeUniversitaire:id,name', 'classeSouhaitee:id,name', 'traitePar:id,name'])
            ->when($statut !== '', fn ($q) => $q->where('statut', $statut))
            ->when($reference !== '', fn ($q) => $q->where('reference_publique', $reference))
            ->when($request->query('contact') === 'non_verifie', fn ($q) => $q->contactNonConfirme())
            ->orderByRaw("FIELD(statut, 'en_attente') DESC")
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        $compteurs = ESBTPReinscriptionDemande::query()
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return view('esbtp.reinscriptions.demandes.index', [
            'demandes' => $demandes,
            'compteurs' => $compteurs,
            'statutActif' => $statut,
            'referenceActive' => $reference === '' ? '' : app(\App\Services\Portail\ReferencePublique::class)->formater($reference),
            'classes' => ESBTPClasse::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Convertit une demande en reinscription reelle.
     *
     * La classe est choisie ICI, par la scolarite. Celle portee par la demande
     * n'est qu'un point de depart : l'etudiant ne decide pas de son affectation.
     */
    public function convertir(Request $request, ESBTPReinscriptionDemande $demande): RedirectResponse
    {
        $valide = $request->validate([
            // La fenetre ne propose que les classes actives ; sans ce filtre,
            // un envoi forge affecterait un etudiant a une classe archivee.
            'classe_id' => ['required', Rule::exists('esbtp_classes', 'id')->where('is_active', true)],
            'decision' => ['required', 'in:passage,redoublement,rattrapage'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ]);

        // Une demande vise l'annee qui etait courante au moment du depot. Si
        // l'ecole a bascule d'annee depuis, la convertir telle quelle
        // reinscrirait l'etudiant dans une annee revolue.
        if (! optional($demande->anneeUniversitaire)->is_current) {
            return back()->with('error', "Cette demande vise une année qui n'est plus l'année en cours. Rejetez-la et invitez l'étudiant à déposer de nouveau.");
        }

        // Du temps a pu passer entre le depot et cette conversion : l'ecole a
        // pu inscrire cet etudiant au guichet entre-temps. Convertir malgre
        // tout creerait une seconde inscription, donc un second jeu de frais
        // pour la meme famille.
        if (ESBTPInscription::aUneInscriptionVivantePour($demande->etudiant_id, $demande->annee_universitaire_id)) {
            return back()->with('error', "Cet étudiant a déjà une inscription pour cette année. Rejetez la demande plutôt que de la convertir.");
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
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        try {
            $inscription = $this->reinscription->effectuerReinscription(
                etudiantId: $demande->etudiant_id,
                nouvelleClasseId: $valide['classe_id'],
                decision: $valide['decision'],
                observations: $valide['observations'] ?? null,
                anneeUniversitaireId: $demande->annee_universitaire_id,
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

            return back()->with('error', 'Réinscription impossible : '.$e->getMessage());
        }

        ESBTPReinscriptionDemande::whereKey($demande->id)
            ->update(['inscription_id' => $inscription->id]);

        $this->oublierLeCompteur();

        Log::info('Demande de reinscription convertie', [
            'demande_id' => $demande->id,
            'etudiant_id' => $demande->etudiant_id,
            'classe_id' => $valide['classe_id'],
            'traite_par' => auth()->id(),
        ]);

        return back()->with('success', 'Réinscription effectuée. La demande est clôturée.');
    }

    public function rejeter(Request $request, ESBTPReinscriptionDemande $demande): RedirectResponse
    {
        $valide = $request->validate([
            // Un rejet sans motif est un rejet qu'on ne saura pas expliquer a
            // la famille qui appellera.
            'motif_rejet' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        // Meme reservation atomique que la conversion : deux agents ne doivent
        // pas pouvoir clore la meme demande avec deux motifs differents.
        $traite = ESBTPReinscriptionDemande::whereKey($demande->id)
            ->where('statut', ESBTPReinscriptionDemande::STATUT_EN_ATTENTE)
            ->update([
                'statut' => ESBTPReinscriptionDemande::STATUT_REJETEE,
                'motif_rejet' => $valide['motif_rejet'],
                'traite_par' => auth()->id(),
                'traite_at' => now(),
            ]);

        if ($traite === 0) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $this->oublierLeCompteur();

        return back()->with('success', 'Demande rejetée.');
    }

    /**
     * Le badge de la barre laterale compte les demandes en attente et vit en
     * cache une minute. Sans cet oubli, l'agent qui vient de traiter une
     * demande verrait le compteur inchange apres rechargement — et c'est
     * precisement la mise a jour de ce badge qui justifie le rechargement.
     */
    private function oublierLeCompteur(): void
    {
        Cache::forget(ESBTPReinscriptionDemande::CLE_CACHE_EN_ATTENTE);
    }
}
