<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Frais\AnnulerDepotNature;
use App\Domain\Assistant\Actions\Frais\PoserBareme;
use App\Domain\Assistant\Actions\Frais\RepartirTropPercu;
use App\Domain\Assistant\Actions\Inscriptions\DeplacerEtudiants;
use App\Domain\Assistant\Actions\Inscriptions\ValiderInscriptions;
use App\Domain\Assistant\Actions\Paiements\AnnulerVersement;
use App\Domain\Assistant\Actions\Paiements\RestaurerVersement;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Helpers\SettingsHelper;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Lot A, appris à Nanan : valider et déplacer des inscriptions, annuler ou
 * restaurer un versement, annuler un dépôt en nature, répartir un trop-versé,
 * poser un barème. Chaque fois : rien n'est écrit avant « Valider », tout l'est
 * après, par le même chemin que la CLI.
 */
class NananLotATest extends TestCase
{
    use DatabaseTransactions;

    private const DROITS = [
        'inscriptions.validate', 'students.edit', 'paiements.avoir', 'trash.view', 'paiements.restore',
        'inscriptions.in_kind.mark', 'paiements.reventiler', 'frais.configure', 'frais.create', 'frais.edit', 'system.manage',
    ];

    private const OUTILS = [
        'proposer_validation_inscriptions', 'proposer_deplacement_etudiants', 'proposer_annulation_versement',
        'proposer_restauration_versement', 'proposer_annulation_depot_nature', 'proposer_repartition_trop_percu', 'proposer_pose_bareme',
    ];

