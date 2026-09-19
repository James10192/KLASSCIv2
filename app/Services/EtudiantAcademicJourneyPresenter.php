<?php

namespace App\Services;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDBulletin;
use App\Models\ESBTPResultat;
use App\Services\LMD\AgregatDeLaPeriode;
use Illuminate\Support\Collection;

class EtudiantAcademicJourneyPresenter
{
    public function present(ESBTPEtudiant $etudiant): array
    {
        $etudiant->loadMissing([
            'inscriptions.anneeUniversitaire',
            'inscriptions.filiere',
            'inscriptions.niveauEtude',
            'inscriptions.classe.filiere',
            'inscriptions.classe.niveauEtude',
            'inscriptions.classe.parcours.mention.domaine',
            'inscriptions.phases.classe.filiere',
            'inscriptions.inscriptionOrigine.classe.filiere',
            'inscriptions.inscriptionSpecialisation.classe.filiere',
        ]);

        $inscriptions = $this->sortInscriptions($etudiant->inscriptions);

        if ($inscriptions->isEmpty()) {
            return [
                'items' => collect(),
                'summary' => [
                    'total' => 0,
                    'has_bts' => false,
                    'has_lmd' => false,
                    'has_mixed_path' => false,
                    'duplicate_years' => collect(),
                ],
            ];
        }

        $classeIds = $inscriptions->pluck('classe_id')->filter()->unique()->values();
        $anneeIds = $inscriptions->pluck('annee_universitaire_id')->filter()->unique()->values();

        return $this->presentFromCollections(
            $inscriptions,
            $this->btsBulletins($etudiant, $classeIds, $anneeIds),
            $this->lmdBulletins($etudiant, $classeIds, $anneeIds),
            $this->resultats($etudiant, $classeIds, $anneeIds)
        );
    }

    /**
     * @param Collection<int, ESBTPInscription> $inscriptions
     * @param Collection<int, ESBTPBulletin>|null $btsBulletins
     * @param Collection<int, ESBTPLMDBulletin>|null $lmdBulletins
     * @param Collection<int, ESBTPResultat>|null $resultats
     */
    public function presentFromCollections(Collection $inscriptions, ?Collection $btsBulletins = null, ?Collection $lmdBulletins = null, ?Collection $resultats = null): array
    {
        $inscriptions = $this->sortInscriptions($inscriptions);

        if ($inscriptions->isEmpty()) {
            return [
                'items' => collect(),
                'summary' => [
                    'total' => 0,
                    'has_bts' => false,
                    'has_lmd' => false,
                    'has_mixed_path' => false,
                    'duplicate_years' => collect(),
                ],
            ];
        }

        $btsBulletins = $this->groupByAcademicKey($btsBulletins ?? collect());
        $lmdBulletins = $this->groupByAcademicKey($lmdBulletins ?? collect());
        $resultats = $this->groupByAcademicKey($resultats ?? collect());
        $duplicateYears = $this->duplicateYears($inscriptions);

        $previous = null;
        $items = $inscriptions->map(function (ESBTPInscription $inscription) use (&$previous, $btsBulletins, $lmdBulletins, $resultats) {
            $system = $this->systemFor($inscription);
            $key = $this->academicKey($inscription->classe_id, $inscription->annee_universitaire_id);
            $metrics = $system === 'LMD'
                ? $this->lmdMetrics($lmdBulletins->get($key, collect()))
                : $this->btsMetrics($btsBulletins->get($key, collect()), $resultats->get($key, collect()));

            $item = [
                'id' => $inscription->id,
                'system' => $system,
                'system_label' => $system,
                'tone' => strtolower($system),
                'annee' => $this->anneeLabel($inscription),
                'classe' => $inscription->classe?->name,
                'niveau' => $this->niveauLabel($inscription),
                'filiere' => $this->filiereLabel($inscription),
                'lmd_path' => $this->lmdPath($inscription),
                'status' => $this->statusLabel($inscription->status),
                'workflow' => $this->workflowLabel($inscription->workflow_step),
                'affectation' => $this->affectationLabel($inscription->affectation_status),
                'is_sous_reserve' => (bool) $inscription->is_sous_reserve,
                'date_inscription' => $this->dateLabel($inscription),
                'metrics' => $metrics,
                'transition' => $this->transitionFrom($previous, $inscription),
                'bts_journey' => $inscription->bts_journey_ui ?? null,
            ];

            $previous = $inscription;

            return $item;
        })->values();

        $systems = $items->pluck('system')->unique()->values();

        return [
            'items' => $items,
            'summary' => [
                'total' => $items->count(),
                'has_bts' => $systems->contains('BTS'),
                'has_lmd' => $systems->contains('LMD'),
                'has_mixed_path' => $systems->count() > 1,
                'duplicate_years' => $duplicateYears,
            ],
        ];
    }

