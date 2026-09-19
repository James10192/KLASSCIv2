<?php

namespace App\Services;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulletinInlineConfigurationService
{
    public function __construct(private BulletinService $bulletinService)
    {
    }

    public function data(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $classe = ESBTPClasse::with(['filiere', 'niveau'])->findOrFail($classeId);
        $periode = $this->bulletinService->normalizePeriode($periode);
        $matieres = $this->matieresPourConfiguration($classe, $anneeUniversitaireId, $periode);
        $periods = $this->periodsFor($periode);

        $configRows = ESBTPConfigMatiere::withTrashed()
            ->where('classe_id', $classe->id)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $periods)
            ->get()
            ->groupBy('matiere_id');

        $coefficientRows = ESBTPMatiereCoefficient::query()
            ->where('filiere_id', $classe->filiere_id)
            ->where('niveau_etude_id', $classe->niveau_etude_id)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $periods)
            ->get()
            ->groupBy('matiere_id');

        $professeursTemplate = $this->loadProfesseursTemplate($classe->id, $anneeUniversitaireId, $periode);

        $matieresPayload = $matieres
            ->map(function ($matiere) use ($configRows, $coefficientRows, $professeursTemplate) {
                $existing = $configRows->get($matiere->id)?->first();
                $existingType = $existing ? $this->normalizeConfigMatiereType($existing->config) : null;
                $coefficient = $coefficientRows->get($matiere->id)?->first()?->coefficient;
                $professeur = trim((string) ($professeursTemplate[$matiere->id] ?? ''));

                return [
                    'id' => (int) $matiere->id,
                    'name' => $matiere->name ?? $matiere->nom ?? 'Matiere #'.$matiere->id,
                    'code' => $matiere->code,
                    'source' => $matiere->configuration_source ?? 'classe',
                    'existing_type' => $existingType,
                    'suggested_type' => $existingType ?: $this->guessMatiereType($matiere),
                    'coefficient' => $coefficient !== null ? (float) $coefficient : null,
                    'professeur' => $professeur,
                ];
            })
            ->values();

        $configuredCount = $matieresPayload->filter(fn ($matiere) => ! empty($matiere['existing_type']))->count();
        $configuredCoefficients = $matieresPayload->filter(fn ($matiere) => $matiere['coefficient'] !== null)->count();
        $configuredProfesseurs = $matieresPayload->filter(fn ($matiere) => trim((string) ($matiere['professeur'] ?? '')) !== '')->count();

        return [
            'success' => true,
            'classe' => [
                'id' => (int) $classe->id,
                'name' => $classe->name,
                'filiere' => $classe->filiere?->name,
                'niveau' => $classe->niveau?->name,
            ],
            'annee_universitaire_id' => $anneeUniversitaireId,
            'periode' => $periode,
            'matieres' => $matieresPayload,
            'configured_count' => $configuredCount,
            'missing_count' => max(0, $matieresPayload->count() - $configuredCount),
            'configured_coefficients_count' => $configuredCoefficients,
            'missing_coefficients_count' => max(0, $matieresPayload->count() - $configuredCoefficients),
            'configured_professeurs_count' => $configuredProfesseurs,
            'missing_professeurs_count' => max(0, $matieresPayload->count() - $configuredProfesseurs),
        ];
    }

    public function save(array $validated, int $userId): array
    {
        $classe = ESBTPClasse::findOrFail($validated['classe_id']);
        $periode = $this->bulletinService->normalizePeriode($validated['periode']);
        $created = 0;
        $updated = 0;
        $removed = 0;
        $coefficientsSaved = 0;
        $coefficientsRemoved = 0;
        $professeursSaved = 0;

        DB::transaction(function () use ($validated, $classe, $periode, $userId, &$created, &$updated, &$removed, &$coefficientsSaved, &$coefficientsRemoved, &$professeursSaved) {
            $professeurs = $this->normalizeProfesseurs(
                $validated['professeurs'] ?? [],
                array_keys($validated['matiere_type'])
            );

            foreach ($this->periodsFor($periode) as $targetPeriode) {
                foreach ($validated['matiere_type'] as $matiereId => $type) {
                    $query = [
                        'matiere_id' => (int) $matiereId,
                        'classe_id' => (int) $validated['classe_id'],
                        'periode' => $targetPeriode,
                        'annee_universitaire_id' => (int) $validated['annee_universitaire_id'],
                    ];

                    if ($type === 'none') {
                        $removed += ESBTPConfigMatiere::withTrashed()
                            ->where($query)
                            ->forceDelete();
                        continue;
                    }

                    $existing = ESBTPConfigMatiere::withTrashed()->where($query)->first();
                    ESBTPConfigMatiere::withTrashed()->updateOrCreate($query, [
                        'config' => json_encode(['type' => $type]),
                        'created_by' => $userId,
                        'updated_by' => $userId,
                        'deleted_at' => null,
                    ]);

                    $existing ? $updated++ : $created++;
                }

                foreach (($validated['coefficients'] ?? []) as $matiereId => $coefficient) {
                    $coefficientQuery = [
                        'matiere_id' => (int) $matiereId,
                        'filiere_id' => (int) $classe->filiere_id,
                        'niveau_etude_id' => (int) $classe->niveau_etude_id,
                        'annee_universitaire_id' => (int) $validated['annee_universitaire_id'],
                        'periode' => $targetPeriode,
                    ];

                    if ($coefficient === null || $coefficient === '') {
                        $coefficientsRemoved += ESBTPMatiereCoefficient::where($coefficientQuery)->delete();
                        continue;
                    }

                    ESBTPMatiereCoefficient::updateOrCreate($coefficientQuery, [
                        'coefficient' => (float) $coefficient,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                    $coefficientsSaved++;
                }

                $this->saveProfesseursTemplate(
                    (int) $validated['classe_id'],
                    (int) $validated['annee_universitaire_id'],
                    $targetPeriode,
                    $professeurs
                );
                $professeursSaved = count($professeurs);
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'removed' => $removed,
            'coefficients_saved' => $coefficientsSaved,
            'coefficients_removed' => $coefficientsRemoved,
            'professeurs_saved' => $professeursSaved,
        ];
    }

    public function loadProfesseursTemplate(int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $periode = $this->bulletinService->normalizePeriode($periode);

        foreach ($this->periodsFor($periode) as $targetPeriode) {
            $raw = SettingsHelper::get($this->professeursTemplateKey($classeId, $anneeUniversitaireId, $targetPeriode), null);
            $template = is_string($raw) ? (json_decode($raw, true) ?: []) : (array) $raw;
            $template = array_filter($template, fn ($value) => trim((string) $value) !== '');

            if ($template !== []) {
                return $template;
            }
        }

        $template = ESBTPBulletin::where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeUniversitaireId)
            ->whereIn('periode', $this->periodsFor($periode))
            ->whereNotNull('professeurs')
            ->where('professeurs', '!=', '')
            ->where('professeurs', '!=', '{}')
            ->latest('updated_at')
            ->value('professeurs');

        return is_string($template) ? (json_decode($template, true) ?: []) : (array) $template;
    }

    public function normalizeProfesseurs(array $professeurs, array $matiereIds): array
    {
        $allowed = array_flip(array_map('intval', $matiereIds));
        $normalized = [];

        foreach ($professeurs as $matiereId => $professeur) {
            $matiereId = (int) $matiereId;
            $professeur = trim((string) $professeur);

            if (! isset($allowed[$matiereId]) || $professeur === '') {
                continue;
            }

            $normalized[$matiereId] = $professeur;
        }

        return $normalized;
    }

    public function saveProfesseursTemplate(int $classeId, int $anneeUniversitaireId, string $periode, array $professeurs): void
    {
        SettingsHelper::setOrCreate(
            $this->professeursTemplateKey($classeId, $anneeUniversitaireId, $periode),
            json_encode($professeurs),
            'bulletin',
            'json'
        );
    }

    public function periodsFor(string $periode): array
    {
        return $periode === 'annuel' ? ['semestre1', 'semestre2'] : [$periode];
    }

    private function matieresPourConfiguration(ESBTPClasse $classe, int $anneeUniversitaireId, string $periode): Collection
    {
        // Configuration d'un bulletin BTS (le LMD a `ESBTPLMDBulletinController`).
        // Les TROIS lectures de cette methode sont gardees, pas seulement
        // celle-ci : le repli lit les deux pivots plats, que `LiaisonsDeMatiere`
        // ecrit ensemble, et `$evaluated` remonte tout ce qui porte une
        // evaluation — y compris ce qui a ete cree avant que les ecrans
        // d'evaluation ne soient gardes.
        $official = ESBTPMatiere::query()
            ->where('is_active', true)
            ->btsOnly()
            ->whereHas('liaisonsFilieresNiveaux', function ($query) use ($classe) {
                $query->where('filiere_id', $classe->filiere_id)
                    ->where('niveau_etude_id', $classe->niveau_etude_id);
            })
            ->orderBy('name')
            ->get();

        if ($official->isEmpty()) {
            $official = ESBTPMatiere::with(['filieres:id', 'niveaux:id'])
                ->where('is_active', true)
                ->btsOnly()
                ->orderBy('name')
                ->get()
                ->filter(function ($matiere) use ($classe) {
                    return $matiere->filieres->pluck('id')->contains($classe->filiere_id)
                        && $matiere->niveaux->pluck('id')->contains($classe->niveau_etude_id);
                })
                ->values();
        }

        $officialIds = $official->pluck('id')->all();
        // Une evaluation a pu etre creee sur une ECUE avant que les ecrans
        // d'evaluation ne soient gardes : l'historique la ramenerait ici.
        $evaluated = ESBTPMatiere::query()
            ->where('is_active', true)
            ->btsOnly()
            ->whereNotIn('id', $officialIds)
            ->whereHas('evaluations', function ($query) use ($classe, $anneeUniversitaireId, $periode) {
                $query->where('classe_id', $classe->id)
                    ->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->where('status', '!=', 'cancelled');

                if ($periode === 'annuel') {
                    $query->whereIn('periode', ['semestre1', 'semestre2']);
                } else {
                    $query->where('periode', $periode);
                }
            })
            ->orderBy('name')
            ->get();

        $official->each(fn ($matiere) => $matiere->configuration_source = 'classe');
        $evaluated->each(fn ($matiere) => $matiere->configuration_source = 'evaluations');

        return $official
            ->merge($evaluated)
            ->unique('id')
            ->values();
    }

    private function normalizeConfigMatiereType(mixed $config): ?string
    {
        $configData = is_string($config) ? (json_decode($config, true) ?: []) : (array) $config;
        $type = $configData['type'] ?? $configData['type_formation'] ?? null;

        return match ($type) {
            'general', 'generale' => 'general',
            'technique', 'technologique_professionnelle' => 'technique',
            default => null,
        };
    }

    private function guessMatiereType(ESBTPMatiere $matiere): string
    {
        $name = strtolower($matiere->nom ?? $matiere->name ?? '');

        return (str_contains($name, 'math')
            || str_contains($name, 'anglais')
            || str_contains($name, 'francais')
            || str_contains($name, 'fran')
            || str_contains($name, 'communication'))
                ? 'general'
                : 'technique';
    }

    private function professeursTemplateKey(int $classeId, int $anneeUniversitaireId, string $periode): string
    {
        return "bulletin_professeurs_template.{$classeId}.{$anneeUniversitaireId}.{$periode}";
    }
}
