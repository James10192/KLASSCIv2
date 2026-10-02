<?php

namespace Tests\Feature\Comptabilite;

use App\Domain\Comptabilite\Relances\ListeDesRelances;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEcheancierRule;
use App\Models\ESBTPEcheancierRuleLine;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\EcheancierComputationService;
use App\Services\RelanceCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * L'arrivee sur la liste des relances recalcule l'echeancier de toute l'annee
 * (un encaissement fait a l'instant doit sortir l'etudiant de la liste). Ce
 * calcul retient ce qui ne depend que des references (configuration, montant
 * par statut, projection des tranches) : ces tests verifient qu'il rend
 * exactement ce que rend un calcul fait eleve par eleve, sans aucune memoire,
 * et que son nombre de requetes ne grandit pas avec l'annee.
 *
 * Aucune doublure : vraies categories, configurations, regles d'echeancier,
 * souscriptions, paiements valides et en attente.
 */
class RelancesArriveeTest extends TestCase
{
    use DatabaseTransactions;

    private ESBTPAnneeUniversitaire $annee;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_le_nombre_de_requetes_de_l_arrivee_ne_depend_pas_du_nombre_d_inscriptions(): void
    {
        $references = $this->references();
        $this->inscrire($references, 0, 12);
        $petite = $this->requetesDeLArrivee();

        $this->inscrire($references, 12, 48);
        $grande = $this->requetesDeLArrivee();

        $this->assertSame($petite, $grande, 'Quatre fois plus d\'inscriptions, autant de requetes.');
    }

    public function test_l_arrivee_rend_les_memes_lignes_et_compteurs_qu_un_calcul_eleve_par_eleve(): void
    {
        $this->inscrire($this->references(), 0, 60);

        $arrivee = $this->liste()->tranche($this->filtres(), 1, '/x', [], 1, true);
        $suite = $this->liste()->tranche($this->filtres(), 2, '/x', [], 1, false);

        $attendues = $this->lignesSansMemoire();
        $debiteurs = $attendues->filter(fn ($r) => $r->soldeRestant > 0)->values();

        $this->assertSame($debiteurs->count(), $arrivee['paginated']->total());
        $this->assertSame(
            $debiteurs->slice(0, ListeDesRelances::TRANCHE)->map(fn ($r) => $this->empreinte($r))->values()->all(),
            $arrivee['paginated']->getCollection()->map(fn ($r) => $this->empreinte($r))->values()->all(),
        );
        $this->assertSame(
            $debiteurs->slice(ListeDesRelances::TRANCHE, ListeDesRelances::TRANCHE)->map(fn ($r) => $this->empreinte($r))->values()->all(),
            $suite['paginated']->getCollection()->map(fn ($r) => $this->empreinte($r))->values()->all(),
        );

        $this->assertEquals([
            'total_impaye' => $debiteurs->sum(fn ($r) => $r->soldeRestant),
            'total_en_attente' => $attendues->sum(fn ($r) => $r->totalPayeEnAttente),
            'count_critical' => $attendues->where('risk', 'critical')->count(),
            'count_high' => $attendues->where('risk', 'high')->count(),
            'count_medium' => $attendues->where('risk', 'medium')->count(),
            'count_low' => $attendues->where('risk', 'low')->count(),
            'total_etudiants' => $debiteurs->count(),
        ], $arrivee['kpis']);
        $this->assertGreaterThan(0, $arrivee['kpis']['count_critical'] + $arrivee['kpis']['count_high']);

        // Une valeur absolue, pour ne pas seulement comparer le calcul a lui-meme :
        // le 3e eleve est non affecte, sans souscription ni paiement. Il doit
        // 50 000 (inscription) + 600 000 (scolarite, tarif non affecte) + 25 000.
        $troisieme = ESBTPInscription::where('annee_universitaire_id', $this->annee->id)->orderBy('id')->skip(2)->first();
        $this->assertSame('non_affecté', $troisieme->affectation_status);
        $ligne = $arrivee['paginated']->getCollection()->first(fn ($r) => $r->inscription->id === $troisieme->id)
            ?? $suite['paginated']->getCollection()->first(fn ($r) => $r->inscription->id === $troisieme->id);
        $this->assertNotNull($ligne, 'Debiteur, il figure dans les deux premieres tranches.');
        $this->assertSame(675000.0, $ligne->totalDu);
        $this->assertSame(675000.0, $ligne->remainingTotal);
        $this->assertSame(0.0, $ligne->totalPaye);
        $this->assertGreaterThan(0, $arrivee['kpis']['total_en_attente']);
    }