    private function groupByAcademicKey(Collection $rows): Collection
    {
        return $rows->groupBy(fn ($row) => $this->academicKey($row->classe_id ?? null, $row->annee_universitaire_id ?? null));
    }

    private function sortInscriptions(Collection $inscriptions): Collection
    {
        return $inscriptions
            ->sortBy(function (ESBTPInscription $inscription) {
                $start = optional($inscription->anneeUniversitaire?->start_date)->format('Ymd') ?? '99999999';
                $date = optional($inscription->date_inscription)->format('Ymd') ?? optional($inscription->created_at)->format('Ymd') ?? '99999999';

                return sprintf('%s-%s-%010d', $start, $date, $inscription->id);
            })
            ->values();
    }

    private function btsBulletins(ESBTPEtudiant $etudiant, Collection $classeIds, Collection $anneeIds): Collection
    {
        if ($classeIds->isEmpty() || $anneeIds->isEmpty()) {
            return collect();
        }

        return ESBTPBulletin::query()
            ->where('etudiant_id', $etudiant->id)
            ->whereIn('classe_id', $classeIds)
            ->whereIn('annee_universitaire_id', $anneeIds)
            ->where('periode', '!=', 'annuel')
            ->get();
    }

    private function lmdBulletins(ESBTPEtudiant $etudiant, Collection $classeIds, Collection $anneeIds): Collection
    {
        if ($classeIds->isEmpty() || $anneeIds->isEmpty()) {
            return collect();
        }

        return ESBTPLMDBulletin::query()
            ->where('etudiant_id', $etudiant->id)
            ->whereIn('classe_id', $classeIds)
            ->whereIn('annee_universitaire_id', $anneeIds)
            ->orderBy('semestre')
            ->get();
    }

    private function resultats(ESBTPEtudiant $etudiant, Collection $classeIds, Collection $anneeIds): Collection
    {
        if ($classeIds->isEmpty() || $anneeIds->isEmpty()) {
            return collect();
        }

        return ESBTPResultat::query()
            // `withTrashed()` sur LES DEUX : `ESBTPMatiere` et `ESBTPClasse` sont en
            // `SoftDeletes`, et le filtre plus bas est en echec ouvert. Une classe
            // archivee en fin d'annee — geste ordinaire — suffisait a le desarmer,
            // alors que le snapshot affiche a cote, lui, est deja en `withTrashed()` :
            // les deux moyennes de la meme page divergeaient a nouveau.
            ->with([
                'matiere' => fn ($q) => $q->withTrashed(),
                'classe' => fn ($q) => $q->withTrashed(),
            ])
            ->where('etudiant_id', $etudiant->id)
            ->whereIn('classe_id', $classeIds)
            ->whereIn('annee_universitaire_id', $anneeIds)
            ->whereNotNull('moyenne')
            ->get()
            // Ce repli sert EXACTEMENT le cas d'avant-bulletin (`btsMetrics()`
            // ne le lit que si aucun bulletin n'est calcule), soit le moment ou
            // une ECUE mal rangee se voit le plus. Et il est rendu sur le MEME
            // ecran que `$btsAnnualSnapshot`, deja filtre : sans ce filtre-ci,
            // la meme page affichait deux moyennes differentes.
            ->filter(fn (ESBTPResultat $resultat) => ! $resultat->matiere
                || ! $resultat->classe
                || CoherenceSystemeAcademique::matiereRetenue($resultat->matiere, $resultat->classe, 'parcours etudiant/moyenne enregistree'))
            ->values();
    }

