from pathlib import Path
import re

ROOT = Path('.')


def replace_once(path: str, old: str, new: str) -> None:
    p = ROOT / path
    text = p.read_text()
    if old not in text:
        raise SystemExit(f'Anchor not found in {path}: {old[:120]!r}')
    p.write_text(text.replace(old, new, 1))


def insert_after_regex(path: str, pattern: str, addition: str) -> None:
    p = ROOT / path
    text = p.read_text()
    match = re.search(pattern, text, flags=re.S)
    if not match:
        raise SystemExit(f'Regex anchor not found in {path}: {pattern}')
    p.write_text(text[:match.end()] + addition + text[match.end():])


# 1) Le suivi distingue une evaluation réellement sans matière d'une matière hors maquette,
#    garde chaque evaluation orpheline séparée, y compris si elle est future, et expose
#    uniquement les matières prévues par la maquette comme cibles de réparation.
replace_once(
    'app/Domain/AcademicPilotage/Services/AcademicNoteCoverageService.php',
    """        $orphanRows = $evaluations
            ->filter(fn (ESBTPEvaluation $evaluation) => ! $subjects->contains('id', (int) $evaluation->matiere_id))
            ->groupBy(fn (ESBTPEvaluation $evaluation) => (int) $evaluation->matiere_id)
            ->map(function (Collection $items) use ($indexPour, $entries, $enseignants): array {
                $evaluation = $items->first();
                $matiere = $evaluation?->matiere;
                // Relation absente (ancienne matière soft-deleted ou donnée
                // historique) : on tente encore le vrai libellé avant un ID.
                if (! $matiere && $evaluation?->matiere_id) {
                    $matiere = ESBTPMatiere::withTrashed()->find((int) $evaluation->matiere_id);
                }

                return $this->subjectRow($matiere, $items, collect(), $indexPour, $entries, true, $enseignants);
            })
            ->values();
""",
    """        // Une evaluation sans `matiere_id` n'est PAS une « matiere hors
        // maquette ». C'est une donnée incomplète à réparer. Surtout, deux
        // evaluations sans matière ne doivent pas être fusionnées sous la clé
        // entière 0 : chacune doit rester identifiable et réparable.
        $orphanRows = $evaluations
            ->concat($futureEvaluations)
            ->filter(fn (ESBTPEvaluation $evaluation) => ! $subjects->contains('id', (int) $evaluation->matiere_id))
            ->groupBy(fn (ESBTPEvaluation $evaluation) => $evaluation->matiere_id
                ? 'matiere:'.(int) $evaluation->matiere_id
                : 'evaluation:'.(int) $evaluation->id)
            ->map(function (Collection $items) use ($evaluations, $futureEvaluations, $subjects, $indexPour, $entries, $enseignants): array {
                /** @var ESBTPEvaluation|null $evaluation */
                $evaluation = $items->first();
                $matiere = $evaluation?->matiere;

                // Relation absente (ancienne matière soft-deleted ou donnée
                // historique) : on tente encore le vrai libellé avant un ID.
                if (! $matiere && $evaluation?->matiere_id) {
                    $matiere = ESBTPMatiere::withTrashed()->find((int) $evaluation->matiere_id);
                }

                $ids = $items->pluck('id')->map(fn ($id) => (int) $id)->all();
                $currentItems = $evaluations->whereIn('id', $ids)->values();
                $futureItems = $futureEvaluations->whereIn('id', $ids)->values();
                $row = $this->subjectRow($matiere, $currentItems, $futureItems, $indexPour, $entries, true, $enseignants);

                if ($evaluation && ! $evaluation->matiere_id) {
                    $titre = trim((string) ($evaluation->titre ?: $evaluation->type ?: 'Évaluation'));
                    $row['name'] = 'Évaluation sans matière — '.$titre;
                    $row['orphan_reason'] = 'evaluation_sans_matiere';
                    $row['evaluation_id'] = (int) $evaluation->id;
                    $row['repair_candidates'] = $subjects
                        ->map(fn (ESBTPMatiere $subject): array => [
                            'id' => (int) $subject->id,
                            'name' => trim(($subject->code ? $subject->code.' · ' : '').$subject->name),
                        ])
                        ->values()
                        ->all();
                } else {
                    $row['orphan_reason'] = 'hors_maquette';
                    $row['evaluation_id'] = $evaluation ? (int) $evaluation->id : null;
                    $row['repair_candidates'] = [];
                }

                return $row;
            })
            ->values();
"""
)

