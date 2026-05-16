<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Http\Requests\Tpe\StoreTpeDeclarationRequest;
use App\Http\Requests\Tpe\UpdateTpeDeclarationRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPTpeDeclaration;
use App\Services\LMD\Tpe\TpeValidationStrategy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Journal TPE — vue étudiant.
 *
 * L'étudiant déclare ses heures TPE par ECUE et par semaine.
 * Le statut initial dépend de la TpeValidationStrategy injectée (Option 2 / Option 3).
 *
 * Volontairement mince — orchestration seulement (rule no-god-code).
 */
class ESBTPTpeDeclarationController extends Controller
{
    public function __construct(
        private readonly TpeValidationStrategy $strategy,
    ) {}

    /**
     * Liste les déclarations de l'étudiant courant (année en cours).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $etudiant = $this->resolveStudent($request);
        if (! $etudiant instanceof ESBTPEtudiant) {
            return redirect()->route('dashboard')
                ->with('error', 'Profil étudiant introuvable.');
        }

        $classe = $etudiant->classe;
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $declarations = ESBTPTpeDeclaration::query()
            ->where('etudiant_id', $etudiant->id)
            ->when($annee, fn ($q) => $q->where('annee_universitaire_id', $annee->id))
            ->with(['matiere:id,name,unite_enseignement_id', 'matiere.uniteEnseignement:id,name'])
            ->orderByDesc('semaine_debut')
            ->orderBy('matiere_id')
            ->get();

        $ecues = $this->buildEcuesForClass($classe, $annee);
        $progress = $this->computeProgress($declarations, $ecues);

        return view('esbtp.tpe-journal.index', [
            'etudiant' => $etudiant,
            'classe' => $classe,
            'annee' => $annee,
            'declarations' => $declarations,
            'declarationsParSemaine' => $declarations->groupBy(fn ($d) => optional($d->semaine_debut)->toDateString()),
            'ecues' => $ecues,
            'progress' => $progress,
            'requiresValidation' => $this->strategy->requiresTeacherAction(),
            'maxHoursPerWeek' => (float) SettingsHelper::get('tpe.max_hours_per_week_per_ecue', 10),
            'windowWeeks' => (int) SettingsHelper::get('tpe.declaration_window_weeks', 2),
        ]);
    }

    /**
     * Crée une nouvelle déclaration. Le statut est dérivé de la Strategy injectée.
     */
    public function store(StoreTpeDeclarationRequest $request): RedirectResponse
    {
        $etudiant = $this->resolveStudent($request);
        if (! $etudiant) {
            return back()->with('error', 'Profil étudiant introuvable.');
        }

        try {
            ESBTPTpeDeclaration::create([
                'etudiant_id' => $etudiant->id,
                'matiere_id' => $request->validated('matiere_id'),
                'annee_universitaire_id' => $request->validated('annee_universitaire_id'),
                'semaine_debut' => $request->validated('semaine_debut'),
                'heures' => $request->validated('heures'),
                'description' => $request->validated('description'),
                'statut' => $this->strategy->initialStatut()->value,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);

            $msg = $this->strategy->requiresTeacherAction()
                ? 'Déclaration envoyée. En attente de validation par l\'enseignant.'
                : 'Déclaration enregistrée.';

            return back()->with('success', $msg);
        } catch (\Illuminate\Database\QueryException $e) {
            // UNIQUE composite (étudiant × ECUE × semaine × année)
            if (str_contains($e->getMessage(), 'tpe_decl_unique')) {
                return back()
                    ->withInput()
                    ->with('error', 'Vous avez déjà déclaré pour cette matière et cette semaine. Modifiez la déclaration existante.');
            }
            Log::error('TPE store failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Erreur lors de la création — réessayez.');
        }
    }

    /**
     * Met à jour heures/description (seulement si EN_ATTENTE — voir UpdateRequest::authorize()).
     */
    public function update(UpdateTpeDeclarationRequest $request, ESBTPTpeDeclaration $declaration): RedirectResponse
    {
        $declaration->update([
            'heures' => $request->validated('heures'),
            'description' => $request->validated('description'),
            'updated_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Déclaration mise à jour.');
    }

    /**
     * Supprime une déclaration. Autorisé uniquement si pas encore validée.
     */
    public function destroy(Request $request, ESBTPTpeDeclaration $declaration): RedirectResponse
    {
        $user = $request->user();
        $isOwner = $declaration->etudiant && $declaration->etudiant->user_id === $user?->id;

        if (! $isOwner || ! $declaration->statut->isEditableByStudent()) {
            abort(403, 'Cette déclaration ne peut plus être supprimée.');
        }

        $declaration->delete();

        return back()->with('success', 'Déclaration supprimée.');
    }

    // ===== Helpers privés (orchestration légère) =====

    private function resolveStudent(Request $request): ?ESBTPEtudiant
    {
        return ESBTPEtudiant::query()
            ->where('user_id', $request->user()?->id)
            ->first();
    }

    /**
     * Liste les ECUEs accessibles à la classe de l'étudiant (source canonique :
     * esbtp_planifications_academiques — rule klassci-classe-matieres.md).
     *
     * @return \Illuminate\Support\Collection<int, ESBTPMatiere>
     */
    private function buildEcuesForClass($classe, $annee)
    {
        if (! $classe || ! $annee) {
            return collect();
        }

        $matiereIds = ESBTPPlanificationAcademique::query()
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('annee_universitaire_id', $annee->id)
            ->where('is_active', true)
            ->whereNotNull('matiere_id')
            ->pluck('matiere_id')
            ->unique();

        if ($matiereIds->isEmpty()) {
            return collect();
        }

        return ESBTPMatiere::query()
            ->whereIn('id', $matiereIds)
            ->whereNotNull('unite_enseignement_id') // LMD strict : ECUE only
            ->orderBy('name')
            ->get(['id', 'name', 'unite_enseignement_id']);
    }

    /**
     * Calcule la progression heures déclarées / heures attendues (volume_horaire_tpe planifié).
     *
     * @return array{declared_total: float, expected_total: int, pct: float}
     */
    private function computeProgress($declarations, $ecues): array
    {
        $declaredValide = (float) $declarations
            ->where('statut.value', \App\Enums\TpeDeclarationStatut::VALIDE->value)
            ->sum('heures');

        if ($ecues->isEmpty()) {
            return [
                'declared_total' => $declaredValide,
                'expected_total' => 0,
                'pct' => 0.0,
            ];
        }

        $expected = (int) ESBTPPlanificationAcademique::query()
            ->whereIn('matiere_id', $ecues->pluck('id'))
            ->where('is_active', true)
            ->sum('volume_horaire_tpe');

        $pct = $expected > 0 ? min(100.0, round(($declaredValide / $expected) * 100, 1)) : 0.0;

        return [
            'declared_total' => $declaredValide,
            'expected_total' => $expected,
            'pct' => $pct,
        ];
    }
}
