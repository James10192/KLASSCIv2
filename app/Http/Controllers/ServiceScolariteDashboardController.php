<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\RoleDashboardBreakdowns;

use App\Models\ESBTPDocumentApproval;
use App\Models\ESBTPNotesWindow;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServiceScolariteDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user && $user->can('identity.registrar_clerk'), 403);

        $approvedDocuments = ESBTPDocumentApproval::query()
            ->where('status', ESBTPDocumentApproval::STATUS_APPROVED)
            ->latest('approved_at')
            ->limit(20)
            ->get();

        $openWindows = ESBTPNotesWindow::query()
            ->whereNull('closed_at')
            ->whereDate('starts_at', '<=', now()->toDateString())
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->with('classe')
            ->orderBy('ends_at')
            ->get();

        return view('dashboard.service-scolarite', [
            'workload' => app(RoleDashboardBreakdowns::class)->clerkWorkload(),
            'user' => $user,
            'approvedDocuments' => $approvedDocuments,
            'openWindows' => $openWindows,
        ]);
    }
}