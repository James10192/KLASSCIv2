<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Reports\EnseignantsReport;
use App\Domain\Notifications\PhoneFormatter;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Services\Enseignants\AnnuaireEnseignantsBulletins;
use App\Services\ExportRenderer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ESBTPEnseignantExportController extends Controller
{
    private const PDF_MAX_ROWS = 1000;
    private const PDF_MAX_AFFECTATIONS = 1000;
    private const EXCEL_MAX_ROWS = 50000;

    // Une ValidationException levee sur un lien GET redirigeait silencieusement
    // vers /esbtp/enseignants : le navigateur n'affichait jamais le PDF ni l'erreur.
    // Chaque format retourne maintenant une page 422 explicite et navigable.
    public function previewPdf(Request $request, ExportRenderer $renderer)
    {
        return $this->deliver($request, $renderer, 'preview');
    }

    public function downloadPdf(Request $request, ExportRenderer $renderer)
    {
        return $this->deliver($request, $renderer, 'pdf');
    }

    public function downloadExcel(Request $request, ExportRenderer $renderer)
    {
        return $this->deliver($request, $renderer, 'excel');
    }

    private function deliver(Request $request, ExportRenderer $renderer, string $format)
    {
        try {
            $report = $this->report(
                $request,
                $format === 'excel' ? self::EXCEL_MAX_ROWS : self::PDF_MAX_ROWS
            );

            return match ($format) {
                'preview' => $renderer->pdfPreview($report),
                'pdf' => $renderer->pdfDownload($report),
                'excel' => $renderer->excelDownload($report),
            };
        } catch (ValidationException $e) {
            return response()->view('esbtp.enseignants.export-error', [
                'messages' => array_values(array_merge(...array_values($e->errors()))),
            ], 422)->header('Cache-Control', 'no-store');
        }
    }

    private function report(Request $request, int $max): EnseignantsReport
    {
        $this->authorize('teachers.view');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:active,inactive'],
            'specialization' => ['nullable', 'string', 'max:150'],
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
            'classe_id' => ['nullable', 'integer', 'exists:esbtp_classes,id'],
            'semestre' => ['nullable', 'integer', 'in:1,2'],
            'vue' => ['nullable', 'in:synthese,annuaire,rapprochement,affectations'],
        ]);

        $vue = $filters['vue'] ?? 'synthese';
        if ($max === self::PDF_MAX_ROWS && $vue === 'affectations' && empty($filters['classe_id'])) {
            throw ValidationException::withMessages([
                'vue' => 'Pour imprimer les matières et les professeurs, choisissez d’abord une classe. Le panorama global est disponible dans la vue Synthèse.',
            ]);
        }

        $anneeId = $filters['annee_universitaire_id'] ?? ESBTPAnneeUniversitaire::anneeCourante()?->id;
        if (! $anneeId || ! $annee = ESBTPAnneeUniversitaire::query()->find($anneeId)) {
            throw ValidationException::withMessages([
                'annee_universitaire_id' => 'Sélectionnez une année universitaire existante pour exporter les professeurs des bulletins.',
            ]);
        }

        // L'annuaire enregistre n'est pas rattache a une annee.
        // Le filtre d'annee s'applique aux affectations des bulletins, pas aux comptes.
        $query = ESBTPTeacher::query()->with('user');
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['specialization'])) {
            $query->where('specialization', 'like', '%' . $filters['specialization'] . '%');
        }

        $count = (clone $query)->count();
        if ($count > $max) {
            throw ValidationException::withMessages([
                'export' => "La liste comporte {$count} enseignants (limite {$max}). Affinez les filtres.",
            ]);
        }

        $enregistres = $query->orderBy('id')->get()->map(function (ESBTPTeacher $teacher): array {
            $user = $teacher->user;
            return [
                'matricule' => (string) ($teacher->matricule ?: '—'),
                'nom' => (string) ($user->name ?? '—'),
                'telephone' => PhoneFormatter::toReadable($user?->phone ?: $teacher->phone) ?? '—',
                'email' => (string) ($user->email ?? $teacher->email ?? '—'),
                'specialisation' => (string) ($teacher->specialization ?: '—'),
                'regime' => match ($teacher->regime) {
                    'permanent' => 'Permanent',
                    'vacataire' => 'Vacataire',
                    'consultant' => 'Consultant',
                    default => ucfirst((string) ($teacher->regime ?? '—')),
                },
                'statut' => $teacher->status === 'active' ? 'Actif' : 'Inactif',
            ];
        })->all();

        $annuaire = app(AnnuaireEnseignantsBulletins::class);
        $affectations = $annuaire->affectations(
            (int) $annee->id,
            ! empty($filters['classe_id']) ? (int) $filters['classe_id'] : null,
            ! empty($filters['semestre']) ? (int) $filters['semestre'] : null
        );
        // Les noms libres n'ont pas de statut ni de specialisation : ne pas les
        // introduire dans un export filtre sur ces proprietes inconnues.
        $inclureManuels = empty($filters['status']) && empty($filters['specialization']);
        $resultat = $annuaire->consolider(
            $enregistres, $affectations, $inclureManuels, $filters['search'] ?? null
        );

        // Seule la vue de detail imprime chaque affectation. Les autres
        // editions restent lisibles meme si l'etablissement a des milliers de
        // libelles (synthese = comptages, annuaire = fiches, rapprochement = noms).
        $totalAffectations = count($resultat['affectations']);
        if ($max === self::PDF_MAX_ROWS && $vue === 'affectations'
            && $totalAffectations > self::PDF_MAX_AFFECTATIONS) {
            throw ValidationException::withMessages([
                'export' => "Cette sélection comporte {$totalAffectations} affectations (limite ".self::PDF_MAX_AFFECTATIONS."). Choisissez un semestre pour alléger ce document.",
            ]);
        }

        $labels = [
            'Document PDF' => [
                'synthese' => 'Synthèse et repères par classe',
                'annuaire' => 'Annuaire officiel (fiches KLASSCI)',
                'rapprochement' => 'Noms des bulletins à rapprocher',
                'affectations' => 'Affectations détaillées par classe',
            ][$vue],
            'Année universitaire des bulletins' => $annee->display_name,
        ];
        if (! empty($filters['classe_id'])) {
            $labels['Classe des bulletins'] = ESBTPClasse::query()->whereKey($filters['classe_id'])->value('name') ?? 'Classe #'.$filters['classe_id'];
        }
        if (! empty($filters['semestre'])) $labels['Semestre des bulletins'] = 'S'.$filters['semestre'];
        if (! empty($filters['search'])) $labels['Recherche'] = $filters['search'];
        if (! empty($filters['status'])) $labels['Statut'] = $filters['status'] === 'active' ? 'Actif' : 'Inactif';
        if (! empty($filters['specialization'])) $labels['Spécialisation'] = $filters['specialization'];

        return new EnseignantsReport($resultat['rows'], $labels, $resultat['affectations'], $totalAffectations, $vue);
    }
}
