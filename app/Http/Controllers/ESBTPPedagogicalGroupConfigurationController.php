<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Services\BulletinInlineConfigurationService;
use App\Services\LMDBulletinService;
use Illuminate\Http\Request;

/** Point d'entrée classe × année × semestre, respectant les deux moteurs académiques. */
class ESBTPPedagogicalGroupConfigurationController extends Controller
{
    public function __invoke(Request $request, BulletinInlineConfigurationService $bts, LMDBulletinService $lmd)
    {
        $validated = $request->validate([
            'classe_id' => ['nullable', 'integer', 'exists:esbtp_classes,id'],
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'periode' => ['nullable', 'string', 'max:20'],
        ]);

        $classes = ESBTPClasse::query()->with(['filiere', 'niveau'])->orderBy('name')->get()
            ->filter(fn (ESBTPClasse $item) => $item->systeme_academique === 'LMD'
                ? ($request->user()?->can('module.lmd.access') && $request->user()->can('lmd.structure.view'))
                : $request->user()?->can('bulletins.configure'));
        $annees = ESBTPAnneeUniversitaire::query()->orderByDesc('annee_debut')->get();
        if (isset($validated['classe_id']) && ! $classes->contains('id', (int) $validated['classe_id'])) {
            abort(403);
        }
        $classe = $classes->firstWhere('id', (int) ($validated['classe_id'] ?? 0)) ?? $classes->first();
        $annee = $annees->firstWhere('id', (int) ($validated['annee_universitaire_id'] ?? 0))
            ?? $annees->firstWhere('is_current', true) ?? $annees->first();

        $isLmd = $classe?->systeme_academique === 'LMD';
        if ($classe) {
            abort_unless($request->user()?->can($isLmd ? 'lmd.structure.view' : 'bulletins.configure'), 403);
        }
        if ($classe && $isLmd) {
            abort_unless($request->user()->can('module.lmd.access'), 403);
        }

        $periodes = $isLmd
            ? collect($classe->getSemestresLMD())->mapWithKeys(fn ($s) => ['semestre'.$s => 'S'.$s])->all()
            : ['semestre1' => 'S1', 'semestre2' => 'S2'];
        $periode = $validated['periode'] ?? array_key_first($periodes);
        abort_unless($periode !== null && array_key_exists($periode, $periodes), 422);

        $configurationBts = null;
        $unitesLmd = collect();
        if ($classe && $annee) {
            if ($isLmd) {
                $unitesLmd = $lmd->getUEsForSemestre($classe, (int) substr($periode, 8));
            } else {
                $configurationBts = $bts->data((int) $classe->id, (int) $annee->id, $periode);
            }
        }

        return view('esbtp.bulletins.configuration-groupe', compact(
            'classes', 'annees', 'classe', 'annee', 'isLmd', 'periodes', 'periode', 'configurationBts', 'unitesLmd'
        ));
    }
}
