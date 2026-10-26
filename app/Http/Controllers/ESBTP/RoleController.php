<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Compatibilite des anciennes routes /esbtp/roles.
 *
 * L'ecriture historique sur le nom et la suppression des roles contournaient
 * la matrice de gestion, le registre canonique et l'audit des extensions.
 * L'interface Personnel est maintenant l'unique parcours de gestion standard.
 */
class RoleController extends Controller
{
    public function index()
    {
        return redirect()->route('esbtp.personnel.unified.index');
    }

    public function create()
    {
        return redirect()->route('esbtp.personnel.unified.index');
    }

    public function edit(Role $role)
    {
        return redirect()->route('esbtp.personnel.unified.index');
    }

    public function store(Request $request)
    {
        abort(403, 'Les roles sont crees depuis la gestion du personnel.');
    }

    public function update(Request $request, Role $role)
    {
        abort(403, 'Les roles sont modifies depuis la gestion du personnel.');
    }

    public function destroy(Role $role)
    {
        abort(403, 'La suppression de roles utilise la gestion du personnel.');
    }
}
