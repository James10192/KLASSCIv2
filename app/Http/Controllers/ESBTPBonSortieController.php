<?php

namespace App\Http\Controllers;

use App\Models\ESBTPBonSortie;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

class ESBTPBonSortieController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
        // Add permissions middleware here later
    }

    public function index()
    {
        $bonsDeSortie = ESBTPBonSortie::latest()->paginate(15);
        return view('esbtp.comptabilite.bons-sortie.index', compact('bonsDeSortie'));
    }

    public function create()
    {
        return view('esbtp.comptabilite.bons-sortie.create');
    }

    public function store(Request $request)
    {
        // Validation logic here...

        $bon = ESBTPBonSortie::create([
            // data from request...
            'createur_id' => Auth::id(),
        ]);

        // Notify approver
        $this->notificationService->notifyBonApproval($bon->id, $request->approbateur_id);

        return redirect()->route('esbtp.bons_sortie.index')->with('success', 'Bon de sortie créé avec succès.');
    }

    public function show(ESBTPBonSortie $bonDeSortie)
    {
        return view('esbtp.comptabilite.bons-sortie.show', compact('bonDeSortie'));
    }

    public function edit(ESBTPBonSortie $bonDeSortie)
    {
        return view('esbtp.comptabilite.bons-sortie.edit', compact('bonDeSortie'));
    }

    public function update(Request $request, ESBTPBonSortie $bonDeSortie)
    {
        // Update logic here...
        return redirect()->route('esbtp.bons_sortie.index')->with('success', 'Bon de sortie mis à jour avec succès.');
    }

    public function destroy(ESBTPBonSortie $bonDeSortie)
    {
        $bonDeSortie->delete();
        return redirect()->route('esbtp.bons_sortie.index')->with('success', 'Bon de sortie supprimé avec succès.');
    }

    public function approve(Request $request, ESBTPBonSortie $bonDeSortie)
    {
        // Approval logic here...
        $bonDeSortie->update([
            'statut' => 'approuve',
            'approved_at' => now(),
            'approbateur_id' => Auth::id(),
        ]);

        // Notify creator
        // $this->notificationService->notifyBonApproved($bonDeSortie->id, $bonDeSortie->createur_id);

        return redirect()->route('esbtp.bons_sortie.show', $bonDeSortie->id)->with('success', 'Bon de sortie approuvé.');
    }
} 