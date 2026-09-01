<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnnonce;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CommunicationDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = Auth::user();
        abort_unless($user && $user->can('identity.communicate'), 403);

        $annonces = ESBTPAnnonce::query()->latest('date_publication')->limit(12)->get();

        return view('dashboard.communication', [
            'user' => $user,
            'annonces' => $annonces,
            'annoncesPubliees' => ESBTPAnnonce::query()->where('is_published', true)->count(),
            'mailpulseOn' => SettingsHelper::get('mailpulse_enabled', '0') === '1',
        ]);
    }
}
