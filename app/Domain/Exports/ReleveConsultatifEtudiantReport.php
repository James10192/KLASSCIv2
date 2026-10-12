<?php

namespace App\Domain\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Lecture non institutionnelle des notes déjà visibles par l'étudiant.
 * LMD et BTS restent calculés par leurs services métier respectifs.
 */
final class ReleveConsultatifEtudiantReport extends ExportableReport
{
    public function __construct(private readonly array $donnees) {}

    public function title(): string
    {
        return 'Relevé de notes — consultation';
    }

    public function pdfView(): string
    {
        return 'esbtp.etudiants.releve-consultatif-pdf';
    }

    public function subtitle(): ?string
    {
        return 'Document consultatif sans valeur officielle';
    }

    public function viewData(): array
    {
        return $this->donnees;
    }

    public function filters(): array
    {
        return [
            'Année universitaire' => (string) ($this->donnees['annee']->name ?? '—'),
            'Système' => (string) ($this->donnees['ecran']['systeme'] ?? '—'),
        ];
    }

    public function filename(): string
    {
        $matricule = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($this->donnees['etudiant']->matricule ?? 'etudiant'));

        return 'releve-consultatif-'.$matricule.'-'.now()->format('Ymd');
    }

    /**
     * La mise en cache PDF standard est globale au serveur.
     * Son empreinte DOIT donc contenir l'identité et les résultats,
     * pas uniquement les filtres année / système.
     */
    public function cacheKey(): string
    {
        return hash('sha256', json_encode([
            'type' => static::class,
            'etudiant_id' => (int) ($this->donnees['etudiant']->id ?? 0),
            'annee_id' => (int) ($this->donnees['annee']->id ?? 0),
            'ecran' => $this->donnees['ecran'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function excelExport(): FromCollection
    {
        $rows = collect([['Période', 'Enseignement', 'Code', 'Moyenne /20']]);
        foreach (($this->donnees['ecran']['semestres'] ?? []) as $semestre) {
            foreach (($semestre['ues'] ?? []) as $ue) {
                $rows->push([$semestre['label'], $ue['name'], $ue['code'], $ue['moyenne']]);
                foreach (($ue['ecues'] ?? []) as $ecue) {
                    $rows->push([$semestre['label'], $ecue['name'], $ecue['code'], $ecue['moyenne']]);
                }
            }
            foreach (($semestre['matieres'] ?? []) as $matiere) {
                $rows->push([$semestre['label'], $matiere['name'], '', $matiere['moyenne']]);
            }
            $rows->push([$semestre['label'], 'Moyenne de période', '', $semestre['moyenne']]);
        }

        return new class($rows) implements FromCollection {
            public function __construct(private readonly Collection $rows) {}
            public function collection(): Collection { return $this->rows; }
        };
    }
}
