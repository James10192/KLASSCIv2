<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\Admissions\DemandeDInscription;
use App\Domain\Admissions\FileDesDemandes;
use App\Domain\Admissions\PreparationDInscription;
use App\Http\Controllers\Controller;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\RendezVous\AccueilRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\RendezVous\ReservateurRdv;
use App\Support\ListeInfinie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Demandes d'inscription : les candidatures des nouveaux eleves et les
 * demandes de reinscription, dans une seule file.
 *
 * Ce controleur ne DECIDE rien. Accepter, rejeter, convertir restent chez
 * ESBTPCandidatureController et ESBTPReinscriptionDemandeController, qui
 * repondent aussi en JSON ; inscrire reste ESBTPInscriptionController::store.
 * Il lit, il prepare, et il pose un rendez-vous.
 */
class ESBTPDemandesInscriptionController extends Controller
{
    public function __construct(private readonly FileDesDemandes $file)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        $filtres = $this->filtres($request);
        $page = $this->file->page($request->user(), $filtres, max(1, (int) $request->query('page', 1)));

        if (ListeInfinie::demandee($request)) {
            return ListeInfinie::reponse($page, fn (DemandeDInscription $d) => view('esbtp.admissions.demandes._ligne', ['d' => $d])->render());
        }

        $donnees = [
            'demandes' => $page,
            'filtres' => $filtres,
            'compteurs' => $this->file->compteurs($request->user()),
            'types' => FileDesDemandes::typesVisibles($request->user()),
        ];

        if ($request->boolean('fragment')) {
            return response()->json([
                'liste' => view('esbtp.admissions.demandes._liste', $donnees)->render(),
                'kpis' => view('esbtp.admissions.demandes._kpis', $donnees)->render(),
                'compteurs' => $donnees['compteurs'],
            ]);
        }

        return view('esbtp.admissions.demandes.index', $donnees + [
            'classes' => $request->user()->can('reinscriptions.demandes.process') ? app(PreparationDInscription::class)->classes() : [],
            'ouvrir' => (string) $request->query('ouvrir', ''),
            'agir' => $request->boolean('agir'),
        ]);
    }

    /** Le panneau du dossier, rendu cote serveur. */
    public function dossier(Request $request, string $type, int $id): JsonResponse
    {
        $demande = $this->demande($request, $type, $id);

        return response()->json([
            'cle' => $demande->cle(),
            'html' => view('esbtp.admissions.demandes._panneau', ['d' => $demande])->render(),
        ]);
    }

    /** Ce que la fenetre « Accepter et inscrire » affiche avant le clic. */
    public function preparerInscription(ESBTPCandidature $candidature, PreparationDInscription $preparation): JsonResponse
    {
        abort_unless(in_array($candidature->statut, [ESBTPCandidature::STATUT_EN_ATTENTE, ESBTPCandidature::STATUT_ACCEPTEE], true), 422, 'Cette candidature est déjà traitée.');

        return response()->json($preparation->pour($candidature));
    }

    public function creneaux(AccueilRdv $accueil): JsonResponse
    {
        return response()->json(['creneaux' => $accueil->creneauxProposes()]);
    }

    /**
     * Pose un rendez-vous a une famille qui n'en a pas, puis la convoque.
     * Meme chemin que le placement en masse du planning, pour une seule famille.
     */
    public function fixerRendezVous(Request $request, string $type, int $id, ReservateurRdv $reservateur, FileConvocationsRdv $convocations): JsonResponse
    {
        $creneau = filter_var($request->input('creneau_id'), FILTER_VALIDATE_INT);
        if ($creneau === false) {
            return response()->json(['message' => 'Choisissez un créneau.'], 422);
        }

        $resultat = $reservateur->placer($this->demande($request, $type, $id)->modele, $creneau);
        if (! $resultat['ok']) {
            return response()->json(['message' => $this->refusRendezVous($resultat['code'])], 422);
        }

        $convocations->confirmer($resultat['reservation']);
        $c = $resultat['reservation']->creneau;

        return response()->json(['message' => 'Rendez-vous fixé au '.$c->date->translatedFormat('l j F').' à '.$c->heureDebutHi().'. La convocation part par e-mail si la famille en a un ; sinon, elle rejoint la liste des familles à prévenir.']);
    }

    /** @return array{type: string, etat: string, q: string, sans_rdv: bool, contact: bool} */
    private function filtres(Request $request): array
    {
        $etat = (string) $request->query('etat', 'a_traiter');

        return [
            'type' => in_array($request->query('type'), [FileDesDemandes::TYPE_NOUVELLE, FileDesDemandes::TYPE_REINSCRIPTION], true) ? (string) $request->query('type') : '',
            'etat' => in_array($etat, FileDesDemandes::ETATS, true) ? $etat : 'a_traiter',
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 80),
            'sans_rdv' => $request->boolean('sans_rdv'),
            'contact' => $request->boolean('contact'),
        ];
    }

    /** Charge une demande de la file, dans les droits de l'agent. */
    private function demande(Request $request, string $type, int $id): DemandeDInscription
    {
        abort_unless(in_array($type, FileDesDemandes::typesVisibles($request->user()), true), 403);
        $avecRdv = ['reservations' => fn ($r) => $r->occupantes()->with('creneau', 'accueilliPar:id,name')->latest('id')];

        return $type === FileDesDemandes::TYPE_NOUVELLE
            ? DemandeDInscription::deCandidature(ESBTPCandidature::with(['anneeUniversitaire:id,name', 'filiere:id,name', 'niveau:id,name', 'traitePar:id,name'] + $avecRdv)->findOrFail($id))
            : DemandeDInscription::deReinscription(ESBTPReinscriptionDemande::with(['etudiant', 'anneeUniversitaire:id,name,is_current', 'classeSouhaitee:id,name', 'traitePar:id,name', 'inscription.classe:id,name'] + $avecRdv)->findOrFail($id));
    }

    private function refusRendezVous(string $code): string
    {
        return match ($code) {
            'deja_reserve' => 'Cette famille a déjà un rendez-vous.',
            'introuvable' => 'Ce dossier est clos : il ne prend plus de rendez-vous.',
            'complet' => 'Ce créneau vient d\'être rempli. Choisissez-en un autre.',
            default => AccueilRdv::message($code),
        };
    }
}
