<?php

namespace App\Http\Controllers\ESBTP;

use App\Exceptions\ReglagesRdvIncomplets;
use App\Http\Controllers\Controller;
use App\Models\ESBTPRdvCreneau;
use App\Models\Setting;
use App\Services\RendezVous\AffecteurDossiersRdv;
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
        private readonly AffecteurDossiersRdv $affecteur,
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
            'rdv' => $this->reglages,
            'rdvJours' => [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'],
            'rdvJoursChoisis' => array_map('strval', array_filter(preg_split(
                '/[,\s]+/',
                $this->reglages->valeur(RendezVousReglages::JOURS, '1,2,3,4,5')
            ) ?: [])),
        ]);
    }

    public function enregistrerReglages(Request $request): RedirectResponse
    {
        $brut = $request->all();
        $auteur = auth()->id();

        $jours = $brut['inscriptions_rdv_jours_ouverts'] ?? [];
        Setting::set(
            RendezVousReglages::JOURS,
            is_array($jours) ? implode(',', array_map('strval', $jours)) : '',
            $auteur
        );

        foreach (RendezVousReglages::clesTexte() as $cle) {
            if ($cle === RendezVousReglages::JOURS) {
                continue;
            }

            $cleFormulaire = str_replace('.', '_', $cle);
            if (! array_key_exists($cle, $brut) && ! array_key_exists($cleFormulaire, $brut)) {
                continue;
            }

            $soumis = $brut[$cle] ?? $brut[$cleFormulaire] ?? '';
            Setting::set($cle, is_string($soumis) ? trim($soumis) : '', $auteur);
        }

        foreach (RendezVousReglages::clesBascules() as $cle) {
            $cleFormulaire = str_replace('.', '_', $cle);
            $allume = filter_var($brut[$cle] ?? $brut[$cleFormulaire] ?? false, FILTER_VALIDATE_BOOLEAN);
            Setting::set($cle, $allume ? '1' : '0', $auteur);
        }

        Setting::clearCache();

        return redirect()
            ->route('esbtp.rendez-vous.index')
            ->with('success', 'Réglages enregistrés. Vous pouvez générer les créneaux.');
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

    public function placer(): RedirectResponse
    {
        $rapport = $this->affecteur->placer();

        return redirect()
            ->route('esbtp.rendez-vous.index')
            ->with('success', sprintf(
                '%d dossiers placés et convoqués par mail. %d sans email, %d sans créneau, %d déjà réservés.',
                $rapport['places'],
                $rapport['sans_email'],
                $rapport['sans_creneau'],
                $rapport['deja']
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