replace_once(
    'app/Domain/AcademicPilotage/Services/AcademicNoteCoverageService.php',
    """        $matiereId = $subject?->id ?? ($evaluations->first()?->matiere_id ? (int) $evaluations->first()->matiere_id : null);

        return [
""",
    """        $sourceEvaluation = $evaluations->first() ?? $futureEvaluations->first();
        $matiereId = $subject?->id ?? ($sourceEvaluation?->matiere_id ? (int) $sourceEvaluation->matiere_id : null);

        return [
"""
)

# 2) Endpoint web de réparation. Il ne change QUE les evaluations actuellement
#    sans matière et ne propose/autorise qu'une matière attendue par la maquette
#    de cette classe et de cette période. Les notes dénormalisées suivent aussi.
replace_once(
    'app/Http/Controllers/ESBTPEvaluationController.php',
    "use Illuminate\\Support\\Facades\\Auth;\n",
    "use Illuminate\\Support\\Facades\\Auth;\nuse Illuminate\\Support\\Facades\\DB;\n"
)

controller_anchor = """    /**
     * Quick edit (titre + barème + coefficient seulement).
"""
controller_method = r'''    /**
     * Répare une ancienne évaluation créée sans matière.
     *
     * Ce n'est pas un éditeur générique : dès qu'une matière existe déjà,
     * l'action refuse. La cible doit appartenir à la maquette attendue pour la
     * classe et la période de l'évaluation, afin que le bouton de réparation ne
     * puisse pas créer une nouvelle incohérence.
     */
    public function rattacherMatiere(Request $request, ESBTPEvaluation $evaluation): JsonResponse
    {
        $validated = $request->validate([
            'matiere_id' => 'required|integer|exists:esbtp_matieres,id',
        ], [
            'matiere_id.required' => 'Choisissez la matière à rattacher.',
            'matiere_id.exists' => 'La matière choisie n’existe plus.',
        ]);

        if ($evaluation->matiere_id !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Cette évaluation est déjà rattachée à une matière. Utilisez l’édition complète pour la déplacer.',
            ], 409);
        }

        $evaluation->loadMissing('classe');
        if (! $evaluation->classe) {
            return response()->json([
                'success' => false,
                'message' => 'La classe de cette évaluation est introuvable.',
            ], 422);
        }

        $attendu = app(\App\Domain\AcademicPilotage\Services\ExpectedSubjectsResolver::class)
            ->forClasse($evaluation->classe, (string) $evaluation->periode);
        $cible = $attendu['subjects']->firstWhere('id', (int) $validated['matiere_id']);

        if (! $cible) {
            return response()->json([
                'success' => false,
                'message' => 'Cette matière n’est pas prévue dans la maquette de cette classe pour cette période.',
            ], 422);
        }

        $coordonneesAvant = [
            'classe_id' => $evaluation->classe_id,
            'matiere_id' => null,
            'periode' => $evaluation->periode,
            'annee_universitaire_id' => $evaluation->annee_universitaire_id,
        ];
        $notesDeplacees = $evaluation->notes()->count();

        DB::transaction(function () use ($evaluation, $cible): void {
            $evaluation->matiere_id = (int) $cible->id;
            $evaluation->updated_by = Auth::id();
            $evaluation->save();

            // `esbtp_notes.matiere_id` est une copie dénormalisée : corriger
            // seulement l'évaluation laisserait le calcul et le bulletin sur
            // deux matières différentes.
            ESBTPNote::where('evaluation_id', $evaluation->id)
                ->update(['matiere_id' => (int) $cible->id]);
        });

        $recalcul = RecalculApresDeplacement::pour($evaluation, $coordonneesAvant, Auth::id());

        \Log::warning('Évaluation sans matière réparée depuis le suivi des notes', [
            'evaluation_id' => (int) $evaluation->id,
            'matiere_id' => (int) $cible->id,
            'matiere' => $cible->name,
            'notes_deplacees' => $notesDeplacees,
            'user_id' => Auth::id(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Évaluation rattachée à « '.$cible->name.' ».',
            'evaluation_id' => (int) $evaluation->id,
            'matiere_id' => (int) $cible->id,
            'matiere' => $cible->name,
            'notes_deplacees' => $notesDeplacees,
            'recalculs_tentes' => $recalcul['recalculs_tentes'],
            'recalculs_en_echec' => $recalcul['echecs'],
            'warning' => $recalcul['echecs'] > 0
                ? 'La matière est rattachée, mais certaines moyennes n’ont pas pu être recalculées. Relancez le recalcul de la classe.'
                : null,
        ]);
    }

'''
replace_once(
    'app/Http/Controllers/ESBTPEvaluationController.php',
    controller_anchor,
    controller_method + controller_anchor
)

