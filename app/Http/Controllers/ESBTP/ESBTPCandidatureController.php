<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Corbeille des candidatures deposees en ligne par les nouveaux etudiants.
 *
 * Elle existe pour la meme raison que la corbeille des reinscriptions : sans
 * elle, le canal public ne serait qu'un formulaire de contact.
 *
 * Une difference assumee avec la reinscription : on ne convertit PAS d'un clic.
 * Reinscrire, c'est reconduire un dossier qui existe ; admettre un nouveau,
 * c'est une decision — verifier les pieces, juger le dossier, affecter une
 * classe. La corbeille prepare donc le terrain et laisse l'ecole decider.
 * Convertir automatiquement creerait des etudiants sur la foi d'un formulaire
 * public.
 *
 * « Preparer le terrain » veut dire deux choses precises, et pas une de plus.
 * Une candidature acceptee porte un lien vers le formulaire d'inscription qui
 * reprend l'identite, la residence et le statut d'affectation deja saisis
 * (ESBTPInscriptionController::create). Et le tuteur declare reste affiche
 * ici, parce que c'est lui que la scolarite appelle. Le formulaire d'inscription
 * le rappelle aussi, dans son bandeau, avec un bouton « Reprendre ce tuteur »
 * qui recopie ses quatre champs — nom, telephone, lien et profession — dans le
 * bloc « Parent / Tuteur ».
 *
 * Sur demande, et non tout seul : ce bloc rend nom, prenoms, telephone et
 * relation obligatoires des qu'un seul porte une valeur, si bien qu'un
 * pre-remplissage partiel d'office rendrait des champs requis sans les remplir,
 * sous une section annoncee « optionnelle ».
 *
 * Le NOM part entier, tel que la candidature le collecte : un champ unique
 * « Nom et prenoms » la ou ce formulaire les separe. Couper au premier espace
 * se trompe une fois sur deux sur les noms composes ivoiriens, donc l'agent
 * repartit — le bouton l'y invite, met le curseur sur « Prenoms », et la
 * validation refuse l'envoi tant que ce champ reste vide.
 */
