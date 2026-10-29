<?php

namespace App\Http\Controllers;

use App\Services\DirecteurEtudesDashboardData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DirecteurEtudesDashboardController extends Controller
{
    public function __construct(private readonly DirecteurEtudesDashboardData $dashboardData)
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user && $user->can('identity.direct_studies'), 403);

        return view('dashboard.directeur-etudes', $this->dashboardData->build());
    }

    public function data(): JsonResponse
    {
        $user = Auth::user();
        if (! $user || ! $user->can('identity.direct_studies')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($this->dashboardData->jsonPayload());
    }
}
