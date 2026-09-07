<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Http\Controllers\Controller;
use App\Models\ESBTPRdvCreneau;
use App\Services\RendezVous\GenerateurCreneaux;
use App\Services\RendezVous\RendezVousReglages;
use App\Services\Reinscription\PortailReinscriptionService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ESBTPRendezVousController extends Controller
{
    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly GenerateurCreneaux $generateur,
    ) {
    }

    public function index(Request $request)
    {
        $debutBrut = is_string($request->query('debut')) ? $request->query('debut') : '';
        $jour = PortailReinscriptionService::interpreterDateIso($debutBrut) ?? Carbon::today();
        $debut = $jour->copy()->startOfWeek(Carbon::MONDAY);
        $fin = $debut->copy()->addDays(6);

        $creneaux = ESBTPRdvCreneau::query()
            ->whereDate('date', '>=', $debut->toDateString())
            ->whereDate('date', '<=', $fin->toDateString())
            ->orderBy('date')
            ->orderBy('heure_debut')
            ->get()
            ->groupBy(fn (ESBTPRdvCreneau $c) => $c->date->toDateString());

        $jours = [];
        $curseur = $debut->copy();
        while ($curseur->lte($fin)) {
            $cle = $curseur->toDateString();
            $jours[] = [
                'date' => $cle,
                'libelle' => $curseur->translatedFormat('l j F'),
                'creneaux' => $creneaux->get($cle, collect()),
            ];
            $curseur->addDay();
        }

        return view('esbtp.rendez-vous.index', [
            'debut' => $debut,
            'fin' => $fin,
            'semainePrecedente' => $debut->copy()->subWeek()->toDateString(),
            'semaineSuivante' => $debut->copy()->addWeek()->toDateString(),
            'jours' => $jours,
            'debit' => $this->reglages->debitJournalier(),
            'peutGerer' => auth()->user()?->can('inscriptions.rdv.manage') ?? false,
            'peutConfigurer' => auth()->user()?->can('inscriptions.rdv.configure') ?? false,
        ]);
    }

    public function generer(): RedirectResponse
    {
        try {
            $rapport = $this->generateur->generer();
        } catch (ReglagesRdvIncomplets $e) {
            return redirect()
                ->route('esbtp.rendez-vous.index')
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('esbtp.rendez-vous.index')
            ->with('success', sprintf(
                'Créneaux générés : %d créés, %d mis à jour, %d fermés, %d conservés (occupés).',
                $rapport->crees,
                $rapport->misAJour,
                $rapport->fermes,
                $rapport->conservesOccupes
            ));
    }

    public function ouvrir(ESBTPRdvCreneau $creneau): RedirectResponse
    {
        $creneau->update(['ouvert' => true]);

        return back()->with('success', 'Créneau ouvert.');
    }

    public function fermer(ESBTPRdvCreneau $creneau): RedirectResponse
    {
        $creneau->update(['ouvert' => false]);

        return back()->with('success', 'Créneau fermé.');
    }
}