# 3) Route dédiée et permission d'écriture explicite. Le groupe parent autorise
#    aussi la lecture ; l'action de réparation ne doit jamais hériter de ce droit.
routes = ROOT / 'routes/web.php'
text = routes.read_text()
needle = "Route::patch('/{evaluation}/quick-update', [ESBTPEvaluationController::class, 'quickUpdate'])"
pos = text.find(needle)
if pos == -1:
    raise SystemExit('quick-update route anchor not found')
end = text.find("->name('quick-update');", pos)
if end == -1:
    raise SystemExit('quick-update route end not found')
end += len("->name('quick-update');")
addition = """

    // Réparation ciblée des anciennes évaluations créées sans matière.
    Route::patch('/{evaluation}/rattacher-matiere', [ESBTPEvaluationController::class, 'rattacherMatiere'])
        ->middleware(['permission:evaluations.edit|edit_evaluations', 'throttle:30,1'])
        ->whereNumber('evaluation')
        ->name('rattacher-matiere');"""
routes.write_text(text[:end] + addition + text[end:])

# 4) Le composant reçoit une URL de réparation uniquement si l'utilisateur a
#    réellement le droit d'éditer les évaluations.
replace_once(
    'resources/views/esbtp/partials/_couverture-notes.blade.php',
    """    $_cvnPilotage = ($lienPilotage ?? true) && \\Illuminate\\Support\\Facades\\Route::has('esbtp.pilotage-academique.index')
        ? route('esbtp.pilotage-academique.index')
        : null;
    $_cvnConfig = [
""",
    """    $_cvnPilotage = ($lienPilotage ?? true) && \\Illuminate\\Support\\Facades\\Route::has('esbtp.pilotage-academique.index')
        ? route('esbtp.pilotage-academique.index')
        : null;
    $_cvnCanRepairEvaluation = auth()->check()
        && auth()->user()->hasAnyPermission(['evaluations.edit', 'edit_evaluations'])
        && \\Illuminate\\Support\\Facades\\Route::has('esbtp.evaluations.rattacher-matiere');
    $_cvnRepairModele = $_cvnCanRepairEvaluation
        ? route('esbtp.evaluations.rattacher-matiere', ['evaluation' => '__EVALUATION__'])
        : null;
    $_cvnConfig = [
"""
)
replace_once(
    'resources/views/esbtp/partials/_couverture-notes.blade.php',
    """        'urlPilotage' => $_cvnPilotage,
        'replie' => (bool) ($replie ?? true),
""",
    """        'urlPilotage' => $_cvnPilotage,
        'rattacherMatiereModele' => $_cvnRepairModele,
        'replie' => (bool) ($replie ?? true),
"""
)

repair_markup = '''                            <template x-if="peutRattacherMatiere(m)">
                                <div class="cvn-repair">
                                    <select :value="matiereChoisie[m.evaluation_id] || ''"
                                            @change="matiereChoisie[m.evaluation_id] = Number($event.target.value) || null"
                                            :disabled="rattachementEnCours === m.evaluation_id"
                                            aria-label="Choisir la matière de l'évaluation">
                                        <option value="">Choisir la matière…</option>
                                        <template x-for="cible in m.repair_candidates" :key="`repair-${m.evaluation_id}-${cible.id}`">
                                            <option :value="cible.id" x-text="cible.name"></option>
                                        </template>
                                    </select>
                                    <button type="button" class="cvn-action cvn-action--repair"
                                            :disabled="!matiereChoisie[m.evaluation_id] || rattachementEnCours === m.evaluation_id"
                                            @click="rattacherMatiere(m)">
                                        <i class="fas" :class="rattachementEnCours === m.evaluation_id ? 'fa-circle-notch fa-spin' : 'fa-link'"></i>
                                        <span x-text="rattachementEnCours === m.evaluation_id ? 'Rattachement…' : 'Rattacher à la matière'"></span>
                                    </button>
                                </div>
                            </template>
'''
replace_once(
    'resources/views/esbtp/partials/_couverture-notes.blade.php',
    """                            <template x-if="m.saisie_url && m.statut !== 'programmee'">
                                <button type="button" class="cvn-action" @click="ouvrirSaisie(m)">
                                    <i class="fas fa-pen-to-square"></i> Ouvrir la saisie
                                </button>
                            </template>
""",
    """                            <template x-if="m.saisie_url && m.statut !== 'programmee'">
                                <button type="button" class="cvn-action" @click="ouvrirSaisie(m)">
                                    <i class="fas fa-pen-to-square"></i> Ouvrir la saisie
                                </button>
                            </template>
""" + repair_markup
)