    private function btsMetrics(Collection $bulletins, Collection $resultats): array
    {
        $calculatedBulletins = $bulletins->filter(fn (ESBTPBulletin $bulletin) => $bulletin->moyenne_generale !== null && $bulletin->moyenne_generale > 0);

        if ($calculatedBulletins->isNotEmpty()) {
            $last = $calculatedBulletins->sortBy('periode')->last();
            $average = round((float) $calculatedBulletins->avg('moyenne_generale'), 2);

            return [
                'source' => 'Bulletins BTS',
                'moyenne' => $average,
                'moyenne_label' => number_format($average, 2, ',', ' ') . ' / 20',
                'rang_label' => $last?->rang ? $last->rang . ($last->effectif_classe ? '/' . $last->effectif_classe : '') : null,
                'credits_label' => null,
                'mention' => $last?->mention,
            ];
        }

        if ($resultats->isNotEmpty()) {
            $average = $this->weightedAverage($resultats);

            return [
                'source' => 'Résultats saisis',
                'moyenne' => $average,
                'moyenne_label' => $average === null ? null : number_format($average, 2, ',', ' ') . ' / 20',
                'rang_label' => null,
                'credits_label' => null,
                'mention' => null,
            ];
        }

        return [
            'source' => 'Aucun résultat',
            'moyenne' => null,
            'moyenne_label' => null,
            'rang_label' => null,
            'credits_label' => null,
            'mention' => null,
        ];
    }

    private function lmdMetrics(Collection $bulletins): array
    {
        if ($bulletins->isEmpty()) {
            return [
                'source' => 'Aucun bulletin LMD',
                'moyenne' => null,
                'moyenne_label' => null,
                'rang_label' => null,
                'credits_label' => null,
                'mention' => null,
            ];
        }

        $creditsTotal = (int) $bulletins->sum('credits_totaux');

        // Ce diagramme est rendu sur `/esbtp/etudiants/{id}`, à quelques
        // centimètres de l'indicateur « Moy. générale » et sur le même
        // regroupement (classe + année) : il doit donc afficher le même nombre,
        // par le même calcul. Il portait sa propre formule — voir
        // `AgregatDeLaPeriode`, section « Les formules concurrentes ».
        $average = AgregatDeLaPeriode::moyenne(AgregatDeLaPeriode::parSemestre($bulletins));

        $last = $bulletins->sortBy('semestre')->last();
        $creditsCapitalises = (int) $bulletins->sum('credits_capitalises');

        return [
            'source' => 'Bulletins LMD',
            'moyenne' => $average,
            'moyenne_label' => $average === null ? null : number_format($average, 2, ',', ' ') . ' / 20',
            'rang_label' => $last?->rang ? $last->rang . ($last->effectif ? '/' . $last->effectif : '') : null,
            'credits_label' => $creditsTotal > 0 ? $creditsCapitalises . ' / ' . $creditsTotal . ' CECT' : null,
            'mention' => $last?->mention_generale,
        ];
    }

    private function weightedAverage(Collection $resultats): ?float
    {
        $weightedTotal = 0.0;
        $coefficients = 0.0;

        foreach ($resultats as $resultat) {
            $coefficient = (float) ($resultat->coefficient ?: 1);
            $weightedTotal += (float) $resultat->moyenne * $coefficient;
            $coefficients += $coefficient;
        }

        return $coefficients > 0 ? round($weightedTotal / $coefficients, 2) : null;
    }

    private function duplicateYears(Collection $inscriptions): Collection
    {
        return $inscriptions
            ->filter(fn (ESBTPInscription $inscription) => $inscription->annee_universitaire_id !== null)
            ->groupBy('annee_universitaire_id')
            ->filter(fn (Collection $yearInscriptions) => $yearInscriptions->count() > 1)
            ->map(fn (Collection $yearInscriptions) => [
                'annee' => $this->anneeLabel($yearInscriptions->first()),
                'count' => $yearInscriptions->count(),
            ])
            ->values();
    }

