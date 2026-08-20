<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\RoleDashboardBreakdowns;

use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AgentInscriptionDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user && $user->can('identity.enrollment_officer'), 403);

        $validatedStatus = 'valid'.chr(233);

        $pendingValidation = ESBTPInscription::query()
            ->whereIn('status', ['en_attente', 'pending'])
            ->whereHas('paiements', fn ($query) => $query->where('status', $validatedStatus))
            ->count();

        $waitingPayment = ESBTPInscription::query()
            ->whereIn('status', ['en_attente', 'pending'])
            ->whereDoesntHave('paiements', fn ($query) => $query->where('status', $validatedStatus))
            ->count();

        $validatedToday = ESBTPInscription::query()
            ->whereDate('updated_at', now()->toDateString())
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('workflow_step', 'etudiant_cree');
            })
            ->count();

        $sousReserve = ESBTPInscription::query()
            ->where('is_sous_reserve', true)
            ->count();

        return view('dashboard.agent-inscription', [
            'funnel' => app(RoleDashboardBreakdowns::class)->enrollmentFunnel(),
            'user' => $user,
            'pendingValidation' => $pendingValidation,
            'waitingPayment' => $waitingPayment,
            'validatedToday' => $validatedToday,
            'sousReserve' => $sousReserve,
        ]);
    }
}