replace_once(
    'resources/views/esbtp/partials/_couverture-notes.blade.php',
    ".cvn-action { margin-left: auto; border: 1px solid rgba(4,83,203,.28); border-radius: 6px; padding: .25rem .45rem; color: #0453cb; background: #fff; text-decoration: none; font-size: .72rem; font-weight: 700; white-space: nowrap; }\n",
    ".cvn-action { margin-left: auto; border: 1px solid rgba(4,83,203,.28); border-radius: 6px; padding: .25rem .45rem; color: #0453cb; background: #fff; text-decoration: none; font-size: .72rem; font-weight: 700; white-space: nowrap; }\n"
    ".cvn-repair { display: flex; align-items: center; gap: .35rem; flex-wrap: wrap; width: 100%; justify-content: flex-end; }\n"
    ".cvn-repair select { min-width: 220px; max-width: 340px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #1e293b; padding: .3rem .45rem; font-size: .72rem; }\n"
    ".cvn-action--repair { margin-left: 0; background: rgba(4,83,203,.05); }\n"
    ".cvn-action:disabled, .cvn-repair select:disabled { opacity: .55; cursor: not-allowed; }\n"
)
replace_once(
    'resources/views/esbtp/partials/_couverture-notes.blade.php',
    """    .cvn-action { margin-left: 0; min-height: 34px; }
""",
    """    .cvn-action { margin-left: 0; min-height: 34px; }
    .cvn-repair { justify-content: flex-start; }
    .cvn-repair select { min-width: min(100%, 260px); max-width: 100%; }
"""
)

# 5) Alpine : la réparation est volontairement très étroite. Une fois réussie,
#    on force le recalcul du suivi pour que la ligne disparaisse immédiatement.
replace_once(
    'resources/views/esbtp/partials/_couverture-notes-script.blade.php',
    """            urlPilotage: config.urlPilotage || null,
            replie: config.replie !== false,
""",
    """            urlPilotage: config.urlPilotage || null,
            rattacherMatiereModele: config.rattacherMatiereModele || null,
            replie: config.replie !== false,
"""
)
replace_once(
    'resources/views/esbtp/partials/_couverture-notes-script.blade.php',
    """            filtre: 'tout',
            _cache: {},
""",
    """            filtre: 'tout',
            matiereChoisie: {},
            rattachementEnCours: null,
            _cache: {},
"""
)

js_methods = r'''            peutRattacherMatiere(matiere) {
                return !!(
                    this.rattacherMatiereModele
                    && matiere
                    && matiere.orphan_reason === 'evaluation_sans_matiere'
                    && matiere.evaluation_id
                    && Array.isArray(matiere.repair_candidates)
                    && matiere.repair_candidates.length > 0
                );
            },

            async rattacherMatiere(matiere) {
                if (!this.peutRattacherMatiere(matiere)) return;
                var matiereId = Number(this.matiereChoisie[matiere.evaluation_id] || 0);
                if (!matiereId) {
                    this.erreur = 'Choisissez d’abord la matière à rattacher.';
                    return;
                }

                this.rattachementEnCours = matiere.evaluation_id;
                this.erreur = '';
                try {
                    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    var url = this.rattacherMatiereModele.replace('__EVALUATION__', String(matiere.evaluation_id));
                    var res = await fetch(url, {
                        method: 'PATCH',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ matiere_id: matiereId }),
                    });
                    var payload = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        var validation = payload.errors && payload.errors.matiere_id && payload.errors.matiere_id[0];
                        throw new Error(validation || payload.message || 'Impossible de rattacher cette évaluation.');
                    }

                    delete this.matiereChoisie[matiere.evaluation_id];
                    await this.charger(true);
                } catch (err) {
                    this.erreur = err.message || 'Impossible de rattacher cette évaluation.';
                } finally {
                    this.rattachementEnCours = null;
                }
            },

'''
replace_once(
    'resources/views/esbtp/partials/_couverture-notes-script.blade.php',
    """            libelleStatut(matiere) {
""",
    js_methods + """            libelleStatut(matiere) {
"""
)
replace_once(
    'resources/views/esbtp/partials/_couverture-notes-script.blade.php',
    """            libelleStatut(matiere) {
                switch (matiere.statut) {
""",
    """            libelleStatut(matiere) {
                if (matiere.orphan_reason === 'evaluation_sans_matiere') return 'Matière non rattachée';
                switch (matiere.statut) {
"""
)

