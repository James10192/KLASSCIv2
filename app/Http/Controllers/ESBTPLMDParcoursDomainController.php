<?php

namespace App\Http\Controllers;

use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPUniteEnseignement;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use Illuminate\Http\Request;

class ESBTPLMDParcoursDomainController extends Controller
{
    /**
     * Afficher la page index avec l'arborescence Domaines > Mentions > Parcours.
     */
    public function index()
    {
        $domaines = ESBTPLMDDomaine::with([
                'mentions.parcours.classes',
                'mentions.parcours.unitesEnseignement',
                'mentions.domaine',
            ])
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

    // =========================================================================
    // CLASSES — Lier/Délier + Créer rapide
    // =========================================================================

    /**
     * Retourner les classes LMD non liées à un parcours (pour le modal lier).
     */
    public function getClassesDisponibles(ESBTPLMDParcours $parcours)
    {
        $classesLiees = ESBTPClasse::where('parcours_id', $parcours->id)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        $classesDisponibles = ESBTPClasse::where('systeme_academique', 'LMD')
            ->where(function ($q) use ($parcours) {
                $q->whereNull('parcours_id')
                  ->orWhere('parcours_id', '!=', $parcours->id);
            })
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'liees' => $classesLiees,
            'disponibles' => $classesDisponibles,
        ]);
    }

    /**
     * Lier/délier des classes à un parcours (AJAX).
     */
    public function syncClasses(Request $request, ESBTPLMDParcours $parcours)
    {
        $validated = $request->validate([
            'classe_ids' => 'present|array',
            'classe_ids.*' => 'exists:esbtp_classes,id',
        ]);

        $classeIds = $validated['classe_ids'];

        // Délier les classes précédemment liées à ce parcours mais absentes de la nouvelle liste
        ESBTPClasse::where('parcours_id', $parcours->id)
            ->whereNotIn('id', $classeIds)
            ->update(['parcours_id' => null]);

        // Lier les nouvelles classes
        if (!empty($classeIds)) {
            ESBTPClasse::whereIn('id', $classeIds)
                ->where('systeme_academique', 'LMD')
                ->update(['parcours_id' => $parcours->id]);
        }

        $count = count($classeIds);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "{$count} classe(s) liée(s) au parcours."]);
        }

        return redirect()->route('esbtp.lmd.parcours-domain.index')
            ->with('success', "{$count} classe(s) liée(s) au parcours {$parcours->name}.");
    }

    /**
     * Créer une classe LMD rapidement depuis le parcours (AJAX).
     */
    public function storeClasseRapide(Request $request, ESBTPLMDParcours $parcours)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_classes,code',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'places_totales' => 'required|integer|min:1',
        ]);

        $validated['parcours_id'] = $parcours->id;
        $validated['filiere_id'] = $parcours->filiere_id;
        // systeme_academique sera auto-set par le model event booted()
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $classe = ESBTPClasse::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Classe {$classe->name} créée et liée au parcours {$parcours->name}.",
                'classe' => $classe,
            ]);
        }

        return redirect()->route('esbtp.lmd.parcours-domain.index')
            ->with('success', "Classe {$classe->name} créée et liée au parcours {$parcours->name}.");
    }

    /**
     * Liste des UEs disponibles pour un parcours (liées + non liées).
     */
    public function getUesDisponibles(ESBTPLMDParcours $parcours)
    {
        // Get all pivot rows for this parcours, grouped by UE
        $pivotRows = $parcours->unitesEnseignement()
            ->select('esbtp_unites_enseignement.id', 'esbtp_unites_enseignement.code', 'esbtp_unites_enseignement.name')
            ->get();

        // Group by UE id → collect semestres
        $lieesMap = [];
        foreach ($pivotRows as $ue) {
            if (!isset($lieesMap[$ue->id])) {
                $lieesMap[$ue->id] = [
                    'id' => $ue->id,
                    'code' => $ue->code,
                    'name' => $ue->name,
                    'semestres' => [],
                ];
            }
            $lieesMap[$ue->id]['semestres'][] = $ue->pivot->semestre;
        }
        $liees = array_values($lieesMap);
        $lieesIds = array_keys($lieesMap);

        $disponibles = ESBTPUniteEnseignement::whereNotIn('id', $lieesIds)
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn($ue) => [
                'id' => $ue->id,
                'code' => $ue->code,
                'name' => $ue->name,
                'semestres' => [],
            ])->values();

        return response()->json(['liees' => $liees, 'disponibles' => $disponibles]);
    }

    /**
     * Synchroniser les UEs d'un parcours (multi-semestres via pivot).
     */
    public function syncUes(Request $request, ESBTPLMDParcours $parcours)
    {
        $items = $request->input('ues', []);

        // Detach all existing, then re-attach with multi-semestres
        $parcours->unitesEnseignement()->detach();

        $count = 0;
        foreach ($items as $item) {
            $ueId = $item['id'] ?? null;
            $semestres = $item['semestres'] ?? [];
            if (!$ueId || empty($semestres)) continue;

            foreach ($semestres as $sem) {
                $parcours->unitesEnseignement()->attach($ueId, [
                    'semestre' => $sem,
                    'ordre' => $item['ordre'] ?? 0,
                ]);
                $count++;
            }
        }

        return response()->json(['success' => true, 'message' => $count . ' lien(s) UE-semestre créé(s).']);
    }
}
