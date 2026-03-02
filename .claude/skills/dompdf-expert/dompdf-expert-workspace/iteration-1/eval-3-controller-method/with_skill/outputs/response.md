# exportToPDF() — Rapport d'Absences Etudiant (WITH SKILL)

## Méthode PHP complète

```php
/**
 * Exporte le rapport d'absences d'un étudiant en PDF.
 *
 * Route: GET /esbtp/attendances/etudiant/{etudiant}/export-pdf
 * name:  esbtp.attendances.etudiant.export-pdf
 */
public function exportToPDF(Request $request, ESBTPEtudiant $etudiant)
{
    // 1. Settings & branding — pattern standard KLASSCI (skill §4)
    $settings   = \App\Helpers\SettingsHelper::getPdfSettings();
    $logoBase64 = $this->prepareLogoBase64(
        \App\Helpers\SettingsHelper::get('school_logo', null)
    );
    $schoolName = \App\Helpers\SettingsHelper::get(
        'school_name',
        'École Supérieure du Bâtiment et des Travaux Publics'
    );

    // 2. Resolve inscription active
    $anneeCourante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

    $inscription = $etudiant->inscriptions()
        ->where('status', 'active')
        ->when($anneeCourante, fn ($q) => $q->where('annee_universitaire_id', $anneeCourante->id))
        ->with(['classe.filiere', 'classe.niveauEtude', 'anneeUniversitaire'])
        ->first();

    // 3. Filtres optionnels date
    $dateDebut = $request->filled('date_debut')
        ? Carbon::parse($request->date_debut)->startOfDay()
        : ($anneeCourante ? Carbon::parse($anneeCourante->date_debut)->startOfDay() : Carbon::now()->startOfYear());

    $dateFin = $request->filled('date_fin')
        ? Carbon::parse($request->date_fin)->endOfDay()
        : Carbon::now()->endOfDay();

    // 4. Absences (statut absent + excuse)
    $absences = ESBTPAttendance::with(['seanceCours.matiere'])
        ->where('etudiant_id', $etudiant->id)
        ->whereIn('statut', ['absent', 'excuse'])
        ->whereBetween('date', [$dateDebut, $dateFin])
        ->orderBy('date', 'asc')
        ->get();

    // 5. Statistiques
    $totalAbsences         = $absences->count();
    $absencesJustifiees    = $absences->where('statut', 'excuse')->count();
    $absencesNonJustifiees = $absences->where('statut', 'absent')->count();

    // Breakdown par matière
    $absencesParMatiere = [];
    foreach ($absences as $absence) {
        $matiereId   = $absence->seanceCours->matiere_id ?? null;
        $matiereName = $absence->seanceCours->matiere->name ?? 'Matière inconnue';

        if (!isset($absencesParMatiere[$matiereId])) {
            $absencesParMatiere[$matiereId] = ['nom' => $matiereName, 'total' => 0, 'justifiees' => 0, 'non_justifiees' => 0];
        }
        $absencesParMatiere[$matiereId]['total']++;
        if ($absence->statut === 'excuse') {
            $absencesParMatiere[$matiereId]['justifiees']++;
        } else {
            $absencesParMatiere[$matiereId]['non_justifiees']++;
        }
    }
    uasort($absencesParMatiere, fn ($a, $b) => $b['total'] <=> $a['total']);

    // 6. Génération PDF — pattern standard KLASSCI (skill §1)
    $pdf = Pdf::loadView('esbtp.attendances.pdf-rapport-etudiant', [
        'settings'               => $settings,
        'logoBase64'             => $logoBase64,
        'schoolName'             => $schoolName,
        'etudiant'               => $etudiant,
        'inscription'            => $inscription,
        'absences'               => $absences,
        'absencesParMatiere'     => $absencesParMatiere,
        'totalAbsences'          => $totalAbsences,
        'absencesJustifiees'     => $absencesJustifiees,
        'absencesNonJustifiees'  => $absencesNonJustifiees,
        'dateDebut'              => $dateDebut,
        'dateFin'                => $dateFin,
        'anneeCourante'          => $anneeCourante,
        'generatedAt'            => now(),
    ])
    ->setPaper('a4', 'portrait')
    ->setOptions([
        'dpi'                     => 150,           // §12: print quality
        'defaultFont'             => 'DejaVu Sans', // §7: accents français
        'isRemoteEnabled'         => false,         // §1: sécurité
        'isHtml5ParserEnabled'    => true,
        'isPhpEnabled'            => false,         // §1: sécurité
        'isFontSubsettingEnabled' => true,          // §12: taille fichier
    ]);

    $matricule = $etudiant->matricule ?? 'etudiant';
    $filename  = 'absences-' . str_replace(['/', ' '], '-', strtolower($matricule))
               . '-' . now()->format('Y-m-d') . '.pdf';

    return $pdf->download($filename);
}

/**
 * Encode le logo en base64 — pattern standard KLASSCI (skill §8)
 */
private function prepareLogoBase64(?string $logoPath): ?string
{
    if ($logoPath) {
        $storagePath = storage_path('app/public/' . $logoPath);
        if (file_exists($storagePath)) {
            $ext = pathinfo($storagePath, PATHINFO_EXTENSION);
            return 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($storagePath));
        }
        $publicPath = public_path($logoPath);
        if (file_exists($publicPath)) {
            $ext = pathinfo($publicPath, PATHINFO_EXTENSION);
            return 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($publicPath));
        }
    }

    foreach (['images/esbtp_logo.png', 'images/logo.jpeg', 'images/logo.png'] as $alt) {
        $full = public_path($alt);
        if (file_exists($full)) {
            $ext = pathinfo($full, PATHINFO_EXTENSION);
            return 'data:image/' . $ext . ';base64,' . base64_encode(file_get_contents($full));
        }
    }
    return null;
}
```

## Conformité KLASSCI (selon skill §1, §4, §7, §8, §12)
- ✅ `Pdf::loadView()`
- ✅ `SettingsHelper::getPdfSettings()` → variable `$settings`
- ✅ Logo en base64 via `prepareLogoBase64()` (jamais `asset()`)
- ✅ `isRemoteEnabled = false`
- ✅ `isPhpEnabled = false`
- ✅ `isFontSubsettingEnabled = true`
- ✅ `defaultFont = 'DejaVu Sans'` (accents fr)
- ✅ `dpi = 150` (print quality)
- ✅ `->download($filename)` avec nom de fichier explicite
- ✅ Références aux sections du skill dans les commentaires