    public function test_le_choix_de_configuration_retenu_suit_le_jour(): void
    {
        $createur = User::factory()->create();
        [$filiere, $niveau, $classe] = $this->classe();
        $categorie = ESBTPFraisCategory::factory()->create();
        Carbon::setTestNow('2026-10-10 10:00:00');
        // Deux tarifs successifs (une configuration par annee : contrainte d'unicite).
        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create();
        foreach ([['2026-01-01', '2026-10-10', 100000, $this->annee], ['2026-10-11', null, 250000, $autreAnnee]] as [$debut, $fin, $montant, $annee]) {
            ESBTPFraisConfiguration::create([
                'frais_category_id' => $categorie->id, 'filiere_id' => $filiere->id, 'niveau_id' => $niveau->id,
                'annee_universitaire_id' => $annee->id, 'amount' => $montant, 'is_active' => true,
                'effective_date' => $debut, 'expiry_date' => $fin, 'created_by' => $createur->id,
            ]);
        }
        $inscription = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $this->annee->id, 'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id, 'classe_id' => $classe->id,
        ]);

        // Un seul service et les MEMES references sur deux jours : la memoire
        // attachee a ces references ne doit pas resservir le tarif de la veille.
        $calcul = app(EcheancierComputationService::class);
        $categories = collect([$categorie]);
        $configurations = ESBTPFraisConfiguration::where('frais_category_id', $categorie->id)->get()
            ->groupBy(fn ($c) => $c->frais_category_id.'_'.$c->filiere_id.'_'.$c->niveau_id);
        $total = fn () => collect($calcul->buildScheduleForInscription($inscription, $categories, $configurations, collect())['items'])->sum('amount');

        $this->assertEquals(100000, $total());
        Carbon::setTestNow('2026-10-12 10:00:00');
        $this->assertEquals(250000, $total());
    }

    private function liste(): ListeDesRelances
    {
        $this->app->forgetInstance(ListeDesRelances::class);

        return app(ListeDesRelances::class);
    }

    private function requetesDeLArrivee(): int
    {
        $liste = $this->liste();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $liste->tranche($this->filtres(), 1, '/x', [], 1, true);
        $n = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $n;
    }

    /**
     * La reference : chaque eleve calcule seul, par un service neuf, dans
     * l'ordre de la liste (plus recente d'abord, departage par identifiant).
     */
    private function lignesSansMemoire()
    {
        return ESBTPInscription::with(['etudiant', 'classe.filiere', 'paiements' => fn ($q) => $q->whereIn('status', ['validé', 'en_attente'])])
            ->where('annee_universitaire_id', $this->annee->id)
            ->latest('created_at')->orderByDesc('id')->get()
            ->map(function (ESBTPInscription $i) {
                $calcul = new RelanceCalculationService(
                    app(EcheancierComputationService::class),
                    app(\App\Services\EcheancierSnapshotService::class),
                );

                return $calcul->preloadForInscriptions(collect([$i]))->buildRow($i);
            });
    }

    private function empreinte(object $r): array
    {
        return [
            (int) $r->inscription->id, $r->totalDu, $r->totalPaye, $r->totalPayeEnAttente, $r->soldeRestant,
            $r->remainingTotal, $r->pourcentage, $r->expectedDueToDate, $r->paidDueToDate, $r->overdueDays, $r->risk,
        ];
    }

    private function filtres(): array
    {
        return ['search' => '', 'risk' => '', 'filiere_id' => '', 'classe_id' => '', 'annee_id' => $this->annee->id];
    }

    private function classe(?ESBTPFiliere $filiere = null, ?ESBTPNiveauEtude $niveau = null): array
    {
        $filiere ??= ESBTPFiliere::factory()->create();
        $niveau ??= ESBTPNiveauEtude::factory()->create();

        return [$filiere, $niveau, ESBTPClasse::factory()->create(['filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id])];
    }

    /**
     * Deux filieres x deux niveaux, trois frais obligatoires dont la scolarite
     * en trois tranches (regle d'echeancier), un frais optionnel.
     *
     * @return array{classes: list<array>, obligatoires: list<ESBTPFraisCategory>, optionnelle: ESBTPFraisCategory, createur: User}
     */
    private function references(): array
    {
        $createur = User::factory()->create();
        $niveaux = ESBTPNiveauEtude::factory()->count(2)->create();
        $obligatoires = collect([50000, 400000, 25000])->map(fn ($m) => ESBTPFraisCategory::factory()->create(['default_amount' => $m]));
        $classes = [];
        foreach (ESBTPFiliere::factory()->count(2)->create() as $f) {
            foreach ($niveaux as $n) {
                $classes[] = $this->classe($f, $n);
                foreach ($obligatoires as $rang => $categorie) {
                    $config = ESBTPFraisConfiguration::create([
                        'frais_category_id' => $categorie->id, 'filiere_id' => $f->id, 'niveau_id' => $n->id,
                        'annee_universitaire_id' => $this->annee->id, 'amount' => $categorie->default_amount,
                        'amount_non_affecte' => $rang === 1 ? 600000 : null,
                        'payment_deadline_days' => 30, 'is_active' => true, 'created_by' => $createur->id,
                    ]);
                    if ($rang === 1) {
                        $this->regleEnTroisTranches($config);
                    }
                }
            }
        }

        return [
            'classes' => $classes,
            'obligatoires' => $obligatoires->all(),
            'optionnelle' => ESBTPFraisCategory::factory()->optionnelle()->create(),
            'createur' => $createur,
        ];
    }

    private function regleEnTroisTranches(ESBTPFraisConfiguration $config): void
    {
        $regle = ESBTPEcheancierRule::create([
            'scope_type' => ESBTPEcheancierRule::SCOPE_CONFIGURATION, 'scope_id' => $config->id,
            'affectation_status' => ESBTPEcheancierRule::STATUS_ALL, 'priority' => 1, 'is_active' => true,
        ]);
        foreach ([[30, 15], [30, 120], [40, 240]] as $i => [$pct, $jours]) {
            ESBTPEcheancierRuleLine::create([
                'rule_id' => $regle->id, 'label' => 'Tranche '.($i + 1), 'sort_order' => $i,
                'amount_mode' => ESBTPEcheancierRuleLine::AMOUNT_MODE_PERCENT, 'amount_value' => $pct,
                'due_mode' => ESBTPEcheancierRuleLine::DUE_MODE_DAYS_AFTER_INSCRIPTION, 'due_value' => $jours,
                'grace_days' => 5, 'is_active' => true,
            ]);
        }
    }

    /**
     * Des eleves aux situations variees : statut d'affectation, date
     * d'inscription, frais optionnel souscrit, paiements valides et en attente.
     */
    private function inscrire(array $references, int $de, int $nombre): void
    {
        [$inscription, $scolarite] = [$references['obligatoires'][0], $references['obligatoires'][1]];
        for ($i = $de; $i < $de + $nombre; $i++) {
            [$f, $n, $c] = $references['classes'][$i % count($references['classes'])];
            $ins = ESBTPInscription::factory()->create([
                'annee_universitaire_id' => $this->annee->id, 'filiere_id' => $f->id, 'niveau_id' => $n->id,
                'classe_id' => $c->id, 'date_inscription' => now()->subDays(20 + ($i * 7) % 300),
                'created_at' => now()->subDays($i % 9)->startOfSecond(),
                'affectation_status' => ['affecté', 'réaffecté', 'non_affecté'][$i % 3],
            ]);
            if ($i % 4 === 0) {
                ESBTPFraisSubscription::factory()->create([
                    'inscription_id' => $ins->id, 'frais_category_id' => $references['optionnelle']->id,
                    'amount' => 30000, 'created_by' => $references['createur']->id,
                ]);
            }
            if ($i % 3 !== 2) {
                ESBTPPaiement::factory()->pour($ins)->surCategorie($scolarite->id)->montant(50000 * (1 + $i % 5))->create();
            }
            if ($i % 7 === 0) {
                ESBTPPaiement::factory()->pour($ins)->surCategorie($inscription->id)->montant(50000)->enAttente()->create();
            }
        }
    }
}
