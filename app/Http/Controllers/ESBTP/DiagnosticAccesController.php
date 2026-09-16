<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PermissionRegistry;
use App\Services\Security\DiagnosticAcces;
use App\Services\Security\ProvenanceReglage;
use Illuminate\Http\Request;

class DiagnosticAccesController extends Controller
{
    public function index(Request $request, PermissionRegistry $registry, ProvenanceReglage $provenance)
    {
        $q = trim((string) $request->get('q', ''));
        $personnes = collect();
        if ($q !== '') {
            $personnes = User::query()
                ->where(function ($query) use ($q) {
                    $query->where('name', 'like', '%'.$q.'%')
                        ->orWhere('username', 'like', '%'.$q.'%')
                        ->orWhere('email', 'like', '%'.$q.'%');
                })
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'username', 'is_active']);
        }

        return view('esbtp.admin.diagnostic-acces', [
            'q' => $q,
            'personnes' => $personnes,
            'droits' => $this->droitsFrequents($registry),
            'cible' => null,
            'verdict' => null,
            'permission' => (string) $request->get('permission', ''),
            'reglages' => $provenance->catalogue(),
        ]);
    }

    public function show(Request $request, User $user, DiagnosticAcces $diagnostic, PermissionRegistry $registry, ProvenanceReglage $provenance)
    {
        $permission = (string) $request->get('permission', 'notes.view');
        $classeId = $request->filled('classe_id') ? (int) $request->get('classe_id') : null;
        $verdict = $diagnostic->pour($user, $permission, $classeId);

        return view('esbtp.admin.diagnostic-acces', [
            'q' => $user->name,
            'personnes' => collect([$user]),
            'droits' => $this->droitsFrequents($registry),
            'cible' => $user,
            'verdict' => $verdict,
            'permission' => $permission,
            'reglages' => $provenance->catalogue(),
        ]);
    }

    /** @return \Illuminate\Support\Collection<int, array{name: string, label: string}> */
    private function droitsFrequents(PermissionRegistry $registry)
    {
        $cles = [
            'notes.view',
            'notes.create',
            'notes.edit',
            'paiements.validate',
            'users.manage',
            'system.manage',
            'personnel.manage',
            'lmd.jury.publish',
            'security.audit.view',
        ];

        return collect($cles)->map(function (string $name) use ($registry) {
            $meta = $registry->permissionMeta($name);

            return ['name' => $name, 'label' => $meta['label'] ?? $name];
        })->values();
    }
}
