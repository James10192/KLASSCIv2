<?php

namespace Database\Seeders\Demo;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPEcheancierRuleLine;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Étape 2 — barèmes financiers : catégories, configurations par
 * filière/niveau, règles d'échéancier multi-tranches.
 */
class FraisDemoData
{
    public function __construct(private readonly ?Command $command = null) {}

    /**
     * @param  array{annee: \App\Models\ESBTPAnneeUniversitaire, filieres: Collection, niveaux: Collection, classes: Collection}  $academic
     * @return array{categories: Collection, configurations: Collection, rules: Collection}
     */
    public function run(array $academic): array
    {
        $categories = $this->seedCategories();
        $configs    = $this->seedConfigurations($academic, $categories);
        $rules      = $this->seedEcheancierRules($configs);

        $this->command?->line(sprintf(
            '   • %d catégories · %d configurations · %d règles d\'échéancier',
            $categories->count(),
            $configs->count(),
            $rules->count(),
        ));

        return [
            'categories'     => $categories,
            'configurations' => $configs,
            'rules'          => $rules,
        ];
    }

    private function seedCategories(): Collection
    {
        $rows = [
            ['name' => 'Frais d\'inscription', 'code' => 'INSCRIPTION', 'is_mandatory' => true,  'sort' => 1, 'amount' => 75000,  'days' => 15, 'icon' => 'fa-user-plus'],
            ['name' => 'Scolarité',             'code' => 'SCOLARITE',   'is_mandatory' => true,  'sort' => 2, 'amount' => 600000, 'days' => 30, 'icon' => 'fa-graduation-cap'],
            ['name' => 'Frais d\'examen',       'code' => 'EXAMEN',      'is_mandatory' => true,  'sort' => 3, 'amount' => 50000,  'days' => 30, 'icon' => 'fa-file-alt'],
            ['name' => 'Bibliothèque',          'code' => 'BIBLIO',      'is_mandatory' => false, 'sort' => 4, 'amount' => 25000,  'days' => 60, 'icon' => 'fa-book'],
            ['name' => 'Activités sportives',   'code' => 'SPORT',       'is_mandatory' => false, 'sort' => 5, 'amount' => 15000,  'days' => 60, 'icon' => 'fa-running'],
        ];

        return collect($rows)->map(fn ($r) => ESBTPFraisCategory::updateOrCreate(
            ['code' => $r['code']],
            [
                'name'                  => $r['name'],
                'is_mandatory'          => $r['is_mandatory'],
                'is_active'             => true,
                'sort_order'            => $r['sort'],
                'default_amount'        => $r['amount'],
                'payment_deadline_days' => $r['days'],
                'icon'                  => $r['icon'],
                'category_type'         => $r['is_mandatory'] ? 'academic' : 'service',
            ]
        ));
    }

    /** @param array{annee: \App\Models\ESBTPAnneeUniversitaire, filieres: Collection, niveaux: Collection, classes: Collection} $academic */
    private function seedConfigurations(array $academic, Collection $categories): Collection
    {
        $configs = collect();
        $byCode = $categories->keyBy('code');
        $combos = $academic['classes']->map(fn ($c) => ['filiere_id' => $c->filiere_id, 'niveau_id' => $c->niveau_etude_id])->unique(fn ($r) => $r['filiere_id'] . ':' . $r['niveau_id']);

        foreach ($combos as $combo) {
            foreach ($categories as $cat) {
                $multiplier = $this->amountMultiplierFor($combo['niveau_id'], $academic['niveaux']);
                $amount = (float) round($cat->default_amount * $multiplier);

                $configs->push(ESBTPFraisConfiguration::updateOrCreate(
                    [
                        'frais_category_id'      => $cat->id,
                        'filiere_id'             => $combo['filiere_id'],
                        'niveau_id'              => $combo['niveau_id'],
                        'annee_universitaire_id' => $academic['annee']->id,
                    ],
                    [
                        'amount'                  => $amount,
                        'amount_affecte'          => $amount,
                        'amount_reaffecte'        => $amount,
                        'amount_non_affecte'      => $amount,
                        'payment_deadline_days'   => $cat->payment_deadline_days,
                        'installments_allowed'    => true,
                        'max_installments'        => 3,
                        'min_installment_amount'  => 50000,
                        'is_active'               => true,
                        'effective_date'          => $academic['annee']->start_date,
                    ]
                ));
            }
        }

        return $configs;
    }

    private function amountMultiplierFor(int $niveauId, Collection $niveaux): float
    {
        $niveau = $niveaux->firstWhere('id', $niveauId);
        return match ($niveau?->type) {
            'LMD' => 1.4,
            default => 1.0,
        };
    }

    /** Crée une règle d'échéancier 30/30/40 sur la catégorie Scolarité de chaque combo. */
    private function seedEcheancierRules(Collection $configurations): Collection
    {
        $rules = collect();
        $scolariteConfigs = $configurations->filter(fn ($c) => optional($c->fraisCategory ?? null)->code === 'SCOLARITE'
            || $c->frais_category_id === ESBTPFraisCategory::where('code', 'SCOLARITE')->value('id'));

        foreach ($scolariteConfigs as $config) {
            $rule = ESBTPEcheancierRule::updateOrCreate(
                [
                    'scope_type'         => ESBTPEcheancierRule::SCOPE_CONFIGURATION,
                    'scope_id'           => $config->id,
                    'affectation_status' => ESBTPEcheancierRule::STATUS_AFFECTE,
                ],
                [
                    'priority'      => 1,
                    'is_active'     => true,
                    'effective_from' => $config->effective_date,
                    'notes'          => 'Règle démo 30/30/40 — generated by PresentationDemoSeeder',
                ]
            );
            $this->ensureLines($rule);
            $rules->push($rule);
        }

        return $rules;
    }

    private function ensureLines(ESBTPEcheancierRule $rule): void
    {
        $lines = [
            ['label' => 'Tranche 1 — inscription', 'pct' => 30, 'days' => 15],
            ['label' => 'Tranche 2 — mi-année',    'pct' => 30, 'days' => 120],
            ['label' => 'Tranche 3 — fin d\'année','pct' => 40, 'days' => 240],
        ];

        foreach ($lines as $i => $line) {
            ESBTPEcheancierRuleLine::updateOrCreate(
                ['rule_id' => $rule->id, 'sort_order' => $i + 1],
                [
                    'label'        => $line['label'],
                    'amount_mode'  => ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT,
                    'amount_value' => $line['pct'],
                    'due_mode'     => ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION,
                    'due_value'    => (string) $line['days'],
                    'grace_days'   => 7,
                    'is_active'    => true,
                ]
            );
        }
    }
}
