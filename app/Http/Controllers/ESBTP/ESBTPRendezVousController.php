<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Http\Controllers\Controller;
use App\Models\ESBTPRdvCreneau;
use App\Models\Setting;
use App\Services\RendezVous\AffecteurDossiersRdv;
use App\Services\RendezVous\FamillesAPrevenirRdv;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\RendezVous\GenerateurCreneaux;
use App\Services\RendezVous\RendezVousReglages;
use App\Services\RendezVous\TableauRendezVous;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ecran des rendez-vous d'inscription. Toutes les actions repondent en JSON :
 * l'ecran se met a jour sans rechargement, en redemandant ses fragments a index().
 */
class ESBTPRendezVousController extends Controller
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly TableauRendezVous $tableau,
    ) {
    }

    public function index(Request $request)
    {
        $donnees = $this->tableau->pourSemaine(is_string($request->query('debut')) ? $request->query('debut') : null) + [
            'peutGerer' => $request->user()?->can('inscriptions.rdv.manage') ?? false,
            'peutConfigurer' => $request->user()?->can('inscriptions.rdv.configure') ?? false,
            'rdv' => $this->reglages,
            'aPrevenir' => app(FamillesAPrevenirRdv::class)->compter(),
        ];

        if ($request->boolean('fragment')) {
            return response()->json([
                'kpis' => view('esbtp.rendez-vous.partials._kpis', $donnees)->render(),
                'chaine' => view('esbtp.rendez-vous.partials._chaine', $donnees)->render(),
                'tableau' => view('esbtp.rendez-vous.partials._tableau', $donnees)->render(),
                'reglages' => view('esbtp.rendez-vous.partials._reglages_resume', $donnees)->render(),
            ]);
        }

        return view('esbtp.rendez-vous.index', $donnees);
    }

    public function enregistrerReglages(Request $request): JsonResponse
    {
        $brut = $request->all();
        $auteur = auth()->id();

        $jours = $brut['inscriptions_rdv_jours_ouverts'] ?? [];
        Setting::set(RendezVousReglages::JOURS, is_array($jours) ? implode(',', array_map('strval', $jours)) : '', $auteur);

        // PHP change les points des noms de champs en soulignes : on lit les deux.
        foreach (RendezVousReglages::clesTexte() as $cle) {
            $cleFormulaire = str_replace('.', '_', $cle);
            if ($cle === RendezVousReglages::JOURS || (! array_key_exists($cle, $brut) && ! array_key_exists($cleFormulaire, $brut))) {
                continue;
            }
            $soumis = $brut[$cle] ?? $brut[$cleFormulaire] ?? '';
            Setting::set($cle, is_string($soumis) ? trim($soumis) : '', $auteur);
        }

        foreach (RendezVousReglages::clesBascules() as $cle) {
            $allume = filter_var($brut[$cle] ?? $brut[str_replace('.', '_', $cle)] ?? false, FILTER_VALIDATE_BOOLEAN);
            Setting::set($cle, $allume ? '1' : '0', $auteur);
        }

        Setting::clearCache();

        return response()->json(['message' => 'Réglages enregistrés.']);
    }

    public function generer(GenerateurCreneaux $generateur): JsonResponse
    {
        try {
            $r = $generateur->generer();
        } catch (ReglagesRdvIncomplets $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => sprintf(
            'Créneaux générés : %d créés, %d mis à jour, %d fermés, %d conservés car déjà réservés.',
            $r->crees, $r->misAJour, $r->fermes, $r->conservesOccupes
        )]);
    }

    public function placer(AffecteurDossiersRdv $affecteur, FileConvocationsRdv $file): JsonResponse
    {
        $r = $affecteur->placer();
        if ($r['refus'] !== null) {
            return response()->json(['message' => $r['refus']], 422);
        }

        return response()->json($r + [
            'a_envoyer' => $file->enAttente(),
            'message' => sprintf(
                '%d dossiers placés. %d sans e-mail, %d sans créneau libre, %d déjà traités.',
                $r['places'], $r['sans_email'], $r['sans_creneau'], $r['deja']
            ),
        ]);
    }

    public function envoyerConvocations(FileConvocationsRdv $file): JsonResponse
    {
        // Un paquet court : l'ecran rappelle tant qu'il en reste, et affiche la progression.
        return response()->json($file->envoyerUnPaquet(15, 20.0));
    }

    public function remettreConvocations(Request $request, FileConvocationsRdv $file): JsonResponse
    {
        $quoi = $request->input('quoi') === 'echecs' ? 'echecs' : 'inconnues';

        return response()->json(['remises' => $file->remettreEnAttente($quoi), 'a_envoyer' => $file->enAttente()]);
    }

    public function ouvrir(ESBTPRdvCreneau $creneau): JsonResponse
    {
        $creneau->update(['ouvert' => true]);

        return response()->json(['message' => 'Créneau ouvert aux familles.']);
    }

    public function fermer(ESBTPRdvCreneau $creneau): JsonResponse
    {
        $creneau->update(['ouvert' => false]);

        return response()->json(['message' => 'Créneau fermé : les familles ne le voient plus. Les réservations existantes sont conservées.']);
    }
}
