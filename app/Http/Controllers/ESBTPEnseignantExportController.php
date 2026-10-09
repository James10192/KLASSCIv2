<?php

namespace App\Http\Controllers;

use App\Domain\Exports\Reports\EnseignantsReport;
use App\Domain\Notifications\PhoneFormatter;
use App\Models\ESBTPTeacher;
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
        ]);

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

        $rows = $query->orderBy('id')->get()->map(function (ESBTPTeacher $teacher): array {
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

        $labels = [];
        if (! empty($filters['search'])) $labels['Recherche'] = $filters['search'];
        if (! empty($filters['status'])) $labels['Statut'] = $filters['status'] === 'active' ? 'Actif' : 'Inactif';
        if (! empty($filters['specialization'])) $labels['Spécialisation'] = $filters['specialization'];

        return new EnseignantsReport($rows, $labels);
    }
}
