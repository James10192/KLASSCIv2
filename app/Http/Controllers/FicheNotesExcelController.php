<?php

namespace App\Http\Controllers;

use App\Domain\Notes\Classeur\ImportDuClasseur;
use App\Domain\Notes\Classeur\MatieresDuClasseur;
use App\Domain\Notes\FormatFeuilleViergeA4;
use App\Exports\NotesClasseurExport;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Fiche de notes Excel d'une classe : télécharger un classeur (une feuille
 * par matière), le remplir hors ligne, le réimporter. BTS et LMD.
 */
class FicheNotesExcelController extends Controller
{
    public function __construct(private MatieresDuClasseur $matieres, private ImportDuClasseur $import) {}

    public function show(ESBTPClasse $classe)
    {
        $lmd = ($classe->systeme_academique ?? '') === 'LMD';
        $annees = ESBTPAnneeUniversitaire::orderByDesc('id')->pluck('name', 'id')->all();
        $courante = ESBTPAnneeUniversitaire::where('is_current', 1)->value('id');

        $limites = FormatFeuilleViergeA4::LIMITES;
        $semestres = $this->semestres($classe, $lmd);
        // Les matières de chaque semestre : l'école peut ne télécharger qu'une matière.
        $matieresParSemestre = collect($semestres)->map(fn ($libelle, $periode) => $this->matieres->pour($classe, $periode)
            ->mapWithKeys(fn ($m) => [(string) $m->id => trim(($m->code ? $m->code.' · ' : '').$m->name)])->all())->all();

        return view('esbtp.notes.fiche-excel', compact('classe', 'lmd', 'annees', 'courante', 'limites', 'semestres', 'matieresParSemestre'));
    }

    /** @return array<string, string> */
    private function semestres(ESBTPClasse $classe, bool $lmd): array
    {
        $annee = (int) ($classe->niveauEtude?->year ?: 1);

        return $lmd
            ? ['semestre'.(2 * $annee - 1) => 'Semestre '.(2 * $annee - 1), 'semestre'.(2 * $annee) => 'Semestre '.(2 * $annee)]
            : ['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2'];
    }

    public function telecharger(Request $request, ESBTPClasse $classe)
    {
        $v = $request->validate([
            'annee_universitaire_id' => ['required', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'periode' => ['required', 'regex:/^semestre([1-9]|10)$/'],
            'matiere_id' => ['nullable', 'integer'],
        ]);
        [$orientation, $colonnes] = FormatFeuilleViergeA4::depuisRequete($request);
        $matieres = $this->matieres->pour($classe, $v['periode']);
        if (! empty($v['matiere_id'])) {
            $matieres = $matieres->where('id', (int) $v['matiere_id'])->values();
            abort_if($matieres->isEmpty(), 422, 'Cette matière n\'est pas au programme de la classe pour ce semestre.');
        }
        abort_if($matieres->isEmpty(), 422, 'Aucune matière configurée pour cette classe et ce semestre.');

        $nom = 'fiche-notes-'.Str::slug($classe->name).($matieres->count() === 1 ? '-'.Str::slug($matieres->first()->code ?: $matieres->first()->name) : '').'-'.$v['periode'].'.xlsx';

        return Excel::download(new NotesClasseurExport($classe->id, $matieres, $v['periode'], (int) $v['annee_universitaire_id'], $colonnes, $orientation), $nom);
    }

    public function simuler(Request $request, ESBTPClasse $classe): JsonResponse
    {
        return $this->traiter($request, $classe, false);
    }

    public function appliquer(Request $request, ESBTPClasse $classe): JsonResponse
    {
        return $this->traiter($request, $classe, true);
    }

    private function traiter(Request $request, ESBTPClasse $classe, bool $ecrire): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']]);
        try {
            $resultat = $ecrire
                ? $this->import->appliquer($request->file('file'), $classe->id)
                : $this->import->simuler($request->file('file'), $classe->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Fiche de notes Excel : import en échec', ['classe_id' => $classe->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Le fichier n\'a pas pu être lu. Vérifiez qu\'il s\'agit bien de la fiche téléchargée depuis KLASSCI.'], 422);
        }

        if ($ecrire && ($resultat['applique'] ?? false)) {
            Log::info('Fiche de notes Excel importée', ['classe_id' => $classe->id, 'user_id' => auth()->id(), 'feuilles' => $resultat['feuilles']]);
        }

        return response()->json(['success' => true, 'data' => $resultat]);
    }
}