# 6) Tests : nom explicite + séparation des orphelines + réparation réelle.
test_path = ROOT / 'tests/Feature/AcademicPilotage/EvaluationSansMatiereRepairTest.php'
test_path.write_text(r'''<?php

namespace Tests\Feature\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Http\Controllers\ESBTPEvaluationController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

class EvaluationSansMatiereRepairTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    public function test_le_suivi_nomme_et_separe_les_evaluations_sans_matiere(): void
    {
        $this->monterLaClasse();
        $this->etudiantInscrit();
        $cible = $this->matiereConfiguree();

        $une = ESBTPEvaluation::factory()->create([
            'titre' => 'Interrogation béton',
            'matiere_id' => null,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'date_evaluation' => now()->subDay(),
            'status' => 'completed',
        ]);
        $deux = ESBTPEvaluation::factory()->create([
            'titre' => 'Devoir de synthèse',
            'matiere_id' => null,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'date_evaluation' => now()->subDay(),
            'status' => 'completed',
        ]);

        $resultat = app(AcademicNoteCoverageService::class)->summarize(
            $this->annee->id, 'semestre1', 'BTS', $this->classe->id
        );

        $orphelines = collect($resultat['subjects'])
            ->where('orphan_reason', 'evaluation_sans_matiere')
            ->values();

        self::assertCount(2, $orphelines);
        self::assertSame(
            ['Évaluation sans matière — Interrogation béton', 'Évaluation sans matière — Devoir de synthèse'],
            $orphelines->pluck('name')->sort()->values()->all()
        );
        self::assertEqualsCanonicalizing([$une->id, $deux->id], $orphelines->pluck('evaluation_id')->all());
        self::assertSame([$cible->id], collect($orphelines->first()['repair_candidates'])->pluck('id')->all());
    }

    public function test_la_reparation_rattache_evaluation_et_notes_a_une_matiere_de_la_maquette(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();
        $cible = $this->matiereConfiguree();
        $evaluation = ESBTPEvaluation::factory()->create([
            'titre' => 'Ancien devoir',
            'matiere_id' => null,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'date_evaluation' => now()->subDay(),
            'status' => 'completed',
            'bareme' => 20,
            'coefficient' => 1,
        ]);
        ESBTPNote::create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $etudiant->id,
            'matiere_id' => null,
            'classe_id' => $this->classe->id,
            'note' => 12,
            'is_absent' => false,
        ]);

        $user = User::findOrFail(1);
        $this->actingAs($user);
        $request = Request::create('/repair', 'PATCH', ['matiere_id' => $cible->id]);
        $request->setUserResolver(fn () => $user);

        $response = app(ESBTPEvaluationController::class)->rattacherMatiere($request, $evaluation);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($cible->id, $evaluation->fresh()->matiere_id);
        self::assertSame($cible->id, ESBTPNote::where('evaluation_id', $evaluation->id)->value('matiere_id'));
    }

    public function test_la_route_de_reparation_exige_le_droit_d_editer_les_evaluations(): void
    {
        $route = Route::getRoutes()->getByName('esbtp.evaluations.rattacher-matiere');

        self::assertNotNull($route);
        self::assertContains('permission:evaluations.edit|edit_evaluations', $route->gatherMiddleware());
    }
}
''')

print('Evaluation orphan repair patch applied.')
