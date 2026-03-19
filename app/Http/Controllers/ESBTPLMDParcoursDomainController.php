<?php

namespace App\Http\Controllers;

use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\User;
use Illuminate\Http\Request;

class ESBTPLMDParcoursDomainController extends Controller
{
    /**
     * Afficher la page index avec l'arborescence Domaines > Mentions > Parcours.
     */
    public function index()
    {
        $domaines = ESBTPLMDDomaine::with(['mentions.parcours', 'mentions.domaine'])
            ->orderBy('name')
            ->get();

        return view('esbtp.lmd.parcours.index', compact('domaines'));
    }

    // =========================================================================
    // DOMAINES
    // =========================================================================

    /**
     * Créer un nouveau domaine.
     */
    public function storeDomaine(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:esbtp_lmd_domaines,code',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ]);

        try {
            ESBTPLMDDomaine::create([
                'name'        => $validated['name'],
                'code'        => $validated['code'],
                'description' => $validated['description'] ?? null,
                'is_active'   => $validated['is_active'] ?? true,
                'created_by'  => auth()->id(),
                'updated_by'  => auth()->id(),
            ]);

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Domaine créé avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la création du domaine : Une erreur est survenue. Veuillez réessayer.');
        }
    }

    /**
     * Mettre à jour un domaine existant.
     */
    public function updateDomaine(Request $request, ESBTPLMDDomaine $domaine)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:esbtp_lmd_domaines,code,' . $domaine->id,
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ]);

        try {
            $domaine->update([
                'name'        => $validated['name'],
                'code'        => $validated['code'],
                'description' => $validated['description'] ?? null,
                'is_active'   => $validated['is_active'] ?? $domaine->is_active,
                'updated_by'  => auth()->id(),
            ]);

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Domaine mis à jour avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la mise à jour du domaine : Une erreur est survenue. Veuillez réessayer.');
        }
    }

    /**
     * Supprimer un domaine (si aucune mention rattachée).
     */
    public function destroyDomaine(ESBTPLMDDomaine $domaine)
    {
        if ($domaine->mentions()->count() > 0) {
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Impossible de supprimer ce domaine : il possède encore des mentions rattachées.');
        }

        try {
            $domaine->delete();

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Domaine supprimé avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la suppression du domaine : Une erreur est survenue. Veuillez réessayer.');
        }
    }

    // =========================================================================
    // MENTIONS
    // =========================================================================

    /**
     * Créer une nouvelle mention.
     */
    public function storeMention(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:esbtp_lmd_mentions,code',
            'domaine_id'  => 'required|exists:esbtp_lmd_domaines,id',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ]);

        try {
            ESBTPLMDMention::create([
                'name'        => $validated['name'],
                'code'        => $validated['code'],
                'domaine_id'  => $validated['domaine_id'],
                'description' => $validated['description'] ?? null,
                'is_active'   => $validated['is_active'] ?? true,
                'created_by'  => auth()->id(),
                'updated_by'  => auth()->id(),
            ]);

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Mention créée avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la création de la mention : Une erreur est survenue. Veuillez réessayer.');
        }
    }

    /**
     * Mettre à jour une mention existante.
     */
    public function updateMention(Request $request, ESBTPLMDMention $mention)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:esbtp_lmd_mentions,code,' . $mention->id,
            'domaine_id'  => 'required|exists:esbtp_lmd_domaines,id',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ]);

        try {
            $mention->update([
                'name'        => $validated['name'],
                'code'        => $validated['code'],
                'domaine_id'  => $validated['domaine_id'],
                'description' => $validated['description'] ?? null,
                'is_active'   => $validated['is_active'] ?? $mention->is_active,
                'updated_by'  => auth()->id(),
            ]);

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Mention mise à jour avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la mise à jour de la mention : Une erreur est survenue. Veuillez réessayer.');
        }
    }

    /**
     * Supprimer une mention (si aucun parcours rattaché).
     */
    public function destroyMention(ESBTPLMDMention $mention)
    {
        if ($mention->parcours()->count() > 0) {
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Impossible de supprimer cette mention : elle possède encore des parcours rattachés.');
        }

        try {
            $mention->delete();

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Mention supprimée avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la suppression de la mention : Une erreur est survenue. Veuillez réessayer.');
        }
    }

    // =========================================================================
    // PARCOURS
    // =========================================================================

    /**
     * Créer un nouveau parcours.
     */
    public function storeParcours(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => 'required|string|max:50|unique:esbtp_lmd_parcours,code',
            'mention_id'      => 'required|exists:esbtp_lmd_mentions,id',
            'filiere_id'      => 'nullable|exists:esbtp_filieres,id',
            'description'     => 'nullable|string|max:1000',
            'responsable_id'  => 'nullable|exists:users,id',
            'credits_licence' => 'nullable|integer|min:0',
            'credits_master'  => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        try {
            ESBTPLMDParcours::create([
                'name'            => $validated['name'],
                'code'            => $validated['code'],
                'mention_id'      => $validated['mention_id'],
                'filiere_id'      => $validated['filiere_id'] ?? null,
                'description'     => $validated['description'] ?? null,
                'responsable_id'  => $validated['responsable_id'] ?? null,
                'credits_licence' => $validated['credits_licence'] ?? 180,
                'credits_master'  => $validated['credits_master'] ?? 120,
                'is_active'       => $validated['is_active'] ?? true,
            ]);

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Parcours créé avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la création du parcours : Une erreur est survenue. Veuillez réessayer.');
        }
    }

    /**
     * Mettre à jour un parcours existant.
     */
    public function updateParcours(Request $request, ESBTPLMDParcours $parcours)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'code'            => 'required|string|max:50|unique:esbtp_lmd_parcours,code,' . $parcours->id,
            'mention_id'      => 'required|exists:esbtp_lmd_mentions,id',
            'filiere_id'      => 'nullable|exists:esbtp_filieres,id',
            'description'     => 'nullable|string|max:1000',
            'responsable_id'  => 'nullable|exists:users,id',
            'credits_licence' => 'nullable|integer|min:0',
            'credits_master'  => 'nullable|integer|min:0',
            'is_active'       => 'nullable|boolean',
        ]);

        try {
            $parcours->update([
                'name'            => $validated['name'],
                'code'            => $validated['code'],
                'mention_id'      => $validated['mention_id'],
                'filiere_id'      => $validated['filiere_id'] ?? null,
                'description'     => $validated['description'] ?? null,
                'responsable_id'  => $validated['responsable_id'] ?? null,
                'credits_licence' => $validated['credits_licence'] ?? $parcours->credits_licence ?? 180,
                'credits_master'  => $validated['credits_master'] ?? $parcours->credits_master ?? 120,
                'is_active'       => $validated['is_active'] ?? $parcours->is_active,
            ]);

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Parcours mis à jour avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la mise à jour du parcours : Une erreur est survenue. Veuillez réessayer.');
        }
    }

    /**
     * Supprimer un parcours (si aucune classe rattachée).
     */
    public function destroyParcours(ESBTPLMDParcours $parcours)
    {
        if ($parcours->classes()->count() > 0) {
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Impossible de supprimer ce parcours : il possède encore des classes rattachées.');
        }

        try {
            $parcours->delete();

            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('success', 'Parcours supprimé avec succès.');
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('esbtp.lmd.parcours-domain.index')
                ->with('error', 'Erreur lors de la suppression du parcours : Une erreur est survenue. Veuillez réessayer.');
        }
    }
}
