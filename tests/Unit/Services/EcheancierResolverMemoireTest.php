<?php

namespace Tests\Unit\Services;

use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPEcheancierRuleLine;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Services\EcheancierComputationService;
use App\Services\EcheancierPaymentAllocationService;
use App\Services\EcheancierProjectionService;
use App\Services\EcheancierReadinessService;
use App\Services\EcheancierResolverService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Le calcul d'echeancier demandait la regle de chaque categorie pour chaque
 * inscription : quatre requetes par couple, donc une page de relances qui
 * grossissait avec l'effectif. Le resolveur retient desormais la regle par
 * (portee, statut, jour).
 *
 * Deux choses a tenir : la regle rendue est exactement celle de l'ancienne
 * requete, et le nombre de requetes ne depend plus du nombre d'inscriptions.
 *
 * Base SQLite en memoire, schema ecrit a la main : le test ne depend pas d'un
 * serveur MySQL.
 */
class EcheancierResolverMemoireTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Carbon::setTestNow('2026-10-02 10:00:00');

        Schema::create('esbtp_echeancier_rules', function (Blueprint $t) {
            $t->id();
            $t->string('scope_type', 40);
            $t->unsignedBigInteger('scope_id');
            $t->string('affectation_status', 30)->default('all');
            $t->unsignedInteger('priority')->default(100);
            $t->boolean('is_active')->default(true);
            $t->date('effective_from')->nullable();
            $t->date('effective_to')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
        });
        Schema::create('esbtp_echeancier_rule_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('rule_id');
            $t->string('label', 120);
            $t->unsignedInteger('sort_order')->default(1);
            $t->string('amount_mode', 20);
            $t->decimal('amount_value', 12, 2)->default(0);
            $t->string('due_mode', 20);
            $t->string('due_value', 20);
            $t->unsignedInteger('grace_days')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });

        $this->semerLesRegles();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_la_regle_rendue_est_celle_de_l_ancienne_requete_pour_chaque_portee(): void
    {
        $resolveur = new EcheancierResolverService();
        $statuts = [null, '', 'affecté', 'affecte', 'réaffecté', 'non_affecté', 'inconnu', 'all'];
        $portees = [];
        foreach ([1, 2, 3, 4, 5, 99] as $id) {
            $portees[] = [ESBTPEcheancierRule::SCOPE_CONFIGURATION, $id];
        }
        $portees[] = [ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT, 1];
        $portees[] = [ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT, 2];

        $compares = 0;
        // Deux passes : la seconde lit la memoire, elle doit rendre la meme chose.
        foreach ([1, 2] as $passe) {
            foreach ($portees as [$type, $id]) {
                foreach ($statuts as $statut) {
                    $attendue = $this->ancienneResolution($type, $id, $statut);
                    $obtenue = $resolveur->resolveByScope($type, $id, $statut);

                    $this->assertSame($attendue?->id, $obtenue?->id, "Passe {$passe}, {$type}#{$id}, statut " . var_export($statut, true));
                    $this->assertSame(
                        $attendue?->lines->pluck('id')->all(),
                        $obtenue?->lines->pluck('id')->all(),
                        "Lignes, {$type}#{$id}, statut " . var_export($statut, true)
                    );
                    $compares++;
                }
            }
        }

        $this->assertSame(128, $compares);
        // Et les cas couverts ne sont pas tous vides.
        $this->assertNotNull($resolveur->resolveByScope(ESBTPEcheancierRule::SCOPE_CONFIGURATION, 1, 'affecté'));
    }

    public function test_les_requetes_ne_croissent_plus_avec_le_nombre_d_inscriptions(): void
    {
        $dix = $this->requetesPourUnLot(10);
        $cent = $this->requetesPourUnLot(100);

        // Avant : 2 controles de table + regle + lignes par couple inscription × categorie
        // (3 categories) soit ~1200 requetes pour 100 inscriptions. Desormais borne par
        // le nombre de portees distinctes, identique pour 10 ou 100 inscriptions.
        $this->assertSame($dix, $cent, "10 inscriptions : {$dix} requetes, 100 : {$cent}");
        // Borne : 2 controles de table + 1 requete de portees + (regle + lignes) par
        // couple (portee, statut), soit au plus 6 portees × 3 statuts × 2.
        $this->assertLessThanOrEqual(3 + 6 * 3 * 2, $cent);
    }

    public function test_une_ecriture_sur_une_regle_vide_la_memoire(): void
    {
        $resolveur = new EcheancierResolverService();
        $avant = $resolveur->resolveByScope(ESBTPEcheancierRule::SCOPE_CONFIGURATION, 1, 'affecté');
        $this->assertNotNull($avant);

        $avant->update(['is_active' => false]);

        $apres = $resolveur->resolveByScope(ESBTPEcheancierRule::SCOPE_CONFIGURATION, 1, 'affecté');
        $this->assertNotSame($avant->id, $apres?->id);
        $this->assertSame($this->ancienneResolution(ESBTPEcheancierRule::SCOPE_CONFIGURATION, 1, 'affecté')?->id, $apres?->id);
    }

    public function test_le_mode_suit_une_ecriture_sur_les_regles(): void
    {
        $readiness = new EcheancierReadinessService();
        $this->assertSame(EcheancierReadinessService::MODE_CONFIGURED, $readiness->mode());

        ESBTPEcheancierRule::query()->get()->each->update(['is_active' => false]);

        $this->assertSame(EcheancierReadinessService::MODE_FALLBACK, $readiness->mode());
    }

    private function requetesPourUnLot(int $effectif): int
    {
        $service = new EcheancierComputationService(
            new EcheancierResolverService(),
            new EcheancierProjectionService(),
            new EcheancierPaymentAllocationService(),
        );
        $categories = collect([1, 2, 3])->map(fn ($id) => $this->categorie($id));
        // Configurations 1..3 pour la classe (10, 20) ; 4..6 pour (11, 20).
        $configurations = collect([
            $this->configuration(1, 1, 10), $this->configuration(2, 2, 10), $this->configuration(3, 3, 10),
            $this->configuration(4, 1, 11), $this->configuration(5, 2, 11), $this->configuration(6, 3, 11),
        ])->groupBy(fn ($c) => $c->frais_category_id . '_' . $c->filiere_id . '_' . $c->niveau_id);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $statuts = ['affecté', 'non_affecté', 'réaffecté'];
        for ($i = 1; $i <= $effectif; $i++) {
            $service->buildScheduleForInscription(
                $this->inscription($i, $i % 2 === 0 ? 10 : 11, $statuts[$i % 3]),
                $categories,
                $configurations,
                collect()
            );
        }
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    /** La requete du resolveur avant memoire, recopiee telle quelle. */
    private function ancienneResolution(string $scopeType, int $scopeId, ?string $statut): ?ESBTPEcheancierRule
    {
        $normalized = ESBTPEcheancierRule::normalizeStatus($statut);

        return ESBTPEcheancierRule::query()
            ->forScope($scopeType, $scopeId)
            ->active()
            ->validAt(now()->toDateString())
            ->whereIn('affectation_status', [$normalized, ESBTPEcheancierRule::STATUS_ALL])
            ->orderByRaw(
                'CASE WHEN affectation_status = ? THEN 0 WHEN affectation_status = ? THEN 1 ELSE 2 END',
                [$normalized, ESBTPEcheancierRule::STATUS_ALL]
            )
            ->orderBy('priority')
            ->orderByDesc('updated_at')
            ->with(['lines' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->first();
    }

    private function semerLesRegles(): void
    {
        $conf = ESBTPEcheancierRule::SCOPE_CONFIGURATION;
        // Portee 1 : regle specifique « affecte » + regle « all ».
        $this->regle($conf, 1, 'affecté', lignes: 3);
        $this->regle($conf, 1, 'all', lignes: 2);
        // Portee 2 : seulement « all », avec une ligne inactive au milieu.
        $r = $this->regle($conf, 2, 'all', lignes: 3);
        $r->lines()->where('sort_order', 2)->update(['is_active' => false]);
        // Portee 3 : regle inactive seulement.
        $this->regle($conf, 3, 'all', lignes: 1, actif: false);
        // Portee 4 : regle expiree + regle future + regle non affecte valide.
        $this->regle($conf, 4, 'all', lignes: 1, du: '2025-01-01', au: '2025-12-31');
        $this->regle($conf, 4, 'affecté', lignes: 1, du: '2027-01-01');
        $this->regle($conf, 4, 'non_affecté', lignes: 2, du: '2026-09-01');
        // Portee 5 : regle specifique « reaffecte » seulement.
        $this->regle($conf, 5, 'réaffecté', lignes: 2);
        // Options.
        $this->regle(ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT, 1, 'all', lignes: 2);
        $this->regle(ESBTPEcheancierRule::SCOPE_OPTION_ASSIGNMENT, 2, 'non_affecté', lignes: 1);
    }

    private function regle(string $type, int $scopeId, string $statut, int $lignes, bool $actif = true, ?string $du = null, ?string $au = null): ESBTPEcheancierRule
    {
        $regle = ESBTPEcheancierRule::create([
            'scope_type' => $type,
            'scope_id' => $scopeId,
            'affectation_status' => $statut,
            'priority' => 100,
            'is_active' => $actif,
            'effective_from' => $du,
            'effective_to' => $au,
        ]);
        // Ordre d'insertion inverse de sort_order : l'ordre rendu doit venir du tri.
        for ($o = $lignes; $o >= 1; $o--) {
            ESBTPEcheancierRuleLine::create([
                'rule_id' => $regle->id,
                'label' => "Tranche {$o}",
                'sort_order' => $o,
                'amount_mode' => ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT,
                'amount_value' => round(100 / $lignes, 2),
                'due_mode' => ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION,
                'due_value' => (string) (30 * $o),
                'is_active' => true,
            ]);
        }

        return $regle;
    }

    private function categorie(int $id): ESBTPFraisCategory
    {
        $c = new ESBTPFraisCategory();
        $c->id = $id;
        $c->name = "Categorie {$id}";
        $c->is_mandatory = true;
        $c->payment_deadline_days = 30;
        $c->default_amount = 100_000;

        return $c;
    }

    private function configuration(int $id, int $categorieId, int $filiereId): ESBTPFraisConfiguration
    {
        $cfg = new ESBTPFraisConfiguration();
        $cfg->id = $id;
        $cfg->frais_category_id = $categorieId;
        $cfg->filiere_id = $filiereId;
        $cfg->niveau_id = 20;
        $cfg->amount = 300_000;
        $cfg->amount_affecte = 300_000;
        $cfg->amount_reaffecte = 300_000;
        $cfg->amount_non_affecte = 300_000;
        $cfg->payment_deadline_days = 30;
        $cfg->is_active = true;

        return $cfg;
    }

    private function inscription(int $id, int $filiereId, string $statut): ESBTPInscription
    {
        $i = new ESBTPInscription();
        $i->id = $id;
        $i->filiere_id = $filiereId;
        $i->niveau_id = 20;
        $i->affectation_status = $statut;
        $i->date_inscription = '2026-09-15';

        return $i;
    }
}
