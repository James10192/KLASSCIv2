<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPRegleAcademique;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReglesAcademiquesController extends Controller
{
    public function index()
    {
        return view('esbtp.reinscription.regles', [
            'regles' => ESBTPRegleAcademique::query()->orderBy('niveau')->orderBy('filiere')->get(),
            'niveaux' => ESBTPNiveauEtude::query()->where('is_active', true)->orderBy('name')->get(),
            'filieres' => ESBTPFiliere::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request, true);
        $regle = ESBTPRegleAcademique::firstOrCreate(
            ['niveau' => $validated['niveau'], 'filiere' => $validated['filiere']],
            $validated
        );
        if (! $regle->wasRecentlyCreated) {
            throw ValidationException::withMessages([
                'niveau' => 'Une règle existe déjà pour ce niveau et cette filière. Modifiez-la plutôt.',
            ]);
        }

        return $this->respond($request, $regle, 'Règle académique créée.');
    }

    public function update(Request $request, int $id)
    {
        $regle = ESBTPRegleAcademique::findOrFail($id);
        $regle->update($this->validatedData($request, false));

        return $this->respond($request, $regle->fresh(), 'Règle académique mise à jour.');
    }

    public function destroy(Request $request, int $id)
    {
        ESBTPRegleAcademique::findOrFail($id)->delete();

        return $request->expectsJson()
            ? response()->json(['id' => $id, 'message' => 'Règle académique supprimée.'])
            : back()->with('success', 'Règle académique supprimée.');
    }

    private function validatedData(Request $request, bool $creation): array
    {
        $fields = [
            'moyenne_passage' => ['required', 'numeric', 'between:0,20'],
            'moyenne_rattrapage' => ['required', 'numeric', 'between:0,20', 'lt:moyenne_passage'],
            'max_matieres_rattrapage' => ['required', 'integer', 'min:0'],
            'max_redoublements' => ['required', 'integer', 'min:0'],
            'autoriser_redoublement' => ['required', 'boolean'],
            'actif' => ['required', 'boolean'],
            'conditions_speciales' => ['nullable', 'string', 'max:2000'],
        ];
        if ($creation) {
            $fields = [
                'niveau' => ['required', 'string', Rule::in(ESBTPNiveauEtude::where('is_active', true)->pluck('name')->all())],
                'filiere' => ['required', 'string', Rule::in(ESBTPFiliere::where('is_active', true)->pluck('name')->all())],
            ] + $fields;
        }

        return $request->validate($fields);
    }

    private function respond(Request $request, ESBTPRegleAcademique $regle, string $message)
    {
        return $request->expectsJson()
            ? response()->json(['regle' => $regle, 'message' => $message])
            : back()->with('success', $message);
    }
}
