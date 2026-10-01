<?php

namespace Tests\Feature\Assistant;

use App\Domain\Academique\AnneesUniversitaires;
use App\Domain\Assistant\Actions\Academique\CorrigerAnneeNiveau;
use App\Domain\Assistant\Actions\Academique\CreerAnneeUniversitaire;
use App\Domain\Assistant\Actions\Academique\DefinirAnneeCourante;
use App\Domain\Assistant\Actions\Academique\EnregistrerFilieres;
use App\Domain\Assistant\Actions\Academique\EnregistrerNiveaux;
use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Matieres\RetirerDeMaquetteBts;
use App\Domain\Assistant\Actions\RegistreDesActions;
use App\Domain\Assistant\Actions\TroncCommun\AjouterSortiesTroncCommun;
use App\Domain\Assistant\Actions\TroncCommun\MarquerFiliereTroncCommun;
use App\Domain\Assistant\Actions\TroncCommun\OrienterInscription;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Assistant\Outils\LireStructureAcademique;
use App\Domain\BtsTroncCommun\LiaisonsDeMatiere;
use App\Helpers\SettingsHelper;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Lot B : la structure académique que la CLI savait écrire (années, filières,
 * niveaux, tronc commun BTS, maquette), apprise à Nanan. Chaque test vérifie
 * que rien n'est écrit avant « Valider », tout l'est après, et qu'une valeur
 * absente est demandée au lieu d'être devinée.
 */
class NananLotBTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSIONS = [
        'annees.view', 'annees.create', 'annees.set_current', 'filieres.view', 'filieres.create', 'filieres.edit',
        'niveaux.view', 'niveaux.create', 'niveaux.edit', 'bts_tronc_commun.manage_targets',
        'inscriptions.specialisation.manage', 'matieres.edit', 'classes.view',
    ];

    private User $admin;

    private ESBTPAnneeUniversitaire $courante;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);
        Role::findOrCreate('superAdmin', 'web');
        foreach (self::PERMISSIONS as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = $this->utilisateur();
        $this->admin->assignRole('superAdmin');

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->courante = ESBTPAnneeUniversitaire::factory()->create(['name' => 'LB-2025-2026', 'is_current' => true]);
        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
    }

    private function utilisateur(): User
    {
        return User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
    }

    private function valider(array $resultat, string $statut = 'executee'): void
    {
        $this->assertSame('approbation', $resultat['widget']['kind'] ?? null, json_encode($resultat, JSON_UNESCAPED_UNICODE));
        $this->actingAs($this->admin)
            ->postJson($resultat['widget']['valider_url'], ['jeton' => $resultat['widget']['jeton']])
            ->assertJson(['statut' => $statut]);
    }

    private function manques(array $resultat): string
    {
        $this->assertArrayNotHasKey('widget', $resultat, 'une proposition incomplète ne doit pas être présentée');

        return implode(' ', $resultat['manques'] ?? []);
    }

    // --- Années universitaires -----------------------------------------------------

    public function test_creer_une_annee_demande_si_elle_devient_courante_puis_n_ecrit_qu_apres_valider(): void
    {
        $args = ['nom' => 'LB-2026-2027', 'date_debut' => '2026-09-15', 'date_fin' => '2027-07-31'];
        $this->assertStringContainsString("l'année en cours", $this->manques(app(CreerAnneeUniversitaire::class)->executeAuthorized($args, $this->admin)));
        $this->assertStringContainsString('fin doit suivre', $this->manques(app(CreerAnneeUniversitaire::class)->executeAuthorized(['date_fin' => '2026-01-01', 'courante' => false] + $args, $this->admin)));
        $this->assertStringContainsString('existe déjà', $this->manques(app(CreerAnneeUniversitaire::class)->executeAuthorized(['nom' => 'LB-2025-2026', 'courante' => false] + $args, $this->admin)));

        $r = app(CreerAnneeUniversitaire::class)->executeAuthorized($args + ['courante' => false], $this->admin);
        $this->assertSame(0, ESBTPAnneeUniversitaire::where('name', 'LB-2026-2027')->count(), 'rien avant Valider');
        $this->valider($r);

        $annee = ESBTPAnneeUniversitaire::where('name', 'LB-2026-2027')->sole();
        $this->assertSame('2026-09-15', $annee->start_date->toDateString());
        $this->assertFalse((bool) $annee->is_current);
        $this->assertTrue((bool) $this->courante->fresh()->is_current, 'l\'année en cours ne bouge pas sans le demander');
    }

    public function test_creer_courante_exige_le_droit_de_changer_l_annee_en_cours(): void
    {
        $secretaire = $this->utilisateur();
        $secretaire->givePermissionTo('annees.create');

        $r = app(CreerAnneeUniversitaire::class)->executeAuthorized(
            ['nom' => 'LB-2027', 'date_debut' => '2027-09-01', 'date_fin' => '2028-07-31', 'courante' => true], $secretaire);
        $this->assertStringContainsString('pas la désigner', $this->manques($r));
    }

    public function test_changer_l_annee_en_cours_previent_et_bascule_par_le_chemin_de_l_ecran(): void
    {
        $suivante = ESBTPAnneeUniversitaire::factory()->create(['name' => 'LB-2026-2027', 'is_current' => false]);
        ESBTPAnneeUniversitaire::getCurrent(); // met l'ancienne en cache

        $r = app(DefinirAnneeCourante::class)->executeAuthorized(['annee' => 'LB-2026-2027'], $this->admin);
        $this->assertSame('eleve', $r['widget']['risque']);
        $this->assertStringContainsString('Tous les utilisateurs', implode(' ', $r['widget']['avertissements']));
        $this->assertTrue((bool) $this->courante->fresh()->is_current, 'rien avant Valider');
        $this->valider($r);

        $this->assertTrue((bool) $suivante->fresh()->is_current);
        $this->assertFalse((bool) $this->courante->fresh()->is_current);
        $this->assertSame($suivante->id, ESBTPAnneeUniversitaire::getCurrent()?->id, 'le cache de l\'année en cours est vidé');
    }

    public function test_changer_l_annee_en_cours_demande_une_annee_existante_et_differente(): void
    {
        $this->assertStringContainsString('Quelle année', $this->manques(app(DefinirAnneeCourante::class)->executeAuthorized([], $this->admin)));
        $this->assertStringContainsString('Aucune année', $this->manques(app(DefinirAnneeCourante::class)->executeAuthorized(['annee' => 'la prochaine'], $this->admin)));
        $this->assertStringContainsString('déjà', $this->manques(app(DefinirAnneeCourante::class)->executeAuthorized(['annee' => (string) $this->courante->id], $this->admin)));
    }

    public function test_une_annee_en_cours_changee_entre_temps_rend_la_proposition_perimee(): void
    {
        $suivante = ESBTPAnneeUniversitaire::factory()->create(['name' => 'LB-A', 'is_current' => false]);
        $autre = ESBTPAnneeUniversitaire::factory()->create(['name' => 'LB-B', 'is_current' => false]);
        $r = app(DefinirAnneeCourante::class)->executeAuthorized(['annee' => 'LB-A'], $this->admin);

        $autre->setAsCurrent();
        $this->valider($r, 'perimee');

        $this->assertFalse((bool) $suivante->fresh()->is_current);
        $this->assertTrue((bool) $autre->fresh()->is_current);
    }

    public function test_la_cli_change_l_annee_en_cours_et_vide_le_cache(): void
    {
        $suivante = ESBTPAnneeUniversitaire::factory()->create(['name' => 'LB-CLI', 'is_current' => false]);
        ESBTPAnneeUniversitaire::getCurrent();
        Sanctum::actingAs($this->admin, ['cli:admin']);

        $this->postJson("/api/cli/annee/set/{$suivante->id}")->assertOk();

        $this->assertSame($suivante->id, ESBTPAnneeUniversitaire::getCurrent()?->id);
    }

    // --- Filières et niveaux -------------------------------------------------------

    public function test_filieres_creees_et_corrigees_sans_vider_la_description(): void
    {
        $gc = ESBTPFiliere::factory()->create(['code' => 'LBGC', 'name' => 'Genie civil', 'description' => 'Ancienne', 'is_active' => false]);

        $r = app(EnregistrerFilieres::class)->executeAuthorized(['filieres' => [
            ['code' => 'lbgc', 'nom' => 'Génie civil'],
            ['code' => 'LBTP', 'nom' => 'Travaux publics'],
        ]], $this->admin);
        $this->assertSame(0, ESBTPFiliere::where('code', 'LBTP')->count(), 'rien avant Valider');
        $this->valider($r);

        $gc->refresh();
        $this->assertSame('Génie civil', $gc->name);
        $this->assertSame('Ancienne', $gc->description, 'une description non donnée n\'est pas effacée');
        $this->assertFalse((bool) $gc->is_active, 'une activation non donnée ne réactive pas');
        $this->assertTrue((bool) ESBTPFiliere::where('code', 'LBTP')->sole()->is_active);
    }

    public function test_corriger_une_filiere_existante_exige_le_droit_de_modifier(): void
    {
        ESBTPFiliere::factory()->create(['code' => 'LBEX']);
        $u = $this->utilisateur();
        $u->givePermissionTo('filieres.create');

        $this->assertStringContainsString('LBEX', $this->manques(app(EnregistrerFilieres::class)->executeAuthorized(['filieres' => [['code' => 'LBEX', 'nom' => 'X']]], $u)));
        $this->assertStringContainsString('code ET le nom', $this->manques(app(EnregistrerFilieres::class)->executeAuthorized(['filieres' => [['code' => 'LBZ']]], $this->admin)));
        $this->assertStringContainsString('double', $this->manques(app(EnregistrerFilieres::class)->executeAuthorized(['filieres' => [['code' => 'LBZ', 'nom' => 'a'], ['code' => 'lbz', 'nom' => 'b']]], $this->admin)));
    }

    public function test_un_code_tenu_par_une_filiere_supprimee_est_refuse_par_nanan_et_la_cli(): void
    {
        ESBTPFiliere::factory()->create(['code' => 'LBDEL'])->delete();

        $this->assertStringContainsString('supprimée', $this->manques(app(EnregistrerFilieres::class)->executeAuthorized(['filieres' => [['code' => 'LBDEL', 'nom' => 'X']]], $this->admin)));

        Sanctum::actingAs($this->admin, ['cli:admin']);
        $this->postJson('/api/cli/filieres', ['filieres' => [['code' => 'LBDEL', 'name' => 'X']]])->assertStatus(422);
    }

    public function test_niveaux_crees_et_annee_lmd_hors_cycle_refusee(): void
    {
        $this->assertStringContainsString('Master', $this->manques(app(EnregistrerNiveaux::class)->executeAuthorized(['niveaux' => [['nom' => 'Master 1', 'type' => 'Master', 'annee' => 1]]], $this->admin)));
        $this->assertStringContainsString('nom, le type', $this->manques(app(EnregistrerNiveaux::class)->executeAuthorized(['niveaux' => [['nom' => 'X']]], $this->admin)));

        $r = app(EnregistrerNiveaux::class)->executeAuthorized(['niveaux' => [['nom' => 'Master 1', 'type' => 'Master', 'annee' => 4, 'code' => 'LBM1']]], $this->admin);
        $this->assertSame(0, ESBTPNiveauEtude::where('code', 'LBM1')->count(), 'rien avant Valider');
        $this->valider($r);
        $this->assertSame(4, (int) ESBTPNiveauEtude::where('code', 'LBM1')->sole()->year);
    }

    public function test_corriger_l_annee_d_un_niveau_lmd(): void
    {
        $master = ESBTPNiveauEtude::factory()->create(['name' => 'Master 1', 'type' => 'Master', 'year' => 1, 'code' => 'LBMA']);

        $this->assertStringContainsString('parmi', $this->manques(app(CorrigerAnneeNiveau::class)->executeAuthorized(['niveau' => 'LBMA', 'annee' => 2], $this->admin)));

        $r = app(CorrigerAnneeNiveau::class)->executeAuthorized(['niveau' => 'lbma', 'annee' => 4], $this->admin);
        $this->assertSame(1, (int) $master->fresh()->year, 'rien avant Valider');
        $this->valider($r);
        $this->assertSame(4, (int) $master->fresh()->year);
    }

    // --- Tronc commun BTS ----------------------------------------------------------

    /** @return array{0: ESBTPFiliere, 1: ESBTPNiveauEtude, 2: ESBTPClasse, 3: ESBTPFiliere, 4: ESBTPClasse} */
    private function troncCommun(): array
    {
        $tc = ESBTPFiliere::factory()->create(['code' => 'LBTC', 'is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);
        $n = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 1, 'code' => 'LBN1']);
        $classeTc = ESBTPClasse::factory()->create(['code' => 'LB_TC_1A', 'filiere_id' => $tc->id, 'niveau_etude_id' => $n->id]);
        $spe = ESBTPFiliere::factory()->create(['code' => 'LBSPE', 'parent_id' => $tc->id]);
        $classeSpe = ESBTPClasse::factory()->create(['code' => 'LB_SPE_1A', 'filiere_id' => $spe->id, 'niveau_etude_id' => $n->id]);

        return [$tc, $n, $classeTc, $spe, $classeSpe];
    }

    public function test_marquer_une_filiere_tronc_commun(): void
    {
        $f = ESBTPFiliere::factory()->create(['code' => 'LBMK']);
        $option = ESBTPFiliere::factory()->create(['code' => 'LBOPT', 'parent_id' => $f->id]);

        $this->assertStringContainsString('marquer', $this->manques(app(MarquerFiliereTroncCommun::class)->executeAuthorized(['filiere' => 'LBMK'], $this->admin)));
        $this->assertStringContainsString('semestres', $this->manques(app(MarquerFiliereTroncCommun::class)->executeAuthorized(['filiere' => 'LBMK', 'tronc_commun' => true], $this->admin)));
        $this->assertStringContainsString('option', $this->manques(app(MarquerFiliereTroncCommun::class)->executeAuthorized(['filiere' => 'LBOPT', 'tronc_commun' => true, 'semestres' => 2], $this->admin)));

        $r = app(MarquerFiliereTroncCommun::class)->executeAuthorized(['filiere' => 'LBMK', 'tronc_commun' => true, 'semestres' => 2], $this->admin);
        $this->assertFalse((bool) $f->fresh()->is_tronc_commun, 'rien avant Valider');
        $this->valider($r);
        $this->assertTrue($f->fresh()->isTroncCommun());
        $this->assertSame(2, (int) $f->fresh()->semestres_tronc_commun);

        Sanctum::actingAs($this->admin, ['cli:admin']);
        $this->postJson("/api/cli/bts-tc/filieres/{$option->id}/mark-tronc-commun", ['is_tronc_commun' => true])->assertStatus(422);
    }

    public function test_ouvrir_des_sorties_depuis_une_classe_de_tronc_commun(): void
    {
        [, $n, $classeTc, $spe, $classeSpe] = $this->troncCommun();
        $autreNiveau = ESBTPClasse::factory()->create(['code' => 'LB_SPE_2A', 'filiere_id' => $spe->id]);

        $this->assertStringContainsString('tronc commun', $this->manques(app(AjouterSortiesTroncCommun::class)->executeAuthorized(['classe' => 'LB_SPE_1A', 'cibles' => ['LB_TC_1A']], $this->admin)));
        $this->assertStringContainsString('même niveau', $this->manques(app(AjouterSortiesTroncCommun::class)->executeAuthorized(['classe' => 'LB_TC_1A', 'cibles' => ['LB_SPE_2A']], $this->admin)));
        $this->assertStringContainsString('introuvable', $this->manques(app(AjouterSortiesTroncCommun::class)->executeAuthorized(['classe' => 'LB_TC_1A', 'cibles' => ['NOPE']], $this->admin)));

        $r = app(AjouterSortiesTroncCommun::class)->executeAuthorized(['classe' => 'lb_tc_1a', 'cibles' => ['LB_SPE_1A'], 'semestre' => 3], $this->admin);
        $this->assertSame(0, ESBTPClasseOrientationTarget::where('source_classe_id', $classeTc->id)->count(), 'rien avant Valider');
        $this->valider($r);
        $sortie = ESBTPClasseOrientationTarget::where('source_classe_id', $classeTc->id)->sole();
        $this->assertSame($classeSpe->id, (int) $sortie->target_classe_id);
        $this->assertSame(3, (int) $sortie->semestre_activation);

        Sanctum::actingAs($this->admin, ['cli:admin']);
        $this->postJson("/api/cli/bts-tc/classes/{$classeSpe->id}/targets", ['target_classe_id' => $classeTc->id])->assertStatus(422);
        $this->assertNotSame($n->id, (int) $autreNiveau->niveau_etude_id);
    }

    public function test_orienter_un_etudiant_ne_cree_rien_avant_valider_meme_par_la_hierarchie(): void
    {
        [$tc, $n, $classeTc, , $classeSpe] = $this->troncCommun();
        SettingsHelper::setOrCreate('tronc_commun_enabled', '1', 'general', 'boolean');
        $inscription = ESBTPInscription::factory()->create([
            'filiere_id' => $tc->id, 'niveau_id' => $n->id, 'classe_id' => $classeTc->id, 'annee_universitaire_id' => $this->courante->id,
        ]);

        // Aucune sortie configurée : seule la hiérarchie de filières l'autorise,
        // et ce repli CRÉE la sortie. La proposition ne doit pas la créer.
        $r = app(OrienterInscription::class)->executeAuthorized(['inscription_id' => $inscription->id, 'classe' => 'LB_SPE_1A'], $this->admin);
        $this->assertSame(0, ESBTPClasseOrientationTarget::where('source_classe_id', $classeTc->id)->count(), 'la préparation n\'écrit rien');
        $this->assertSame($classeTc->id, (int) $inscription->fresh()->classe_id);
        $this->valider($r);

        $this->assertSame($classeSpe->id, (int) $inscription->fresh()->classe_id);
        $this->assertTrue($inscription->fresh()->phases()->where('type_phase', 'specialisation')->where('is_active', true)->exists());

        $deja = app(OrienterInscription::class)->executeAuthorized(['inscription_id' => $inscription->id, 'classe' => 'LB_SPE_1A'], $this->admin);
        $this->assertStringContainsString('déjà une spécialité', $this->manques($deja));
    }

    public function test_orienter_vers_une_classe_qui_n_est_pas_une_sortie_est_refuse(): void
    {
        [$tc, $n, $classeTc] = $this->troncCommun();
        SettingsHelper::setOrCreate('tronc_commun_enabled', '1', 'general', 'boolean');
        $etrangere = ESBTPClasse::factory()->create(['code' => 'LB_ETR_1A', 'niveau_etude_id' => $n->id]);
        $inscription = ESBTPInscription::factory()->create(['filiere_id' => $tc->id, 'niveau_id' => $n->id, 'classe_id' => $classeTc->id]);

        $this->assertStringContainsString('pas une sortie', $this->manques(app(OrienterInscription::class)->executeAuthorized(['inscription_id' => $inscription->id, 'classe' => 'LB_ETR_1A'], $this->admin)));
        $this->assertNotSame($etrangere->id, (int) $inscription->fresh()->classe_id);
    }

    // --- Maquette BTS --------------------------------------------------------------

    public function test_retirer_une_matiere_de_la_maquette_et_confirmer_une_matiere_evaluee(): void
    {
        $f = ESBTPFiliere::factory()->create(['code' => 'LBMQ']);
        $n = ESBTPNiveauEtude::factory()->create(['type' => 'BTS', 'year' => 2, 'code' => 'LBN2']);
        $classe = ESBTPClasse::factory()->create(['filiere_id' => $f->id, 'niveau_etude_id' => $n->id]);
        $libre = ESBTPMatiere::factory()->create(['code' => 'LBLIB']);
        $notee = ESBTPMatiere::factory()->create(['code' => 'LBNOT']);
        $absente = ESBTPMatiere::factory()->create(['code' => 'LBABS']);
        foreach ([$libre, $notee] as $m) {
            app(LiaisonsDeMatiere::class)->poser($m->id, $f->id, $n->id);
        }
        ESBTPEvaluation::factory()->create(['classe_id' => $classe->id, 'matiere_id' => $notee->id, 'annee_universitaire_id' => $this->courante->id]);
        $dans = fn (ESBTPMatiere $m) => ESBTPMatiereFilierNiveau::forCombo($f->id, $n->id)->where('matiere_id', $m->id)->exists();

        $this->assertStringContainsString('LBABS', $this->manques(app(RetirerDeMaquetteBts::class)->executeAuthorized(['filiere' => 'LBMQ', 'niveau' => 'LBN2', 'matieres' => ['LBABS']], $this->admin)));
        $this->assertStringContainsString('confirme', $this->manques(app(RetirerDeMaquetteBts::class)->executeAuthorized(['filiere' => 'LBMQ', 'niveau' => 'LBN2', 'matieres' => ['LBNOT']], $this->admin)));

        $r = app(RetirerDeMaquetteBts::class)->executeAuthorized(['filiere' => 'LBMQ', 'niveau' => 'LBN2', 'matieres' => ['LBLIB', 'LBNOT'], 'confirme_malgre_les_notes' => true], $this->admin);
        $this->assertSame('eleve', $r['widget']['risque']);
        $this->assertTrue($dans($libre) && $dans($notee), 'rien avant Valider');
        $this->valider($r);

        $this->assertFalse($dans($libre));
        $this->assertFalse($dans($notee));
        $this->assertFalse($dans($absente));
    }

    // --- Droits, lecture, séance d'entraînement ------------------------------------

    public function test_sans_droit_aucune_action_du_lot_n_est_proposee(): void
    {
        $sansDroit = $this->utilisateur();
        $cles = ['creation_annee', 'annee_courante', 'filieres', 'niveaux', 'annee_niveau', 'tronc_commun_filiere', 'sortie_tronc_commun', 'orientation_bts', 'retrait_maquette_bts'];
        foreach ($cles as $cle) {
            $action = app(RegistreDesActions::class)->action($cle);
            $this->assertNotNull($action, $cle);
            $this->assertFalse($action->isAvailableFor($sansDroit), $cle);
            $this->assertTrue($action->isAvailableFor($this->admin), $cle);
        }
        $noms = array_column(app(CatalogueOutils::class)->schemas($sansDroit), 'nom');
        $this->assertNotContains('proposer_annee_courante', $noms);
        $this->assertNotContains('lire_structure_academique', $noms);
    }

    public function test_lire_la_structure_dit_l_annee_en_cours(): void
    {
        ESBTPFiliere::factory()->create(['code' => 'LBLU', 'is_tronc_commun' => true]);
        $d = app(LireStructureAcademique::class)->executeAuthorized([], $this->admin)['diagnostic'];

        $this->assertSame($this->courante->id, $d['annee_en_cours']['id']);
        $this->assertContains('LBLU', array_column($d['filieres']['lignes'], 1));
    }

    /**
     * Séance d'entraînement : « passe à 2026-2027 ». La vraie boucle, le vrai
     * catalogue, le vrai prompt ; le modèle scripté lit la structure puis
     * propose. L'année en cours ne bouge pas avant « Valider ».
     */
    public function test_seance_d_entrainement_changer_l_annee_en_cours(): void
    {
        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 4, 'assistant.limites.budget_tokens' => 0]);
        ESBTPAnneeUniversitaire::factory()->create(['name' => 'LB-2026-2027', 'is_current' => false]);
        $faux->scripts['m'] = [
            FauxFournisseur::outil('t1', 'lire_structure_academique', ['parties' => ['annees']]),
            FauxFournisseur::outil('t2', 'proposer_annee_courante', ['annee' => 'LB-2026-2027']),
            FauxFournisseur::texte('Je propose de passer à LB-2026-2027 : tous les écrans basculeront. Relisez puis validez.'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        foreach (['proposer_annee_courante', 'proposer_creation_annee', 'proposer_filieres', 'proposer_niveaux', 'proposer_annee_niveau',
            'proposer_tronc_commun_filiere', 'proposer_sortie_tronc_commun', 'proposer_orientation_bts', 'proposer_retrait_maquette_bts', 'lire_structure_academique'] as $outil) {
            $this->assertStringContainsString($outil, $systeme);
        }

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => "Passe l'établissement à l'année LB-2026-2027"]], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        $this->assertSame(['lire_structure_academique', 'proposer_annee_courante'], array_column($r->appels, 'tool'));
        $this->assertTrue((bool) $this->courante->fresh()->is_current, 'rien avant Valider');
        $this->assertSame(1, DB::table('chatbot_actions_log')->where('action_type', 'annee_courante')->where('status', 'proposed')->count());
    }

    // --- Retours de revue (thermo-review) ------------------------------------------

    /** B1 : ce que dit l'avertissement est vrai — un encaissement suit son inscription. */
    public function test_la_bascule_ne_promet_pas_de_deplacer_les_encaissements(): void
    {
        ESBTPAnneeUniversitaire::factory()->create(['name' => 'LB-B1', 'is_current' => false]);
        $texte = implode(' ', app(DefinirAnneeCourante::class)->executeAuthorized(['annee' => 'LB-B1'], $this->admin)['widget']['avertissements']);

        $this->assertStringNotContainsString('encaissements se rattacheront', $texte);
        $this->assertStringContainsString("l'année de l'inscription", $texte);
        $this->assertStringContainsString('reste modifiable', $texte);
    }

    /**
     * S1 : le cache est vidé APRÈS le commit de la bascule, jamais pendant.
     * On relève le niveau de transaction au moment du vidage : celui d'avant
     * l'appel (la transaction du test) et non celui de la bascule.
     */
    public function test_le_cache_n_est_vide_qu_apres_le_commit(): void
    {
        $suivante = ESBTPAnneeUniversitaire::factory()->create(['name' => 'LB-S1', 'is_current' => false]);
        $avant = DB::transactionLevel();
        $niveaux = [];
        Cache::partialMock()->shouldReceive('flush')->andReturnUsing(function () use (&$niveaux) {
            $niveaux[] = DB::transactionLevel();

            return true;
        });

        app(AnneesUniversitaires::class)->definirCourante($suivante);

        $this->assertSame([$avant], $niveaux, 'le cache doit être vidé une fois, après le commit de la bascule');
        $this->assertTrue((bool) $suivante->fresh()->is_current);
    }

    /** S2 : la fiche filière et l'administration passent par les mêmes règles. */
    public function test_tous_les_ecrans_refusent_une_sortie_d_un_autre_niveau(): void
    {
        [$tc, , $classeTc, $spe] = $this->troncCommun();
        $autreNiveau = ESBTPClasse::factory()->create(['code' => 'LB_SPE_XN', 'filiere_id' => $spe->id]);

        $this->actingAs($this->admin)->postJson("/esbtp/filieres/{$tc->id}/sorties-tc", [
            'source_classe_id' => $classeTc->id, 'target_classe_id' => $autreNiveau->id,
        ])->assertStatus(422);
        $this->actingAs($this->admin)->postJson('/esbtp/admin/orientation-targets', [
            'source_classe_id' => $classeTc->id, 'target_classe_id' => $autreNiveau->id,
        ])->assertStatus(422);

        $this->assertSame(0, ESBTPClasseOrientationTarget::where('source_classe_id', $classeTc->id)->count());
    }

    /** S2 : l'écran de modification d'une filière ne marque pas une option tronc commun. */
    public function test_l_ecran_filiere_refuse_une_option_tronc_commun(): void
    {
        $parent = ESBTPFiliere::factory()->create(['code' => 'LBPAR']);
        $option = ESBTPFiliere::factory()->create(['code' => 'LBOPX', 'parent_id' => $parent->id]);

        $this->actingAs($this->admin)->put("/esbtp/filieres/{$option->id}", [
            'name' => $option->name, 'code' => 'LBOPX', 'is_active' => 1, 'parent_id' => $parent->id, 'is_tronc_commun' => 1,
        ])->assertSessionHas('error');

        $this->assertFalse((bool) $option->fresh()->is_tronc_commun);

        // Et à la création, par le même refus.
        $this->actingAs($this->admin)->post('/esbtp/filieres', [
            'name' => 'Option neuve', 'code' => 'LBOPN', 'is_active' => 1, 'parent_id' => $parent->id, 'is_tronc_commun' => 1,
        ])->assertSessionHas('error');
        $this->assertSame(0, ESBTPFiliere::where('code', 'LBOPN')->count());
    }

    /** S3 : le libellé non donné reste ; le type LMD se reconnaît sans la casse. */
    public function test_niveaux_libelle_conserve_et_type_sans_casse(): void
    {
        $n = ESBTPNiveauEtude::factory()->create(['type' => 'Licence', 'year' => 1, 'name' => 'L1', 'libelle' => 'Licence première année', 'code' => 'LBL1']);
        $this->valider(app(EnregistrerNiveaux::class)->executeAuthorized(['niveaux' => [['nom' => 'Licence 1', 'type' => 'licence', 'annee' => 1]]], $this->admin));

        $this->assertSame('Licence 1', $n->fresh()->name);
        $this->assertSame('Licence première année', $n->fresh()->libelle, 'un libellé non donné n\'est pas écrasé');
        $this->assertStringContainsString('Master', $this->manques(app(EnregistrerNiveaux::class)->executeAuthorized(['niveaux' => [['nom' => 'M1', 'type' => 'master', 'annee' => 1]]], $this->admin)));
    }

    /** S4 : tronc commun désactivé dans l'établissement : pas d'orientation, comme l'écran. */
    public function test_orientation_refusee_si_le_tronc_commun_est_desactive(): void
    {
        [$tc, $n, $classeTc] = $this->troncCommun();
        SettingsHelper::setOrCreate('tronc_commun_enabled', '0', 'general', 'boolean');
        $inscription = ESBTPInscription::factory()->create(['filiere_id' => $tc->id, 'niveau_id' => $n->id, 'classe_id' => $classeTc->id]);

        $this->assertStringContainsString("n'est pas activé", $this->manques(app(OrienterInscription::class)->executeAuthorized(['inscription_id' => $inscription->id, 'classe' => 'LB_SPE_1A'], $this->admin)));
    }

    /** S5 : rouvrir une sortie garde ses notes et son semestre ; le tableau dit le vrai semestre. */
    public function test_rouvrir_une_sortie_garde_notes_et_semestre(): void
    {
        [, $n, $classeTc, $spe, $classeSpe] = $this->troncCommun();
        $ouverte = ESBTPClasse::factory()->create(['code' => 'LB_SPE_1B', 'filiere_id' => $spe->id, 'niveau_etude_id' => $n->id]);
        ESBTPClasseOrientationTarget::create(['source_classe_id' => $classeTc->id, 'target_classe_id' => $classeSpe->id, 'semestre_activation' => 3, 'is_active' => false, 'sort_order' => 0, 'notes' => 'Décision du conseil']);
        ESBTPClasseOrientationTarget::create(['source_classe_id' => $classeTc->id, 'target_classe_id' => $ouverte->id, 'semestre_activation' => 4, 'is_active' => true, 'sort_order' => 1]);

        $r = app(AjouterSortiesTroncCommun::class)->executeAuthorized(['classe' => 'LB_TC_1A', 'cibles' => ['LB_SPE_1A', 'LB_SPE_1B']], $this->admin);
        $lignes = collect($r['widget']['lignes'])->keyBy(1);
        $this->assertSame('S4', $lignes['LB_SPE_1B'][4], 'une sortie déjà ouverte montre son vrai semestre');
        $this->assertSame('S3', $lignes['LB_SPE_1A'][4]);
        $this->valider($r);

        $rouverte = ESBTPClasseOrientationTarget::where('target_classe_id', $classeSpe->id)->sole();
        $this->assertTrue((bool) $rouverte->is_active);
        $this->assertSame('Décision du conseil', $rouverte->notes);
        $this->assertSame(3, (int) $rouverte->semestre_activation);
    }
}
