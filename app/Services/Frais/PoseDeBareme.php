<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisOption;
use App\Models\Setting;
use App\Services\FraisCacheService;
use App\Services\FraisConfigurationWriter;
use App\Services\TenantScolariteSettings;
use Illuminate\Support\Facades\DB;

/**
 * Pose un barème : les catégories de frais (créées ou mises à jour par leur
 * code) puis leurs montants par portée (système, parcours ou filière, niveau).
 *
 * Vivait dans CLIFraisController::poserBareme(). Extrait pour que la CLI et
 * Nanan écrivent par le même chemin. Les montants passent par
 * FraisConfigurationWriter, comme l'écran de configuration des frais, et le
 * cache des configurations est invalidé comme il le fait.
 *
 * Entrée : le tableau déjà validé par la CLI (categories, configurations,
 * confirmer_statut), avec les identifiants de portée résolus.
 */
class PoseDeBareme
{
    public function __construct(
        private FraisConfigurationWriter $writer,
        private FraisCacheService $cache,
    ) {
    }

    /** Ce qui rend le barème inapplicable, sans rien écrire. */
    public function refus(array $bareme): ?string
    {
        $codes = collect($bareme['categories'] ?? [])->keyBy(fn ($c) => strtoupper((string) $c['code']));
        foreach ($bareme['configurations'] ?? [] as $ligne) {
            if (! $codes->has(strtoupper((string) $ligne['category_code']))) {
                return 'Categorie inconnue dans configurations: '.$ligne['category_code'];
            }
        }

        return null;
    }

    /** La portée d'une ligne de configuration, telle que l'écran l'enregistre. */
    public static function portee(array $ligne): array
    {
        $systeme = strtoupper((string) $ligne['systeme']);

        return [
            'systeme' => $systeme,
            'parcours_id' => $ligne['parcours_id'] ?? null,
            'filiere_id' => $ligne['filiere_id'] ?? null,
            'niveau_id' => $ligne['niveau_id'],
        ];
    }

    /** Le montant global actuel d'une catégorie sur une portée, ou null s'il n'y en a pas. */
    public static function montantActuel(int $categorieId, array $portee): ?float
    {
        $existante = ESBTPFraisConfiguration::queryForScope($portee)
            ->where('frais_category_id', $categorieId)
            ->whereNull('annee_universitaire_id')
            ->first();

        return $existante ? (float) $existante->amount : null;
    }

    /**
     * @return array{categories: int, configurations_creees: int, configurations_maj: int}
     */
    public function appliquer(array $bareme, int $auteur): array
    {
        return DB::transaction(function () use ($bareme, $auteur) {
            $parCode = [];

            foreach ($bareme['categories'] as $i => $cat) {
                $code = strtoupper($cat['code']);
                $modele = ESBTPFraisCategory::query()->where('code', $code)->first();
                if (! $modele) {
                    $modele = ESBTPFraisCategory::create([
                        'name' => $cat['name'],
                        'code' => $code,
                        'is_mandatory' => (bool) ($cat['is_mandatory'] ?? true),
                        'audience' => $cat['audience'] ?? ESBTPFraisCategory::AUDIENCE_TOUS,
                        'category_type' => $cat['category_type'] ?? 'academic',
                        'default_amount' => (float) ($cat['default_amount'] ?? 0),
                        'payment_deadline_days' => 30,
                        'is_active' => true,
                        'sort_order' => $i + 1,
                        'accepts_in_kind' => false,
                    ]);
                    ESBTPFraisOption::create([
                        'configuration_id' => null,
                        'name' => 'Standard',
                        'description' => 'Option standard pour '.$modele->name,
                        'additional_amount' => 0,
                        'is_default' => true,
                        'is_active' => true,
                        'available_from' => now(),
                        'sort_order' => 1,
                    ]);
                } else {
                    $modele->fill([
                        'name' => $cat['name'],
                        'is_mandatory' => (bool) ($cat['is_mandatory'] ?? $modele->is_mandatory),
                        'audience' => $cat['audience'] ?? $modele->audience,
                        'category_type' => $cat['category_type'] ?? $modele->category_type,
                        'default_amount' => $cat['default_amount'] ?? $modele->default_amount,
                        'is_active' => true,
                    ])->save();
                }
                $parCode[$code] = $modele;
            }

            $parPortee = [];
            foreach ($bareme['configurations'] as $ligne) {
                $portee = self::portee($ligne);
                $cle = implode('|', [$portee['systeme'], $portee['parcours_id'] ?? '', $portee['filiere_id'] ?? '', $portee['niveau_id']]);
                $parPortee[$cle]['scope'] = $portee;
                $cat = $parCode[strtoupper($ligne['category_code'])];
                $parPortee[$cle]['categories'][$cat->id] = [
                    'amount' => $ligne['amount'],
                    'amount_affecte' => $ligne['amount_affecte'] ?? $ligne['amount'],
                    'amount_reaffecte' => $ligne['amount_reaffecte'] ?? $ligne['amount'],
                    'amount_non_affecte' => $ligne['amount_non_affecte'] ?? $ligne['amount'],
                    'deadline_days' => 30,
                ];
            }

            $created = 0;
            $updated = 0;
            foreach ($parPortee as $bloc) {
                $resultat = $this->writer->persistCategories($bloc['scope'], $bloc['categories'], 'global', null, $auteur, 'overwrite_all');
                $created += $resultat['created'];
                $updated += $resultat['updated'];
                $this->cache->invalidateConfigurationCache(
                    $bloc['scope']['filiere_id'],
                    $bloc['scope']['niveau_id'],
                    null,
                    $bloc['scope']['systeme'],
                    $bloc['scope']['parcours_id'],
                );
            }

            if ($bareme['confirmer_statut'] ?? false) {
                Setting::updateOrCreate(
                    ['key' => TenantScolariteSettings::CONFIRMER_STATUT_ETABLISSEMENT],
                    ['value' => '1', 'type' => 'boolean', 'group' => 'scolarite', 'is_required' => false]
                );
                if (method_exists(Setting::class, 'clearCache')) {
                    Setting::clearCache();
                }
            }

            return ['categories' => count($parCode), 'configurations_creees' => $created, 'configurations_maj' => $updated];
        });
    }
}
