<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentInscriptionRepairService
{
    public function diagnose(ESBTPEtudiant $etudiant, ?int $anneeId = null, ?int $targetClasseId = null): array
    {
        $annee = $this->resolveAnnee($anneeId);
        $targetClasse = $targetClasseId ? $this->loadTargetClasse($targetClasseId) : null;
        $inscriptions = $annee
            ? $this->loadCurrentInscriptions($etudiant->id, $annee->id)
            : new EloquentCollection();

        $profiles = $this->buildProfiles($inscriptions);
        $decision = $this->buildDecision($profiles, $targetClasse);

        return [
            'etudiant' => [
                'id' => $etudiant->id,
                'matricule' => $etudiant->matricule,
                'nom_complet' => trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? '')),
                'statut' => $etudiant->statut,
            ],
            'annee' => $annee ? [
                'id' => $annee->id,
                'name' => $annee->display_name ?? $annee->name,
                'is_current' => (bool) $annee->is_current,
            ] : null,
            'target_classe' => $this->formatClasse($targetClasse),
            'suggested_target_classe_id' => $this->suggestTargetClasseId($profiles),
            'inscriptions' => $profiles,
            'summary' => [
                'active_inscriptions_count' => count($profiles),
                'has_duplicate_current_year' => count($profiles) > 1,
                'can_repair' => $decision['can_repair'],
                'requires_target_classe' => $targetClasse === null,
                'message' => $decision['message'],
            ],
            'decision' => $decision,
        ];
    }

    public function repair(ESBTPEtudiant $etudiant, array $options, ?int $userId = null): array
    {
        $annee = $this->resolveAnnee(isset($options['annee_universitaire_id']) ? (int) $options['annee_universitaire_id'] : null);
        $targetClasse = $this->loadTargetClasse((int) ($options['target_classe_id'] ?? 0));
        $dryRun = (bool) ($options['dry_run'] ?? false);

        if (! $annee) {
            return [
                'success' => false,
                'message' => "Aucune annee universitaire active n'est disponible.",
                'diagnostic' => $this->diagnose($etudiant, null, $targetClasse?->id),
            ];
        }

        if (! $targetClasse) {
            return [
                'success' => false,
                'message' => 'La classe cible est introuvable ou inactive.',
                'diagnostic' => $this->diagnose($etudiant, $annee->id, null),
            ];
        }

        return DB::transaction(function () use ($etudiant, $annee, $targetClasse, $dryRun, $userId) {
            $lockedIds = ESBTPInscription::where('etudiant_id', $etudiant->id)
                ->where('annee_universitaire_id', $annee->id)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            $inscriptions = ESBTPInscription::with([
                    'anneeUniversitaire',
                    'classe.filiere',
                    'classe.niveauEtude',
                    'filiere',
                    'niveau',
                    'paiements',
                ])
                ->whereIn('id', $lockedIds)
                ->orderBy('created_at', 'desc')
                ->get();

            $profiles = $this->buildProfiles($inscriptions);
            $decision = $this->buildDecision($profiles, $targetClasse);

            if (! $decision['can_repair']) {
                return [
                    'success' => false,
                    'message' => $decision['message'],
                    'diagnostic' => $this->diagnose($etudiant->fresh(), $annee->id, $targetClasse->id),
                ];
            }

            if ($dryRun) {
                return [
                    'success' => true,
                    'dry_run' => true,
                    'message' => 'Simulation prete, aucune donnee modifiee.',
                    'diagnostic' => $this->diagnose($etudiant->fresh(), $annee->id, $targetClasse->id),
                ];
            }

            $keep = ESBTPInscription::findOrFail((int) $decision['keep_inscription_id']);
            $archiveIds = array_map('intval', $decision['archive_inscription_ids']);
            $oldClasseIds = collect([$keep->classe_id])->merge(
                ESBTPInscription::whereIn('id', $archiveIds)->pluck('classe_id')
            )->filter()->unique()->values()->all();

            $archived = [];
            foreach ($archiveIds as $archiveId) {
                if ($archiveId === $keep->id) {
                    continue;
                }

                $archive = ESBTPInscription::find($archiveId);
                if (! $archive) {
                    continue;
                }

                $archive->updated_by = $userId;
                $archive->save();
                $archive->delete();
                $archived[] = $archiveId;
            }

            $keep->forceFill([
                'classe_id' => $targetClasse->id,
                'filiere_id' => $targetClasse->filiere_id,
                'niveau_id' => $targetClasse->niveau_etude_id,
                'status' => 'active',
                'workflow_step' => 'etudiant_cree',
                'affectation_status' => $keep->affectation_status ?: ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
                'date_validation' => $keep->date_validation ?: now(),
                'validated_by' => $keep->validated_by ?: $userId,
                'updated_by' => $userId,
            ])->save();

            if ($etudiant->statut !== 'actif') {
                $etudiant->forceFill(['statut' => 'actif'])->save();
            }

            collect($oldClasseIds)
                ->merge([$targetClasse->id])
                ->unique()
                ->each(function ($classeId) {
                    ESBTPClasse::find($classeId)?->updatePlacesOccupees();
                });

            Log::info('Correction inscriptions etudiant executee', [
                'etudiant_id' => $etudiant->id,
                'annee_universitaire_id' => $annee->id,
                'keep_inscription_id' => $keep->id,
                'archived_inscription_ids' => $archived,
                'target_classe_id' => $targetClasse->id,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'dry_run' => false,
                'message' => 'Correction appliquee : inscription conservee alignee et doublon archive.',
                'archived_inscription_ids' => $archived,
                'kept_inscription_id' => $keep->id,
                'diagnostic' => $this->diagnose($etudiant->fresh(), $annee->id, $targetClasse->id),
            ];
        });
    }

    private function resolveAnnee(?int $anneeId): ?ESBTPAnneeUniversitaire
    {
        if ($anneeId) {
            return ESBTPAnneeUniversitaire::find($anneeId);
        }

        return ESBTPAnneeUniversitaire::where('is_current', true)->first();
    }

    private function loadTargetClasse(int $classeId): ?ESBTPClasse
    {
        if (! $classeId) {
            return null;
        }

        return ESBTPClasse::with(['filiere', 'niveauEtude'])
            ->where('is_active', true)
            ->find($classeId);
    }

    private function loadCurrentInscriptions(int $etudiantId, int $anneeId): EloquentCollection
    {
        return ESBTPInscription::with([
                'anneeUniversitaire',
                'classe.filiere',
                'classe.niveauEtude',
                'filiere',
                'niveau',
                'paiements',
            ])
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function buildProfiles(EloquentCollection $inscriptions): array
    {
        return $inscriptions->map(function (ESBTPInscription $inscription) {
            $paiements = $inscription->paiements;
            $valides = $paiements->filter(fn ($p) => $this->isStatus($p->status, 'valid'));
            $attente = $paiements->filter(fn ($p) => $this->isStatus($p->status, 'attente'));
            $lastPaymentAt = $this->latestPaymentDate($paiements->pluck('date_paiement')->filter()->all());
            $classe = $inscription->classe;
            $niveau = $classe?->niveauEtude ?? $inscription->niveau;
            $filiere = $classe?->filiere ?? $inscription->filiere;

            return [
                'id' => $inscription->id,
                'classe_id' => $inscription->classe_id,
                'classe' => $classe?->name,
                'filiere_id' => $filiere?->id,
                'filiere' => $filiere?->name,
                'niveau_id' => $niveau?->id,
                'niveau' => $niveau?->name,
                'niveau_year' => (int) ($niveau?->year ?? 0),
                'status' => $inscription->status,
                'workflow_step' => $inscription->workflow_step,
                'type_inscription' => $inscription->type_inscription,
                'date_inscription' => $inscription->date_inscription?->format('Y-m-d'),
                'created_at' => $inscription->created_at?->toISOString(),
                'payments' => [
                    'count' => $paiements->count(),
                    'valid_count' => $valides->count(),
                    'pending_count' => $attente->count(),
                    'total' => \App\Models\ESBTPPaiement::netStudentPaidFrom($paiements) + \App\Models\ESBTPPaiement::pendingEncaissementsFrom($paiements),
                    'valid_total' => \App\Models\ESBTPPaiement::netStudentPaidFrom($valides),
                    'last_payment_at' => $lastPaymentAt,
                ],
                'score' => $this->scoreInscription($inscription, $paiements, $valides),
            ];
        })->values()->all();
    }

    private function buildDecision(array $profiles, ?ESBTPClasse $targetClasse): array
    {
        if (count($profiles) === 0) {
            return [
                'can_repair' => false,
                'message' => "Aucune inscription active trouvee pour l'annee selectionnee.",
                'keep_inscription_id' => null,
                'archive_inscription_ids' => [],
                'target_after_repair' => $this->formatClasse($targetClasse),
            ];
        }

        if (! $targetClasse) {
            return [
                'can_repair' => false,
                'message' => 'Choisissez une classe cible pour lancer la simulation.',
                'keep_inscription_id' => null,
                'archive_inscription_ids' => [],
                'target_after_repair' => null,
            ];
        }

        $ranked = $this->rankProfiles($profiles, $targetClasse);

        $keep = $ranked->first();
        $archive = $ranked->slice(1)->pluck('id')->values()->all();
        $needsAlignment = (int) ($keep['classe_id'] ?? 0) !== (int) $targetClasse->id
            || (int) ($keep['filiere_id'] ?? 0) !== (int) $targetClasse->filiere_id
            || (int) ($keep['niveau_id'] ?? 0) !== (int) $targetClasse->niveau_etude_id
            || $keep['status'] !== 'active'
            || $keep['workflow_step'] !== 'etudiant_cree';

        $canRepair = count($archive) > 0 || $needsAlignment;

        return [
            'can_repair' => $canRepair,
            'message' => $canRepair
                ? 'Simulation prete : conserver la ligne la plus payee, archiver les doublons et aligner la classe.'
                : 'Rien a corriger pour cette annee et cette classe cible.',
            'keep_inscription_id' => $keep['id'] ?? null,
            'archive_inscription_ids' => $archive,
            'needs_alignment' => $needsAlignment,
            'target_after_repair' => $this->formatClasse($targetClasse),
        ];
    }

    private function scoreInscription(ESBTPInscription $inscription, $paiements, $valides): array
    {
        return [
            'valid_total' => \App\Models\ESBTPPaiement::netStudentPaidFrom($valides),
            'total' => \App\Models\ESBTPPaiement::netStudentPaidFrom($paiements) + \App\Models\ESBTPPaiement::pendingEncaissementsFrom($paiements),
            'valid_count' => $valides->count(),
            'count' => $paiements->count(),
            'status_priority' => match ($inscription->status) {
                'active' => 3,
                'en_attente' => 2,
                'terminée' => 1,
                'terminee' => 1,
                default => 0,
            },
            'workflow_priority' => $inscription->workflow_step === 'etudiant_cree' ? 2 : 0,
            'created_at' => $inscription->created_at?->timestamp ?? 0,
            'id_tiebreaker' => -1 * (int) $inscription->id,
        ];
    }

    private function suggestTargetClasseId(array $profiles): ?int
    {
        if (empty($profiles)) {
            return null;
        }

        return collect($profiles)
            ->sort(function (array $a, array $b) {
                return $this->compareScore(
                    [
                        'niveau_year' => (int) ($a['niveau_year'] ?? 0),
                        'second_year_bts' => (int) str_starts_with((string) ($a['classe'] ?? ''), '2BTS'),
                        'valid_total' => (float) ($a['payments']['valid_total'] ?? 0),
                        'total' => (float) ($a['payments']['total'] ?? 0),
                        'id_tiebreaker' => -1 * (int) ($a['id'] ?? 0),
                    ],
                    [
                        'niveau_year' => (int) ($b['niveau_year'] ?? 0),
                        'second_year_bts' => (int) str_starts_with((string) ($b['classe'] ?? ''), '2BTS'),
                        'valid_total' => (float) ($b['payments']['valid_total'] ?? 0),
                        'total' => (float) ($b['payments']['total'] ?? 0),
                        'id_tiebreaker' => -1 * (int) ($b['id'] ?? 0),
                    ]
                );
            })
            ->value('classe_id');
    }

    private function formatClasse(?ESBTPClasse $classe): ?array
    {
        if (! $classe) {
            return null;
        }

        return [
            'id' => $classe->id,
            'name' => $classe->name,
            'filiere_id' => $classe->filiere_id,
            'filiere' => $classe->filiere?->name,
            'niveau_id' => $classe->niveau_etude_id,
            'niveau' => $classe->niveauEtude?->name,
            'systeme' => $classe->systeme_academique,
            'places_disponibles' => $classe->places_disponibles,
        ];
    }

    private function isStatus(?string $status, string $needle): bool
    {
        $normalized = str($status ?? '')
            ->lower()
            ->ascii()
            ->toString();

        return str_contains($normalized, $needle);
    }

    private function rankProfiles(array $profiles, ?ESBTPClasse $targetClasse = null)
    {
        return collect($profiles)
            ->map(function (array $profile) use ($targetClasse) {
                $score = $profile['score'] ?? [];

                if ($targetClasse) {
                    $score['target_classe_priority'] = (int) ($profile['classe_id'] ?? 0) === (int) $targetClasse->id ? 2 : 0;
                    $score['target_structure_priority'] = (int) ($profile['filiere_id'] ?? 0) === (int) $targetClasse->filiere_id
                        && (int) ($profile['niveau_id'] ?? 0) === (int) $targetClasse->niveau_etude_id
                        ? 1
                        : 0;
                }

                $profile['_decision_score'] = $score;

                return $profile;
            })
            ->sort(fn (array $a, array $b) => $this->compareScore($a['_decision_score'] ?? [], $b['_decision_score'] ?? []))
            ->map(function (array $profile) {
                unset($profile['_decision_score']);

                return $profile;
            })
            ->values();
    }

    private function compareScore(array $a, array $b): int
    {
        $keys = [
            'valid_total',
            'total',
            'valid_count',
            'count',
            'target_classe_priority',
            'target_structure_priority',
            'status_priority',
            'workflow_priority',
            'niveau_year',
            'second_year_bts',
            'created_at',
            'id_tiebreaker',
        ];

        foreach ($keys as $key) {
            $left = $a[$key] ?? 0;
            $right = $b[$key] ?? 0;

            if ($left == $right) {
                continue;
            }

            return $left < $right ? 1 : -1;
        }

        return 0;
    }

    private function latestPaymentDate(array $dates): ?string
    {
        $timestamps = collect($dates)
            ->map(function ($date) {
                if ($date instanceof DateTimeInterface) {
                    return $date->getTimestamp();
                }

                return $date ? strtotime((string) $date) : false;
            })
            ->filter(fn ($timestamp) => $timestamp !== false)
            ->values();

        if ($timestamps->isEmpty()) {
            return null;
        }

        return date('Y-m-d', (int) $timestamps->max());
    }
}