    private User $admin;
    private int $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);
        Role::findOrCreate('superAdmin', 'web');
        foreach (self::DROITS as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = $this->utilisateur();
        $this->admin->assignRole('superAdmin');

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = (int) ESBTPAnneeUniversitaire::factory()->create(['is_current' => true])->id;
        SettingsHelper::setOrCreate('comptabilite.period_locked_until', '', 'comptabilite', 'string');
        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
    }

    private function valider(array $resultat, ?User $qui = null, string $statut = 'executee'): void
    {
        $this->assertSame('approbation', $resultat['widget']['kind'] ?? null, json_encode($resultat, JSON_UNESCAPED_UNICODE));
        $this->actingAs($qui ?? $this->admin)
            ->postJson($resultat['widget']['valider_url'], ['jeton' => $resultat['widget']['jeton']])
            ->assertJson(['statut' => $statut]);
    }

    private function refuse(array $resultat, string $attendu): void
    {
        $this->assertArrayNotHasKey('widget', $resultat, json_encode($resultat, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString($attendu, implode(' ', $resultat['manques'] ?? []));
    }

    private function classe(string $code): ESBTPClasse
    {
        return ESBTPClasse::factory()->create(['code' => $code, 'name' => str_replace('_', ' ', $code)]);
    }

    private function inscription(ESBTPClasse $classe, array $attributs = []): ESBTPInscription
    {
        return ESBTPInscription::factory()->create($attributs + [
            'classe_id' => $classe->id, 'annee_universitaire_id' => $this->annee,
            'etudiant_id' => ESBTPEtudiant::factory()->create(['matricule' => 'LA'.Str::upper(Str::random(8))])->id,
        ]);
    }

    private function versement(ESBTPInscription $inscription, array $attributs = []): ESBTPPaiement
    {
        return ESBTPPaiement::factory()->pour($inscription)->create($attributs + ['montant' => 47000, 'numero_recu' => 'REC-LA-'.Str::upper(Str::random(6))]);
    }

    // --- Valider des inscriptions --------------------------------------------------

    public function test_valider_une_classe_ne_touche_que_les_inscriptions_au_versement_valide(): void
    {
        $classe = $this->classe('LA_VAL_1A');
        $attente = ['status' => 'en_attente', 'workflow_step' => 'en_validation'];
        $payee = $this->inscription($classe, $attente);
        $this->versement($payee);
        $enAttente = $this->inscription($classe, $attente);
        $this->versement($enAttente, ['status' => 'en_attente']);
        $sans = $this->inscription($classe, $attente);

        $r = app(ValiderInscriptions::class)->executeAuthorized(['classe' => 'la_val_1a'], $this->admin);
        $this->assertStringContainsString('2 inscription(s) laissée(s)', implode(' ', $r['avertissements']));
        $this->assertSame('en_attente', $payee->fresh()->status, 'rien avant Valider');
        $this->valider($r);

        $this->assertSame(['active', 'etudiant_cree'], [$payee->fresh()->status, $payee->fresh()->workflow_step]);
        $this->assertSame('en_attente', $enAttente->fresh()->status, 'un versement en attente ne valide jamais');
        $this->assertSame('en_attente', $sans->fresh()->status);
    }

    public function test_valider_sans_versement_valide_est_refuse(): void
    {
        $classe = $this->classe('LA_VAL_1B');
        $i = $this->inscription($classe, ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($i, ['status' => 'en_attente']);

        $this->refuse(app(ValiderInscriptions::class)->executeAuthorized(['matricules' => [$i->etudiant->matricule]], $this->admin), 'versement encore en attente');
        $this->refuse(app(ValiderInscriptions::class)->executeAuthorized([], $this->admin), 'matricules');
    }

    public function test_un_versement_annule_entre_proposition_et_validation_perime_la_proposition(): void
    {
        $i = $this->inscription($this->classe('LA_VAL_1C'), ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $versement = $this->versement($i);
        $r = app(ValiderInscriptions::class)->executeAuthorized(['inscriptions' => [$i->id]], $this->admin);

        $versement->update(['status' => 'rejeté']);
        $this->valider($r, null, 'perimee');
        $this->assertSame('en_attente', $i->fresh()->status);
    }

    /** Une étape du dossier qui bouge sans rendre l'inscription inéligible : seul l'état la voit. */
    public function test_un_dossier_avance_entre_proposition_et_validation_perime_la_proposition(): void
    {
        $i = $this->inscription($this->classe('LA_VAL_1D'), ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($i);
        $r = app(ValiderInscriptions::class)->executeAuthorized(['inscriptions' => [$i->id]], $this->admin);

        $i->update(['workflow_step' => 'documents_complets']);
        $this->valider($r, null, 'perimee');
        $this->assertSame('en_attente', $i->fresh()->status);
    }

    /** La CLI juge par le même prédicat que Nanan. */
    public function test_la_cli_valide_par_le_meme_predicat(): void
    {
        $classe = $this->classe('LA_VAL_CLI');
        $attente = ['status' => 'en_attente', 'workflow_step' => 'en_validation'];
        $enAttente = $this->inscription($classe, $attente);
        $this->versement($enAttente, ['status' => 'en_attente']);
        $payee = $this->inscription($classe, $attente);
        $this->versement($payee);
        Sanctum::actingAs($this->utilisateur(), ['cli:read', 'cli:write']);

        $this->postJson("/api/cli/inscriptions/{$enAttente->id}/validate")->assertStatus(422)->assertJsonPath('message', 'Cannot validate: payment is still pending (en_attente)');
        $this->postJson('/api/cli/inscriptions/validate-bulk', ['classe_id' => $classe->id])->assertOk()
            ->assertJsonPath('data.summary.validated', 1)
            ->assertJsonPath('data.skipped.0.reason', 'paiement_en_attente');
        $this->assertSame('active', $payee->fresh()->status);
    }

    // --- Changer de classe ---------------------------------------------------------

    public function test_deplacer_un_eleve_de_1a_en_1b(): void
    {
        $a = $this->classe('LA_MOV_1A');
        $b = $this->classe('LA_MOV_1B');
        $i = $this->inscription($a);

        $r = app(DeplacerEtudiants::class)->executeAuthorized(['deplacements' => [['matricule' => $i->etudiant->matricule, 'vers' => 'LA_MOV_1B', 'depuis' => 'LA_MOV_1A']]], $this->admin);
        $this->assertSame($a->id, (int) $i->fresh()->classe_id, 'rien avant Valider');
        $this->valider($r);

        $this->assertSame($b->id, (int) $i->fresh()->classe_id);
        $this->assertSame('réaffecté', $i->fresh()->affectation_status);
    }

    public function test_deplacer_sans_classe_d_arrivee_ou_depuis_la_mauvaise_classe_demande(): void
    {
        $a = $this->classe('LA_MOV_2A');
        $this->classe('LA_MOV_2B');
        $i = $this->inscription($a);

        $this->refuse(app(DeplacerEtudiants::class)->executeAuthorized(['deplacements' => [['matricule' => $i->etudiant->matricule]]], $this->admin), 'Vers quelle classe');
        $this->refuse(app(DeplacerEtudiants::class)->executeAuthorized(['deplacements' => [['matricule' => $i->etudiant->matricule, 'vers' => 'LA_MOV_2A']]], $this->admin), 'déjà en');
        $this->refuse(app(DeplacerEtudiants::class)->executeAuthorized(['deplacements' => [['matricule' => $i->etudiant->matricule, 'vers' => 'LA_MOV_2B', 'depuis' => 'LA_MOV_2B']]], $this->admin), "n'est pas inscrit");
        $this->refuse(app(DeplacerEtudiants::class)->executeAuthorized(['deplacements' => [['matricule' => 'INCONNU0', 'vers' => 'LA_MOV_2B']]], $this->admin), 'introuvable');
    }

    /** La CLI lit le déplacement par le même examen que Nanan. */
    public function test_la_cli_deplace_par_le_meme_examen(): void
    {
        $a = $this->classe('LA_MOV_4A');
        $b = $this->classe('LA_MOV_4B');
        $i = $this->inscription($a);
        Sanctum::actingAs($this->utilisateur(), ['cli:read', 'cli:write']);
        $move = ['etudiant_id' => $i->etudiant_id, 'from_classe_id' => $a->id, 'to_classe_id' => $b->id];

        $this->postJson('/api/cli/inscriptions/move', ['moves' => [$move]])->assertOk()->assertJsonPath('data.summary.moved', 1);
        $this->assertSame($b->id, (int) $i->fresh()->classe_id);
        $this->postJson('/api/cli/inscriptions/move', ['moves' => [$move]])->assertOk()
            ->assertJsonPath('data.errors.0.reason', 'no_inscription_in_source_class');
    }

    public function test_deplacer_perime_si_l_eleve_a_change_de_classe_entre_temps(): void
    {
        $a = $this->classe('LA_MOV_3A');
        $this->classe('LA_MOV_3B');
        $c = $this->classe('LA_MOV_3C');
        $i = $this->inscription($a);
        $r = app(DeplacerEtudiants::class)->executeAuthorized(['deplacements' => [['matricule' => $i->etudiant->matricule, 'vers' => 'LA_MOV_3B']]], $this->admin);

        $i->update(['classe_id' => $c->id]);
        $this->valider($r, null, 'perimee');
        $this->assertSame($c->id, (int) $i->fresh()->classe_id);
    }

    // --- Annuler un versement par un avoir -----------------------------------------

    public function test_annuler_un_versement_emet_un_avoir_total_apres_valider(): void
    {
        $v = $this->versement($this->inscription($this->classe('LA_AV_1A')));

        $r = app(AnnulerVersement::class)->executeAuthorized(['numero_recu' => strtolower($v->numero_recu), 'avoir_kind' => 'refund', 'motif' => 'Versement saisi deux fois au guichet'], $this->admin);
        $this->assertSame(0, ESBTPPaiement::where('parent_paiement_id', $v->id)->count(), 'rien avant Valider');
        $this->valider($r);

        $avoir = ESBTPPaiement::where('parent_paiement_id', $v->id)->sole();
        $this->assertSame([47000.0, 'refund'], [(float) $avoir->montant, $avoir->avoir_kind]);
        $this->assertNotSoftDeleted($v);
    }

    public function test_annuler_exige_le_sort_de_l_argent_le_motif_et_respecte_la_periode_close(): void
    {
        $v = $this->versement($this->inscription($this->classe('LA_AV_1B')), ['date_paiement' => '2026-01-10']);

        $r = app(AnnulerVersement::class)->executeAuthorized(['numero_recu' => $v->numero_recu, 'motif' => 'court'], $this->admin);
        $this->refuse($r, 'crédit');
        $this->refuse($r, 'motif');

        SettingsHelper::setOrCreate('comptabilite.period_locked_until', '2026-01-31', 'comptabilite', 'string');
        $this->refuse(app(AnnulerVersement::class)->executeAuthorized(['numero_recu' => $v->numero_recu, 'avoir_kind' => 'credit', 'motif' => 'Saisi sur le mauvais élève'], $this->admin), 'verrouillée');
        $this->assertSame(0, ESBTPPaiement::where('parent_paiement_id', $v->id)->count());
    }

    public function test_annuler_perime_si_un_avoir_a_ete_emis_entre_temps(): void
    {
        $v = $this->versement($this->inscription($this->classe('LA_AV_1C')));
        $r = app(AnnulerVersement::class)->executeAuthorized(['numero_recu' => $v->numero_recu, 'avoir_kind' => 'credit', 'motif' => 'Versement saisi sur le mauvais élève'], $this->admin);

        app(\App\Services\AvoirService::class)->issue($v, 10000, 'credit', 'Avoir partiel émis à l’écran', $this->admin->id);
        $this->valider($r, null, 'perimee');
        $this->assertSame(1, ESBTPPaiement::where('parent_paiement_id', $v->id)->count());
    }

    // --- Restaurer un versement supprimé -------------------------------------------

    public function test_restaurer_un_versement_supprime_et_son_inscription(): void
    {
        $i = $this->inscription($this->classe('LA_RS_1A'));
        $v = $this->versement($i);
        $v->delete();
        $i->delete();

        $this->refuse(app(AnnulerVersement::class)->executeAuthorized(['numero_recu' => $v->numero_recu, 'avoir_kind' => 'credit', 'motif' => 'Motif suffisamment long'], $this->admin), 'restaure');
        $r = app(RestaurerVersement::class)->executeAuthorized(['numero_recu' => $v->numero_recu], $this->admin);
        $this->assertStringContainsString('inscription', implode(' ', $r['avertissements']));
        $this->assertSoftDeleted($v);
        $this->valider($r);

        $this->assertNotSoftDeleted($v);
        $this->assertNotSoftDeleted($i);
        $this->refuse(app(RestaurerVersement::class)->executeAuthorized(['numero_recu' => $v->numero_recu], $this->admin), "n'est pas supprimé");
    }

    // --- Dépôt en nature -----------------------------------------------------------

    public function test_annuler_un_depot_en_nature_le_rend_du(): void
    {
        $i = $this->inscription($this->classe('LA_DN_1A'));
        $rame = ESBTPFraisCategory::factory()->create(['name' => 'Rame de papier', 'accepts_in_kind' => true]);
        $s = ESBTPFraisSubscription::factory()->create(['inscription_id' => $i->id, 'frais_category_id' => $rame->id, 'amount' => 5000,
            'satisfied_in_kind' => true, 'created_by' => $this->admin->id]);

        $r = app(AnnulerDepotNature::class)->executeAuthorized(['matricule' => $i->etudiant->matricule, 'categorie' => 'rame'], $this->admin);
        $this->assertTrue((bool) $s->fresh()->satisfied_in_kind, 'rien avant Valider');
        $this->valider($r);
        $this->assertFalse((bool) $s->fresh()->satisfied_in_kind);
    }

    public function test_un_depot_deja_verse_ne_s_annule_pas(): void
    {
        $i = $this->inscription($this->classe('LA_DN_1B'));
        $rame = ESBTPFraisCategory::factory()->create(['accepts_in_kind' => true]);
        ESBTPFraisSubscription::factory()->create(['inscription_id' => $i->id, 'frais_category_id' => $rame->id, 'amount' => 5000,
            'satisfied_in_kind' => true, 'created_by' => $this->admin->id]);
        $this->versement($i, ['frais_category_id' => $rame->id]);

        $this->refuse(app(AnnulerDepotNature::class)->executeAuthorized(['inscription_id' => $i->id], $this->admin), 'versement validé');
    }

    // --- Répartir un trop-versé ----------------------------------------------------

    private function tropVerse(): array
    {
        $i = $this->inscription($this->classe('LA_TP_'.Str::upper(Str::random(4))));
        $scolarite = ESBTPFraisCategory::factory()->create(['sort_order' => 1]);
        $tenue = ESBTPFraisCategory::factory()->create(['sort_order' => 2]);
        foreach ([[$scolarite, 100000], [$tenue, 50000]] as [$cat, $montant]) {
            ESBTPFraisSubscription::factory()->create(['inscription_id' => $i->id, 'frais_category_id' => $cat->id, 'amount' => $montant, 'created_by' => $this->admin->id]);
        }
        $v = $this->versement($i, ['montant' => 150000, 'frais_category_id' => $scolarite->id, 'date_paiement' => '2026-01-10']);

        return [$i, $v, $tenue];
    }

    public function test_repartir_un_trop_verse_impute_le_surplus_sur_l_autre_frais(): void
    {
        [$i, $v, $tenue] = $this->tropVerse();

        $this->refuse(app(RepartirTropPercu::class)->executeAuthorized(['inscription_id' => $i->id], $this->admin), 'motif');
        $r = app(RepartirTropPercu::class)->executeAuthorized(['matricule' => $i->etudiant->matricule, 'motif' => 'Versement unique pour scolarité et tenue'], $this->admin);
        $this->assertSame(0, ESBTPPaiementAllocation::where('paiement_id', $v->id)->count(), 'rien avant Valider');
        $this->valider($r);

        $this->assertSame(50000.0, (float) ESBTPPaiementAllocation::where('paiement_id', $v->id)->where('frais_category_id', $tenue->id)->value('montant'));
        $this->refuse(app(RepartirTropPercu::class)->executeAuthorized(['inscription_id' => $i->id, 'motif' => 'Deuxième passage pour rien'], $this->admin), 'Rien à répartir');
    }

    public function test_repartir_refuse_un_versement_d_une_periode_close(): void
    {
        [$i] = $this->tropVerse();
        $comptable = $this->utilisateur();
        $comptable->givePermissionTo('paiements.reventiler');
        SettingsHelper::setOrCreate('comptabilite.period_locked_until', '2026-01-31', 'comptabilite', 'string');
        $this->actingAs($comptable);

        $this->refuse(app(RepartirTropPercu::class)->executeAuthorized(['inscription_id' => $i->id, 'motif' => 'Versement unique pour deux frais'], $comptable), 'verrouillée');
    }

    // --- Barème --------------------------------------------------------------------

    public function test_poser_un_bareme_par_codes_puis_valider(): void
    {
        $filiere = ESBTPFiliere::factory()->create(['code' => 'LABAT']);
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'LAN1', 'year' => 1, 'type' => 'BTS']);
        $scolarite = ESBTPFraisCategory::factory()->create(['code' => 'LASCO', 'name' => 'Scolarité LA']);

        $r = app(PoserBareme::class)->executeAuthorized(['configurations' => [
            ['categorie' => 'lasco', 'filiere' => 'LABAT', 'niveau' => 'LAN1', 'montant' => 450000],
            ['categorie' => 'LATEN', 'filiere' => 'LABAT', 'niveau' => 'LAN1', 'montant' => 25000],
        ], 'categories' => [['code' => 'LATEN', 'name' => 'Tenue LA']]], $this->admin);
        $this->assertStringContainsString('Tenue LA', implode(' ', $r['avertissements']));
        $this->assertSame(0, ESBTPFraisConfiguration::where('filiere_id', $filiere->id)->count(), 'rien avant Valider');
        $this->valider($r);

        $this->assertSame(450000.0, (float) ESBTPFraisConfiguration::where('frais_category_id', $scolarite->id)->where('filiere_id', $filiere->id)->where('niveau_id', $niveau->id)->value('amount'));
        $this->assertSame(25000.0, (float) ESBTPFraisConfiguration::where('frais_category_id', ESBTPFraisCategory::where('code', 'LATEN')->value('id'))->value('amount'));
    }

    public function test_un_bareme_sans_montant_ou_sur_une_filiere_inconnue_demande(): void
    {
        ESBTPNiveauEtude::factory()->create(['code' => 'LAN2', 'year' => 2, 'type' => 'BTS']);
        ESBTPFraisCategory::factory()->create(['code' => 'LASC2']);

        $this->refuse(app(PoserBareme::class)->executeAuthorized(['configurations' => [['categorie' => 'LASC2', 'filiere' => 'ZZZ', 'niveau' => 'LAN2']]], $this->admin), 'Quel montant');
        $this->refuse(app(PoserBareme::class)->executeAuthorized(['configurations' => [['categorie' => 'LASC2', 'filiere' => 'ZZZ', 'niveau' => 'LAN2', 'montant' => 1]]], $this->admin), 'introuvable. Existants');
    }

    public function test_un_montant_change_entre_proposition_et_validation_perime_la_proposition(): void
    {
        $filiere = ESBTPFiliere::factory()->create(['code' => 'LAPER']);
        ESBTPNiveauEtude::factory()->create(['code' => 'LAN3', 'year' => 1, 'type' => 'BTS']);
        $cat = ESBTPFraisCategory::factory()->create(['code' => 'LASC3']);
        $ligne = ['categorie' => 'LASC3', 'filiere' => 'LAPER', 'niveau' => 'LAN3'];
        $this->valider(app(PoserBareme::class)->executeAuthorized(['configurations' => [$ligne + ['montant' => 100000]]], $this->admin));
        $r = app(PoserBareme::class)->executeAuthorized(['configurations' => [$ligne + ['montant' => 300000]]], $this->admin);

        // Quelqu'un change le montant à l'écran entre-temps : la carte montrait « 100 000 → 300 000 ».
        ESBTPFraisConfiguration::where('filiere_id', $filiere->id)->update(['amount' => 200000]);
        $this->valider($r, null, 'perimee');
        $this->assertSame(200000.0, (float) ESBTPFraisConfiguration::where('frais_category_id', $cat->id)->where('filiere_id', $filiere->id)->value('amount'));
    }

    /** La CLI écrit par le même service que Nanan. */
    public function test_la_cli_pose_le_bareme_par_le_meme_service(): void
    {
        $filiere = ESBTPFiliere::factory()->create(['code' => 'LACLI']);
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'LAN4', 'year' => 1, 'type' => 'BTS']);
        Sanctum::actingAs($this->utilisateur(), ['cli:read', 'cli:admin']);
        $corps = ['categories' => [['code' => 'LACLIS', 'name' => 'Scolarité CLI']],
            'configurations' => [['category_code' => 'LACLIS', 'systeme' => 'BTS', 'filiere_id' => $filiere->id, 'niveau_id' => $niveau->id, 'amount' => 123000]]];

        $this->postJson('/api/cli/frais/poser-bareme', $corps)->assertOk()->assertJsonPath('data.applique', false);
        $this->postJson('/api/cli/frais/poser-bareme', $corps + ['apply' => true])->assertOk()->assertJsonPath('data.configurations_creees', 1);
        $this->assertSame(123000.0, (float) ESBTPFraisConfiguration::where('filiere_id', $filiere->id)->value('amount'));
        $this->postJson('/api/cli/frais/poser-bareme', ['categories' => $corps['categories'], 'configurations' => [['category_code' => 'AUTRE'] + $corps['configurations'][0]]])->assertStatus(422);
    }

    // --- Droits --------------------------------------------------------------------

    public function test_sans_les_droits_aucune_de_ces_actions_n_est_offerte(): void
    {
        $lecteur = $this->utilisateur();
        $noms = array_column(app(CatalogueOutils::class)->schemas($lecteur), 'nom');
        foreach (self::OUTILS as $outil) {
            $this->assertNotContains($outil, $noms);
        }

        $presque = $this->utilisateur();
        $presque->givePermissionTo('trash.view');
        $this->assertFalse(app(RestaurerVersement::class)->isAvailableFor($presque), 'restaurer exige aussi paiements.restore');
        $this->assertArrayHasKey('error', app(ValiderInscriptions::class)->executeAuthorized(['classe' => 'X'], $lecteur));

        $noms = array_column(app(CatalogueOutils::class)->schemas($this->admin), 'nom');
        foreach (self::OUTILS as $outil) {
            $this->assertContains($outil, $noms);
        }
    }

    // --- Séance d'entraînement -----------------------------------------------------

    /**
     * La vraie boucle, le vrai catalogue, le vrai prompt. Le modèle scripté suit
     * le mode opératoire : il demande d'abord le sort de l'argent quand l'outil
     * le réclame, puis propose ; rien n'est écrit avant « Valider ».
     */
    public function test_seance_d_entrainement_annuler_un_versement_puis_valider_une_inscription(): void
    {
        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 4, 'assistant.limites.budget_tokens' => 0]);
        $i = $this->inscription($this->classe('LA_SE_1A'), ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $doublon = $this->versement($i);
        $this->versement($i);
        $faux->scripts['m'] = [
            FauxFournisseur::outil('t1', 'proposer_annulation_versement', ['numero_recu' => $doublon->numero_recu]),
            FauxFournisseur::outil('t2', 'proposer_annulation_versement', ['numero_recu' => $doublon->numero_recu, 'avoir_kind' => 'refund', 'motif' => 'Versement saisi deux fois au guichet']),
            FauxFournisseur::outil('t3', 'proposer_validation_inscriptions', ['matricules' => [$i->etudiant->matricule]]),
            FauxFournisseur::texte('Je propose d’annuler le doublon puis de valider l’inscription : relisez puis validez.'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        foreach (self::OUTILS as $outil) {
            $this->assertStringContainsString($outil, $systeme);
        }

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => 'Le reçu '.$doublon->numero_recu.' est un doublon, annule-le et valide l’inscription']], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        // Le premier appel, incomplet, n'est pas présenté : l'outil a renvoyé la question au modèle.
        $this->assertStringContainsString('remboursé', json_encode($faux->recues[1]['requete']->messages, JSON_UNESCAPED_UNICODE));
        $this->assertSame(['proposer_annulation_versement', 'proposer_validation_inscriptions'], array_column($r->appels, 'tool'));
        $this->assertSame(0, ESBTPPaiement::where('parent_paiement_id', $doublon->id)->count());
        $this->assertSame('en_attente', $i->fresh()->status);
    }
}
