<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPTeacher;

class AcademicPilotageReportService
{
    public function __construct(private readonly UnpaidStudentCountService $unpaidStudents)
    {
    }

    public function build(string $kind): array
    {
        $annee = ESBTPAnneeUniversitaire::query()->where('is_current', true)->first();
        $anneeId = $annee?->id;

        $inscrits = ESBTPInscription::query()
            ->when($anneeId, fn ($q) => $q->where('annee_universitaire_id', $anneeId))
            ->where('status', 'active')
            ->count();

        $classes = ESBTPClasse::query()->where('is_active', true)->count();
        $enseignants = ESBTPTeacher::query()->count();
        $edtManquants = 0;
        if ($anneeId) {
            $classesAvecEdt = ESBTPEmploiTemps::query()
                ->where('annee_universitaire_id', $anneeId)
                ->pluck('classe_id')
                ->unique();
            $edtManquants = ESBTPClasse::query()
                ->where('is_active', true)
                ->whereNotIn('id', $classesAvecEdt)
                ->count();
        }

        $notesManquantes = ESBTPEvaluation::query()
            ->when($anneeId, fn ($q) => $q->whereHas('classe', fn ($inner) => $inner->where('annee_universitaire_id', $anneeId)))
            ->whereDate('date_evaluation', '<', today())
            ->whereDoesntHave('notes')
            ->count();

        $payload = [
            'kind' => $kind,
            'annee' => $annee?->name,
            'inscrits_valides' => $inscrits,
            'classes' => $classes,
            'enseignants' => $enseignants,
            'edt_manquants' => $edtManquants,
            'etudiants_non_soldes' => $this->unpaidStudents->count($anneeId),
            'notes_manquantes' => $notesManquantes,
        ];

        $this->assertNoAmounts($payload);

        return $payload;
    }

    private function assertNoAmounts(array $payload): void
    {
        $encoded = strtolower(json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '');
        foreach (['montant', 'fcfa', 'amount', 'totalencaisse'] as $needle) {
            if (str_contains($encoded, $needle)) {
                throw new \RuntimeException('Academic reports must not expose financial amounts.');
            }
        }
    }
}