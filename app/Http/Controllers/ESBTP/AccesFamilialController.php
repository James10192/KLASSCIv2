<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPFamilyAccessGrant;
use App\Models\ESBTPFamilyAccountInvitation;
use App\Models\ESBTPParent;
use App\Models\ESBTPEtudiant;
use App\Services\Familles\AccesFamilial;
use App\Services\Familles\ReglagesFamille;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class AccesFamilialController extends Controller
{
    public function espace(Request $request, ReglagesFamille $settings, AccesFamilial $policy): View
    {
        abort_unless($settings->enabled(), 404);
        abort_if($request->user()->hasRole('etudiant'), 403);

        $parents = ESBTPParent::query()->where('user_id', $request->user()->id)->get();
        abort_if($parents->isEmpty(), 403);

        $grants = ESBTPFamilyAccessGrant::query()
            ->with(['parent', 'etudiant'])
            ->whereIn('parent_id', $parents->pluck('id'))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->get()
            ->filter(fn (ESBTPFamilyAccessGrant $grant) => $policy->peutConsulter($grant, $request->user()))
            ->values();

        return view('esbtp.familles.espace', ['grants' => $grants]);
    }

    public function habilitations(ReglagesFamille $settings): View
    {
        abort_unless($settings->enabled(), 404);

        $relations = DB::table('esbtp_etudiant_parent')
            ->join('esbtp_parents', 'esbtp_parents.id', '=', 'esbtp_etudiant_parent.parent_id')
            ->join('esbtp_etudiants', 'esbtp_etudiants.id', '=', 'esbtp_etudiant_parent.etudiant_id')
            ->where('esbtp_etudiant_parent.is_tuteur', true)
            ->whereNull('esbtp_parents.deleted_at')
            ->whereNull('esbtp_etudiants.deleted_at')
            ->orderBy('esbtp_etudiant_parent.parent_id')
            ->limit(50)
            ->get([
                'esbtp_parents.id as parent_id',
                'esbtp_parents.nom as parent_nom',
                'esbtp_parents.prenoms as parent_prenoms',
                'esbtp_parents.user_id as parent_user_id',
                'esbtp_etudiants.id as etudiant_id',
                'esbtp_etudiants.nom as etudiant_nom',
                'esbtp_etudiants.prenoms as etudiant_prenoms',
                'esbtp_etudiants.date_naissance',
            ]);

        $grants = ESBTPFamilyAccessGrant::query()
            ->whereIn('parent_id', $relations->pluck('parent_id'))
            ->get()
            ->keyBy(fn ($g) => $g->parent_id.':'.$g->etudiant_id);

        $invitations = ESBTPFamilyAccountInvitation::query()
            ->whereIn('grant_id', $grants->pluck('id'))
            ->orderByDesc('id')->get()
            ->unique('grant_id')->keyBy('grant_id');

        return view('esbtp.familles.habilitations', compact('relations', 'grants', 'invitations'));
    }

    public function approuver(Request $request, ReglagesFamille $settings, AccesFamilial $service): RedirectResponse
    {
        abort_unless($settings->enabled(), 404);
        $input = $request->validate([
            'parent_id' => ['required', 'integer', 'exists:esbtp_parents,id'],
            'etudiant_id' => ['required', 'integer', 'exists:esbtp_etudiants,id'],
            'evidence_type' => ['required', 'in:identite_guichet,document_legal,accord_formel'],
            'reference' => ['required', 'string', 'min:6', 'max:120'],
            'consent_reference' => ['nullable', 'string', 'min:6', 'max:120'],
        ]);

        $service->approuver(
            ESBTPParent::findOrFail($input['parent_id']),
            ESBTPEtudiant::findOrFail($input['etudiant_id']),
            $request->user(),
            $input['evidence_type'],
            $input['reference'],
            $input['consent_reference'] ?? null
        );

        return back()->with('success', 'Accès familial autorisé pour cette relation et cette période.');
    }

    public function revoquer(
        Request $request,
        ESBTPFamilyAccessGrant $grant,
        ReglagesFamille $settings,
        AccesFamilial $service
    ): RedirectResponse {
        abort_unless($settings->enabled(), 404);
        $service->revoquer($grant, $request->user());

        return back()->with('success', 'Accès du responsable révoqué immédiatement.');
    }
}
