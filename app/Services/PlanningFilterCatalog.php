<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPLMDParcours;
use Illuminate\Support\Collection;

class PlanningFilterCatalog
{
    private ?Collection $pureLmdFiliereIds = null;
    private ?Collection $btsVisibleFiliereIds = null;

    public function getBtsFilieres(): Collection
    {
        return ESBTPFiliere::query()
            ->where('is_active', true)
            ->whereIn('id', $this->getBtsVisibleFiliereIds()->all())
            ->orderBy('name')
            ->get();
    }

    public function getBtsNiveaux(): Collection
    {
        return ESBTPNiveauEtude::query()
            ->where('is_active', true)
            ->whereNotIn('type', ['Licence', 'Master', 'Doctorat'])
            ->orderBy('year')
            ->get();
    }

    public function normalizeBtsFiliereId($filiereId): ?int
    {
        if (!$filiereId) {
            return null;
        }

        $filiereId = (int) $filiereId;

        return $this->getBtsVisibleFiliereIds()->contains($filiereId) ? $filiereId : null;
    }

    public function normalizeBtsNiveauId($niveauId): ?int
    {
        if (!$niveauId) {
            return null;
        }

        $niveauId = (int) $niveauId;

        return $this->getBtsNiveaux()->pluck('id')->contains($niveauId) ? $niveauId : null;
    }

    public function getPureLmdFiliereIds(): Collection
    {
        if ($this->pureLmdFiliereIds !== null) {
            return $this->pureLmdFiliereIds;
        }

        $lmdFiliereIds = ESBTPLMDParcours::query()
            ->whereNotNull('filiere_id')
            ->pluck('filiere_id')
            ->merge(
                ESBTPClasse::query()
                    ->whereNotNull('filiere_id')
                    ->where(function ($query) {
                        $query->where('systeme_academique', 'LMD')
                            ->orWhereNotNull('parcours_id');
                    })
                    ->pluck('filiere_id')
            )
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $btsFiliereIds = ESBTPClasse::query()
            ->whereNotNull('filiere_id')
            ->whereNull('parcours_id')
            ->where(function ($query) {
                $query->whereNull('systeme_academique')
                    ->orWhere('systeme_academique', '!=', 'LMD');
            })
            ->pluck('filiere_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $this->pureLmdFiliereIds = $lmdFiliereIds
            ->reject(fn (int $id) => $btsFiliereIds->contains($id))
            ->values();

        return $this->pureLmdFiliereIds;
    }

    public function getBtsVisibleFiliereIds(): Collection
    {
        if ($this->btsVisibleFiliereIds !== null) {
            return $this->btsVisibleFiliereIds;
        }

        $pureLmdIds = $this->getPureLmdFiliereIds();

        $this->btsVisibleFiliereIds = ESBTPFiliere::query()
            ->where('is_active', true)
            ->when($pureLmdIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $pureLmdIds->all()))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return $this->btsVisibleFiliereIds;
    }
}
