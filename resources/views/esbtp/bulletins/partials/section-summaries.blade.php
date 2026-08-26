@php
    $sections = ['general' => null, 'technical' => null];
    if (($settings['bulletin_show_section_averages'] ?? '1') == '1') {
        $sections = app(\App\Services\BulletinSectionSummary::class)->pair(
            $resultatsGeneraux ?? collect(),
            $resultatsTechniques ?? collect(),
            $absencesParMatiere ?? [],
            isset($moyenneGenerale) ? (float) $moyenneGenerale : null,
            isset($moyenneTechnique) ? (float) $moyenneTechnique : null,
            ! empty($showRankPerSubject) ? [
                'etudiant_id' => (int) ($etudiant->id ?? 0),
                'classe_id' => (int) ($classe->id ?? 0),
                'annee_id' => (int) ($anneeUniversitaire->id ?? $bulletin->annee_universitaire_id ?? 0),
                'periode' => (string) $periode,
            ] : null
        );
    }
@endphp