    private function transitionFrom(?ESBTPInscription $previous, ESBTPInscription $current): array
    {
        if (! $previous) {
            return ['type' => 'entry', 'label' => 'Entrée dans le parcours', 'icon' => 'fa-sign-in-alt'];
        }

        if ($previous->annee_universitaire_id && $previous->annee_universitaire_id === $current->annee_universitaire_id) {
            return ['type' => 'duplicate_same_year', 'label' => 'Inscription multiple la même année', 'icon' => 'fa-layer-group'];
        }

        $previousSystem = $this->systemFor($previous);
        $currentSystem = $this->systemFor($current);

        if ($previousSystem === 'BTS' && $currentSystem === 'LMD') {
            return ['type' => 'bridge_bts_lmd', 'label' => 'Transition BTS vers LMD', 'icon' => 'fa-route'];
        }

        if ($previousSystem === 'LMD' && $currentSystem === 'BTS') {
            return ['type' => 'bridge_lmd_bts', 'label' => 'Transition LMD vers BTS', 'icon' => 'fa-route'];
        }

        if ($this->niveauLabel($previous) && $this->niveauLabel($previous) === $this->niveauLabel($current)) {
            return ['type' => 'same_level', 'label' => 'Même niveau repris', 'icon' => 'fa-redo-alt'];
        }

        if ($this->hasYearGap($previous, $current)) {
            return ['type' => 'gap', 'label' => 'Reprise après interruption', 'icon' => 'fa-pause-circle'];
        }

        return ['type' => 'progression', 'label' => 'Progression académique', 'icon' => 'fa-arrow-right'];
    }

    private function hasYearGap(ESBTPInscription $previous, ESBTPInscription $current): bool
    {
        $previousStart = $previous->anneeUniversitaire?->start_date;
        $currentStart = $current->anneeUniversitaire?->start_date;

        if (! $previousStart || ! $currentStart) {
            return false;
        }

        return ((int) $currentStart->format('Y') - (int) $previousStart->format('Y')) > 1;
    }

    private function systemFor(ESBTPInscription $inscription): string
    {
        if (($inscription->classe?->systeme_academique ?? null) === 'LMD') {
            return 'LMD';
        }

        $type = strtoupper((string) ($inscription->classe?->niveauEtude?->type ?? $inscription->niveauEtude?->type ?? ''));

        return str_contains($type, 'LICENCE') || str_contains($type, 'MASTER') || str_contains($type, 'DOCTOR')
            ? 'LMD'
            : 'BTS';
    }

    private function lmdPath(ESBTPInscription $inscription): array
    {
        $parcours = $inscription->classe?->parcours;

        return [
            'domaine' => $parcours?->mention?->domaine?->name,
            'mention' => $parcours?->mention?->name,
            'parcours' => $parcours?->name,
        ];
    }

    private function anneeLabel(ESBTPInscription $inscription): string
    {
        return $inscription->anneeUniversitaire?->display_name
            ?? $inscription->anneeUniversitaire?->name
            ?? 'Année non renseignée';
    }

    private function niveauLabel(ESBTPInscription $inscription): ?string
    {
        return $inscription->classe?->niveauEtude?->name
            ?? $inscription->niveauEtude?->name
            ?? $inscription->classe?->niveau?->name
            ?? $inscription->niveau?->name;
    }

    private function filiereLabel(ESBTPInscription $inscription): ?string
    {
        return $inscription->classe?->filiere?->name
            ?? $inscription->filiere?->name;
    }

    private function dateLabel(ESBTPInscription $inscription): ?string
    {
        return $inscription->date_inscription?->format('d/m/Y')
            ?? $inscription->created_at?->format('d/m/Y');
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'active', 'actif' => 'Active',
            'pending', 'en_attente' => 'En attente',
            'annulée', 'annulee' => 'Annulée',
            'abandon' => 'Abandon',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : 'Non renseigné',
        };
    }

    private function workflowLabel(?string $workflow): ?string
    {
        return match ($workflow) {
            'prospect' => 'Prospect',
            'documents_complets' => 'Documents complets',
            'en_validation' => 'En validation',
            'valide' => 'Validé',
            'etudiant_cree' => 'Étudiant créé',
            default => $workflow ? ucfirst(str_replace('_', ' ', $workflow)) : null,
        };
    }

    private function affectationLabel(?string $affectation): ?string
    {
        return match ($affectation) {
            'affecté', 'affecte' => 'Affecté',
            'réaffecté', 'reaffecte' => 'Réaffecté',
            'non_affecté', 'non_affecte' => 'Non affecté',
            default => $affectation ? ucfirst(str_replace('_', ' ', $affectation)) : null,
        };
    }

    private function academicKey(?int $classeId, ?int $anneeId): string
    {
        return ($classeId ?: 'none') . ':' . ($anneeId ?: 'none');
    }
}
