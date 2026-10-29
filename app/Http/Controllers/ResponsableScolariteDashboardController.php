<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\RoleDashboardBreakdowns;

use App\Models\ESBTPClasse;
use App\Models\ESBTPDocumentApproval;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNotesWindow;
use App\Services\NotesWindowGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ResponsableScolariteDashboardController extends Controller
{
    public function __construct(private readonly NotesWindowGuard $notesWindows)
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user && $user->can('identity.registrar'), 403);

        $pendingApprovals = ESBTPDocumentApproval::query()
            ->where('status', ESBTPDocumentApproval::STATUS_PENDING)
            ->latest()
            ->limit(12)
            ->get();

        $openWindows = ESBTPNotesWindow::query()
            ->whereNull('closed_at')
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->with('classe')
            ->orderBy('ends_at')
            ->get();

        $inscriptionsEnAttente = ESBTPInscription::query()
            ->whereIn('status', ['en_attente', 'pending']);

        $pendingInscriptions = (clone $inscriptionsEnAttente)->count();

        // Quand aucune fenetre de saisie n'est ouverte, la validation des
        // inscriptions est le seul travail en cours de ce role : la liste
        // evite un tableau de bord vide qui n'oriente vers rien.
        $inscriptionsAValider = $inscriptionsEnAttente
            ->with(['etudiant:id,nom,prenoms', 'classe:id,name'])
            ->latest()
            ->limit(8)
            ->get();

        $classes = ESBTPClasse::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('dashboard.responsable-scolarite', [
            'noteProgress' => app(RoleDashboardBreakdowns::class)->noteEntryProgress(),
            'user' => $user,
            'pendingApprovals' => $pendingApprovals,
            'openWindows' => $openWindows,
            'pendingInscriptions' => $pendingInscriptions,
            'inscriptionsAValider' => $inscriptionsAValider,
            'classes' => $classes,
        ]);
    }
}