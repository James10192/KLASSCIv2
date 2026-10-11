<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Reports\EnseignantsReport;
use App\Domain\Notifications\PhoneFormatter;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPAnneeUniversitaire;
use App\Services\Enseignants\AnnuaireEnseignantsBulletins;
use App\Services\ExportRenderer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ESBTPEnseignantExportController extends Controller
{
    private const PDF_MAX_ROWS = 1000;
    private const EXCEL_MAX_ROWS = 50000;

    public function previewPdf(Request $request, ExportRenderer $renderer)
    {
        return $renderer->pdfPreview($this->report($request, self::PDF_MAX_ROWS));
    }

    public function downloadPdf(Request $request, ExportRenderer $renderer)
    {
        return $renderer->pdfDownload($this->report($request, self::PDF_MAX_ROWS));
    }

    public function downloadExcel(Request $request, ExportRenderer $renderer)
    {
        return $renderer->excelDownload($this->report($request, self::EXCEL_MAX_ROWS));
    }

    private function report(Request $request, int $max): EnseignantsReport
    {
        $this->authorize('teachers.view');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:active,inactive'],
            'specialization' => ['nullable', 'string', 'max:150'],
            'annee_universitaire_id' => ['nullable', 'integer', 'exists:esbtp_annee_universitaires,id'],
        ]);

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
        $affectations = $annuaire->affectations((int) $annee->id);
        // Les noms libres n'ont pas de statut ni de specialisation : ne pas les
        // introduire dans un export filtre sur ces proprietes inconnues.
        $inclureManuels = empty($filters['status']) && empty($filters['specialization']);
        $resultat = $annuaire->consolider(
            $enregistres, $affectations, $inclureManuels, $filters['search'] ?? null
        );

        $volume = count($resultat['rows']) + count($resultat['affectations']);
        if ($volume > $max) {
            throw ValidationException::withMessages([
                'export' => "L'annuaire et le détail des bulletins comportent {$volume} lignes (limite {$max}). Affinez les filtres ou utilisez Excel.",
            ]);
        }

        $labels = ['Année universitaire des bulletins' => $annee->display_name];
        if (! empty($filters['search'])) $labels['Recherche'] = $filters['search'];
        if (! empty($filters['status'])) $labels['Statut'] = $filters['status'] === 'active' ? 'Actif' : 'Inactif';
        if (! empty($filters['specialization'])) $labels['Spécialisation'] = $filters['specialization'];

        return new EnseignantsReport($resultat['rows'], $labels, $resultat['affectations']);
    }
}