class ESBTPCandidatureController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inscriptions.candidatures.view')->only('index');
        $this->middleware('permission:inscriptions.candidatures.process')->only(['accepter', 'rejeter']);
    }

    public function index(Request $request): View
    {
        $statut = $request->string('statut')->toString();
        $statutsConnus = [
            ESBTPCandidature::STATUT_EN_ATTENTE,
            ESBTPCandidature::STATUT_ACCEPTEE,
            ESBTPCandidature::STATUT_REJETEE,
            ESBTPCandidature::STATUT_CONVERTIE,
        ];

        // Un dossier precis, depuis l'accueil du jour : la liste pagine par 25
        // sans recherche, la famille y serait a une page quelconque.
        $reference = app(\App\Services\Portail\ReferencePublique::class)->normaliser($request->string('reference')->toString());

        $candidatures = ESBTPCandidature::query()
            ->with(['anneeUniversitaire:id,name', 'filiere:id,name', 'niveau:id,name', 'traitePar:id,name'])
            ->when(in_array($statut, $statutsConnus, true), fn ($q) => $q->where('statut', $statut))
            ->when($reference !== '', fn ($q) => $q->where('reference_publique', $reference))
            ->orderByRaw("FIELD(statut, 'en_attente') DESC")
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('esbtp.inscriptions.candidatures.index', [
            'candidatures' => $candidatures,
            'compteurs' => ESBTPCandidature::query()
                ->selectRaw('statut, COUNT(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut'),
            'statutActif' => in_array($statut, $statutsConnus, true) ? $statut : '',
            'referenceActive' => $reference === '' ? '' : app(\App\Services\Portail\ReferencePublique::class)->formater($reference),
        ]);
    }

    /**
     * Marque une candidature comme acceptee.
     *
     * Ne cree rien : c'est un accuse de decision, qui sort le dossier de la
     * file d'attente et le range dans « a inscrire ». L'inscription elle-meme
     * se fait par le flux habituel, avec les pieces sous les yeux.
     */
    public function accepter(Request $request, ESBTPCandidature $candidature): RedirectResponse
    {
        if (! $this->decider($candidature, ['statut' => ESBTPCandidature::STATUT_ACCEPTEE])) {
            return back()->with('error', 'Cette candidature a déjà été traitée.');
        }

        Log::info('Candidature acceptee', [
            'candidature_id' => $candidature->id,
            'traite_par' => auth()->id(),
        ]);

        // Qui peut inscrire est EMMENE au formulaire, on ne lui decrit pas ou
        // trouver un bouton.
        //
        // La phrase precedente le nommait — « le bouton Créer l'inscription
        // ouvre le formulaire pré-rempli » — et elle etait fausse pour
        // l'ecran d'ou l'on vient : on accepte depuis l'onglet « En attente »,
        // et la candidature acceptee en SORT. L'agent lisait donc le nom d'un
        // bouton absent de la liste sous ses yeux. Sur l'onglet « Toutes », le
        // tri remonte les dossiers en attente : passe vingt-cinq — le cas
        // d'Abidjan — la ligne quitte la page et le resultat est le meme.
        //
        // Rediriger supprime la question au lieu de la reformuler : le
        // formulaire pre-rempli est de toute facon l'etape suivante.
        //
        // Le Gate lit la porte REELLE de la route d'arrivee, pas une partie
        // d'elle : `inscriptions.create` seule ne suffit pas, un groupe exige
        // aussi l'une des permissions d'identite. La vue de la corbeille se
        // pose la meme question pour afficher son lien, et interroge le meme
        // Gate — sans quoi le bouton resterait visible la ou la redirection
        // s'abstient, et rendrait le 403 que tout ceci evite.
        if ($request->user()?->can('inscriptions.ouvrir-formulaire')) {
            return redirect()
                ->route('esbtp.inscriptions.create', ['candidature' => $candidature->id])
                ->with('success', 'Candidature acceptée. Voici le formulaire pré-rempli.');
        }

        // Celui qui ne peut pas inscrire reste ou il est : le dossier a change
        // d'onglet, et c'est tout ce qui le concerne.
        return back()->with(
            'success',
            'Candidature acceptée. Elle est prête à être inscrite par le service inscriptions.'
        );
    }

    public function rejeter(Request $request, ESBTPCandidature $candidature): RedirectResponse
    {
        $valide = $request->validate([
            // Un rejet sans motif est un rejet qu'on ne saura pas expliquer a
            // la famille qui rappellera.
            'motif_rejet' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $decidee = $this->decider($candidature, [
            'statut' => ESBTPCandidature::STATUT_REJETEE,
            'motif_rejet' => $valide['motif_rejet'],
        ]);

        return $decidee
            ? back()->with('success', 'Candidature rejetée.')
            : back()->with('error', 'Cette candidature a déjà été traitée.');
    }

    /**
     * L'unique ecriture de decision, et elle est verrouillee.
     *
     * Lire `estTraitable()` puis ecrire laisse une fenetre entre les deux, et
     * cette fenetre a deux occupants reels. Deux agents devant la meme file en
     * pleine rentree : l'un accepte, l'autre rejette, le dernier ecrit gagne et
     * le premier repart avec un message qui dit le contraire de la ligne. Et le
     * canal public lui-meme, dont `rouvrir()` ramene une candidature decidee en
     * attente quand la famille redepose avec la meme identite — cote service,
     * il n'existe aucun bouton pour cela, mais le depot, lui, ne demande la
     * permission de personne.
     *
     * D'ou le verrou, pose sur la CLE PRIMAIRE seule : sur un index unique, un
     * `lockForUpdate` qui ne trouve rien prend un verrou d'intervalle et deux
     * insertions concurrentes se bloquent l'une l'autre (1213 au lieu de 1062).
     * Ici la ligne existe forcement — le model binding l'a resolue — donc le
     * verrou porte sur elle et sur rien d'autre.
     *
     * @param  array<string, mixed>  $valeurs
     * @return bool false si la candidature n'etait plus a decider
     */
    private function decider(ESBTPCandidature $candidature, array $valeurs): bool
    {
        return DB::transaction(function () use ($candidature, $valeurs): bool {
            $verrouillee = ESBTPCandidature::query()
                ->whereKey($candidature->getKey())
                ->lockForUpdate()
                ->first();

            if ($verrouillee === null || ! $verrouillee->estTraitable()) {
                return false;
            }

            $verrouillee->update($valeurs + [
                'traite_par' => auth()->id(),
                'traite_at' => now(),
            ]);

            return true;
        });
    }
}
