# Méthode exportToPDF() — ESBTPAttendanceController (BASELINE)

## Méthode PHP complète

```php
/**
 * Exporte le rapport d'absences d'un étudiant en PDF.
 */
public function exportToPDF(Request $request)
{
    $etudiant = auth()->user()->etudiant;
    if (!$etudiant) {
        abort(403, 'Profil étudiant non trouvé');
    }

    $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();
    $inscription = null;
    if ($anneeCourante) {
        $inscription = $etudiant->inscriptions()
            ->where('status', 'active')
            ->where('annee_universitaire_id', $anneeCourante->id)
            ->with(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire'])
            ->first();
    }

    $query = ESBTPAttendance::with(['seanceCours.matiere'])
        ->where('etudiant_id', $etudiant->id)
        ->whereIn('statut', ['absent', 'excuse']);

    if ($request->filled('date_debut')) {
        $query->whereDate('date', '>=', $request->date_debut);
    }
    if ($request->filled('date_fin')) {
        $query->whereDate('date', '<=', $request->date_fin);
    }

    $absences = $query->orderBy('date', 'desc')->get();

    $totalAbsences = $absences->count();
    $stats = [
        'total'          => $totalAbsences,
        'justifiees'     => $absences->where('is_justified', true)->count(),
        'non_justifiees' => $absences->where('statut', 'absent')->where('is_justified', false)->count(),
    ];

    $schoolInfo  = \App\Helpers\SettingsHelper::getSchoolInfo();
    $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
    $logoBase64  = $this->prepareLogoForPdf($schoolInfo['logo'] ?? '');

    $data = [
        'etudiant'    => $etudiant,
        'inscription' => $inscription,
        'absences'    => $absences,
        'stats'       => $stats,
        'schoolInfo'  => $schoolInfo,
        'pdfSettings' => $pdfSettings,
        'logoBase64'  => $logoBase64,
        'generatedAt' => now(),
    ];

    $pdf = Pdf::loadView('esbtp.attendances.export-pdf', $data);
    $pdf->setPaper('a4', 'portrait');
    $pdf->setOptions([
        'isHtml5ParserEnabled'    => true,
        'isRemoteEnabled'         => false,
        'defaultFont'             => 'DejaVu Sans',
        'dpi'                     => 150,
        'isFontSubsettingEnabled' => true,
    ]);

    $filename = 'rapport-absences-' . $etudiant->matricule . '-' . date('Y-m-d') . '.pdf';

    return $pdf->download($filename);
}

private function prepareLogoForPdf(?string $logoPath): ?string
{
    if ($logoPath) {
        $storagePath = storage_path('app/public/' . $logoPath);
        if (file_exists($storagePath)) {
            $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
            return 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($storagePath));
        }
    }
    foreach (['images/esbtp_logo.png', 'images/logo.jpeg'] as $alt) {
        $fullPath = public_path($alt);
        if (file_exists($fullPath)) {
            $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
            return 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($fullPath));
        }
    }
    return null;
}
```

## Checklist conformité KLASSCI
- ✅ Pdf::loadView()
- ✅ SettingsHelper::getPdfSettings()
- ✅ SettingsHelper::getSchoolInfo()
- ✅ isRemoteEnabled = false
- ✅ isFontSubsettingEnabled = true
- ✅ defaultFont = 'DejaVu Sans'
- ✅ Logo en base64 (pas asset())
- ✅ ->download($filename)
- ✅ DPI 150
