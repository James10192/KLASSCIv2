<?php

namespace Database\Seeders\Demo;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Console\Command;

/**
 * Étape 1 — squelette académique du tenant presentation.
 * Crée année courante, filières, niveaux, classes.
 *
 * @return array{annee: ESBTPAnneeUniversitaire, filieres: \Illuminate\Support\Collection, niveaux: \Illuminate\Support\Collection, classes: \Illuminate\Support\Collection}
 */
class AcademicDemoData
{
    public function __construct(private readonly ?Command $command = null) {}

    /**
     * @return array{annee: ESBTPAnneeUniversitaire, filieres: \Illuminate\Support\Collection, niveaux: \Illuminate\Support\Collection, classes: \Illuminate\Support\Collection}
     */
    public function run(): array
    {
        $annee = $this->seedAnnee();
        $filieres = $this->seedFilieres();
        $niveaux = $this->seedNiveaux();
        $classes = $this->seedClasses($annee, $filieres, $niveaux);

        $this->command?->line(sprintf(
            '   • Année %s · %d filières · %d niveaux · %d classes',
            $annee->name,
            $filieres->count(),
            $niveaux->count(),
            $classes->count(),
        ));

        return compact('annee', 'filieres', 'niveaux', 'classes');
    }

    private function seedAnnee(): ESBTPAnneeUniversitaire
    {
        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);

        return ESBTPAnneeUniversitaire::updateOrCreate(
            ['name' => '2025-2026'],
            [
                'start_date'  => '2025-09-01',
                'end_date'    => '2026-07-31',
                'is_current'  => true,
                'is_active'   => true,
                'description' => 'Année universitaire courante (démo)',
            ]
        );
    }

    private function seedFilieres(): \Illuminate\Support\Collection
    {
        $rows = [
            ['name' => 'BTS Informatique de Gestion', 'code' => 'BTS-IG'],
            ['name' => 'BTS Comptabilité Gestion',     'code' => 'BTS-CG'],
            ['name' => 'Licence Génie Civil',           'code' => 'L-GC'],
            ['name' => 'Master Management & RH',        'code' => 'M-MRH'],
        ];

        return collect($rows)->map(fn ($r) => ESBTPFiliere::updateOrCreate(
            ['code' => $r['code']],
            ['name' => $r['name'], 'is_active' => true, 'is_tronc_commun' => false]
        ));
    }

    private function seedNiveaux(): \Illuminate\Support\Collection
    {
        $rows = [
            ['name' => '1ère année', 'code' => 'N1', 'year' => 1, 'type' => 'BTS'],
            ['name' => '2ème année', 'code' => 'N2', 'year' => 2, 'type' => 'BTS'],
            ['name' => 'Licence 3',  'code' => 'L3', 'year' => 3, 'type' => 'LMD'],
            ['name' => 'Master 1',   'code' => 'M1', 'year' => 4, 'type' => 'LMD'],
            ['name' => 'Master 2',   'code' => 'M2', 'year' => 5, 'type' => 'LMD'],
        ];

        return collect($rows)->map(fn ($r) => ESBTPNiveauEtude::updateOrCreate(
            ['code' => $r['code']],
            [
                'name'      => $r['name'],
                'libelle'   => $r['name'],
                'year'      => $r['year'],
                'type'      => $r['type'],
                'is_active' => true,
            ]
        ));
    }

    private function seedClasses(ESBTPAnneeUniversitaire $annee, \Illuminate\Support\Collection $filieres, \Illuminate\Support\Collection $niveaux): \Illuminate\Support\Collection
    {
        $byCode = $niveaux->keyBy('code');
        $combos = [
            ['filiere' => 'BTS-IG', 'niveau' => 'N1', 'name' => '1BTS IG A', 'capacity' => 35],
            ['filiere' => 'BTS-IG', 'niveau' => 'N1', 'name' => '1BTS IG B', 'capacity' => 35],
            ['filiere' => 'BTS-IG', 'niveau' => 'N2', 'name' => '2BTS IG A', 'capacity' => 30],
            ['filiere' => 'BTS-CG', 'niveau' => 'N1', 'name' => '1BTS CG A', 'capacity' => 35],
            ['filiere' => 'BTS-CG', 'niveau' => 'N2', 'name' => '2BTS CG A', 'capacity' => 30],
            ['filiere' => 'L-GC',   'niveau' => 'L3', 'name' => 'L3 Génie Civil',  'capacity' => 25],
            ['filiere' => 'M-MRH',  'niveau' => 'M1', 'name' => 'M1 Management',   'capacity' => 20],
            ['filiere' => 'M-MRH',  'niveau' => 'M2', 'name' => 'M2 Management',   'capacity' => 20],
        ];

        return collect($combos)->map(function ($c) use ($annee, $filieres, $byCode) {
            $filiere = $filieres->firstWhere('code', $c['filiere']);
            $niveau  = $byCode->get($c['niveau']);

            return ESBTPClasse::updateOrCreate(
                [
                    'name'                    => $c['name'],
                    'annee_universitaire_id'  => $annee->id,
                ],
                [
                    'code'             => $c['name'],
                    'filiere_id'       => $filiere->id,
                    'niveau_etude_id'  => $niveau->id,
                    'places_totales'   => $c['capacity'],
                    'places_occupees'  => 0,
                    'is_active'        => true,
                    'systeme_academique' => $niveau->type === 'LMD' ? 'LMD' : 'BTS',
                    'description'      => $c['name'] . ' (démo)',
                ]
            );
        });
    }
}
