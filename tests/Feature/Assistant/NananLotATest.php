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
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class, \App\Http\Middleware\ForcePasswordChange::class]);
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

    /** Une inscription annulée garde son versement validé (cas courant après un avoir) : jamais revalidée. */
    public function test_une_inscription_annulee_avec_versement_valide_est_laissee(): void
    {
        $classe = $this->classe('LA_VAL_ANN');
        $annulee = $this->inscription($classe, ['status' => 'annulée', 'workflow_step' => 'en_validation']);
        $this->versement($annulee);
        $payee = $this->inscription($classe, ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($payee);

        $r = app(ValiderInscriptions::class)->executeAuthorized(['classe' => 'LA_VAL_ANN'], $this->admin);
        $this->assertSame([[(int) $payee->id]], [$this->idsProposes($r)]);
        $this->valider($r);
        $this->assertSame('annulée', $annulee->fresh()->status);
        $this->assertSame('active', $payee->fresh()->status);

        $this->refuse(app(ValiderInscriptions::class)->executeAuthorized(['inscriptions' => [$annulee->id]], $this->admin), 'inscription annulée');
        Sanctum::actingAs($this->utilisateur(), ['cli:read', 'cli:write']);
        $this->postJson("/api/cli/inscriptions/{$annulee->id}/validate")->assertStatus(422)->assertJsonPath('message', 'Cannot validate: inscription is cancelled');
    }

    /** Comme l'écran : pas de seconde inscription active la même année. */
    public function test_un_eleve_deja_inscrit_ailleurs_cette_annee_est_laisse(): void
    {
        $i = $this->inscription($this->classe('LA_VAL_DBL'), ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($i);
        ESBTPInscription::factory()->create(['etudiant_id' => $i->etudiant_id, 'annee_universitaire_id' => $this->annee,
            'classe_id' => $this->classe('LA_VAL_DBL2')->id, 'status' => 'active']);

        $this->refuse(app(ValiderInscriptions::class)->executeAuthorized(['inscriptions' => [$i->id]], $this->admin), 'autre inscription active');
    }

    /** Comme l'écran, qui valide une à une : au-delà des places, la suite reste en attente. */
    public function test_une_classe_a_une_place_ne_recoit_qu_une_inscription(): void
    {
        $classe = $this->classe('LA_VAL_PL');
        $classe->update(['places_totales' => 2]);
        ESBTPInscription::factory()->create(['classe_id' => $classe->id, 'annee_universitaire_id' => $this->annee, 'status' => 'active', 'workflow_step' => 'etudiant_cree']);
        $premiere = $this->inscription($classe, ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($premiere);
        $seconde = $this->inscription($classe, ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($seconde);
        // L'écran laisse les rôles historiques déroger ; le compte « standard » de la caisse, non.
        $agent = $this->utilisateur();
        $agent->givePermissionTo('inscriptions.validate');
        $this->actingAs($agent);

        $r = app(ValiderInscriptions::class)->executeAuthorized(['classe' => 'LA_VAL_PL'], $agent);
        $this->assertSame([(int) $premiere->id], $this->idsProposes($r));
        $this->assertStringContainsString('classe pleine', implode(' ', $r['avertissements']));
        $this->valider($r, $agent);
        $this->assertSame(['active', 'en_attente'], [$premiere->fresh()->status, $seconde->fresh()->status]);
    }

    /** Même chemin que la validation groupée de l'écran : les rappels de l'inscription s'arrêtent. */
    public function test_valider_arrete_les_rappels_comme_l_ecran(): void
    {
        $i = $this->inscription($this->classe('LA_VAL_RAP'), ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($i);
        $rappel = \App\Models\NotificationReminder::create(['remindable_type' => ESBTPInscription::class, 'remindable_id' => $i->id,
            'reminder_count' => 0, 'next_reminder_at' => now()->addDay(), 'is_active' => true]);

        $this->valider(app(ValiderInscriptions::class)->executeAuthorized(['inscriptions' => [$i->id]], $this->admin));
        $this->assertFalse((bool) $rappel->fresh()->is_active);
        $this->assertSame('etudiant_cree', $i->fresh()->workflow_step);
    }

    private function idsProposes(array $resultat): array
    {
        $journal = \App\Models\ChatbotActionLog::findOrFail($resultat['proposition'] ?? 0);

        return app(ValiderInscriptions::class)->preparer((array) $journal->action_data['arguments'], $journal->user)->donnees['ids'] ?? [];
    }

    // --- L'écran ne revalide jamais une inscription annulée ----------------------

    private function annuleeAvecVersement(string $code, array $attributs = []): ESBTPInscription
    {
        $i = $this->inscription($this->classe($code), $attributs + ['status' => 'annulée', 'workflow_step' => 'en_validation']);
        $versement = $this->versement($i);
        $i->update(['paiement_validation_id' => $versement->id]);

        return $i;
    }

    public function test_la_validation_groupee_de_l_ecran_ne_revalide_pas_une_annulee(): void
    {
        // Réinscription en_validation : le workflow ne la jugeait que sur son étape.
        $reinscription = $this->annuleeAvecVersement('LA_EC_G1', ['type_inscription' => 'réinscription']);
        $premiere = $this->annuleeAvecVersement('LA_EC_G2');

        $reponse = $this->actingAs($this->admin)->postJson(route('esbtp.inscriptions.bulk-valider'),
            ['inscription_ids' => [$reinscription->id, $premiere->id]], ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $this->assertSame(['annulée', 'annulée'], [$reinscription->fresh()->status, $premiere->fresh()->status]);
        $this->assertSame(['Inscription annulée', 'Inscription annulée'], array_column($reponse->json('stats.ignorees'), 'raison'));
    }

    public function test_la_validation_unitaire_de_l_ecran_ne_revalide_pas_une_annulee(): void
    {
        $i = $this->annuleeAvecVersement('LA_EC_U1');

        $this->actingAs($this->admin)->putJson(route('esbtp.inscriptions.valider', $i->id), [], ['X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame('annulée', $i->fresh()->status);

        $r = $this->annuleeAvecVersement('LA_EC_U2', ['type_inscription' => 'réinscription']);
        $this->actingAs($this->admin)->postJson(route('esbtp.inscriptions.valider-definitivement', $r->id), [], ['X-Requested-With' => 'XMLHttpRequest']);
        $this->assertSame('annulée', $r->fresh()->status);
    }

    // --- Dépasser la capacité : une permission, jamais un nom de rôle -------------

    private function classePleine(string $code): ESBTPClasse
    {
        Permission::findOrCreate('inscriptions.override_capacity', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $classe = $this->classe($code);
        $classe->update(['places_totales' => 1]);
        ESBTPInscription::factory()->create(['classe_id' => $classe->id, 'annee_universitaire_id' => $this->annee, 'status' => 'active', 'workflow_step' => 'etudiant_cree']);

        return $classe;
    }

    /** Un rôle Spatie « secretaire » ne donne rien : seule la permission compte. */
    public function test_une_secretaire_spatie_sans_la_permission_ne_depasse_pas(): void
    {
        $classe = $this->classePleine('LA_CAP_1');
        Role::findOrCreate('secretaire', 'web');
        $secretaire = $this->utilisateur();
        $secretaire->assignRole('secretaire');
        $this->assertSame('etudiant', $secretaire->fresh()->role, 'colonne héritée laissée à sa valeur par défaut');
        $this->actingAs($secretaire);

        $this->assertFalse(app(\App\Services\InscriptionWorkflowService::class)->checkClassAvailability($classe->id)['available']);
        $this->assertNotContains('inscriptions.override_capacity', config('permissions.role_defaults.secretaire'));
        $this->assertArrayHasKey('inscriptions.override_capacity', config('permissions.permissions'));

        $this->actingAs($this->admin);
        $this->assertTrue(app(\App\Services\InscriptionWorkflowService::class)->checkClassAvailability($classe->id)['available'], 'superAdmin passe');
    }

    /** Les comptes dont la colonne héritée autorisait le dépassement le gardent, par la migration. */
    public function test_la_migration_donne_la_permission_aux_comptes_qui_l_avaient(): void
    {
        $classe = $this->classePleine('LA_CAP_2');
        $legacy = $this->utilisateur();
        $legacy->forceFill(['role' => 'secretaire'])->save();
        $etudiant = $this->utilisateur();
        $this->actingAs($legacy);
        $workflow = app(\App\Services\InscriptionWorkflowService::class);
        $this->assertFalse($workflow->checkClassAvailability($classe->id)['available'], 'la colonne seule ne suffit plus');

        $migration = require base_path('database/migrations/2026_10_01_223307_grant_override_capacity_to_legacy_role_accounts.php');
        $migration->up();
        $migration->up();

        $this->assertTrue($legacy->fresh()->hasDirectPermission('inscriptions.override_capacity'));
        $this->assertFalse($etudiant->fresh()->hasDirectPermission('inscriptions.override_capacity'));
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('heritage_droit_depassement_capacite')->where('user_id', $legacy->id)->count());
        $this->actingAs($legacy->fresh());
        $this->assertTrue($workflow->checkClassAvailability($classe->id)['available']);
    }

    /** Une classe est universelle : l'alternative proposée ne dépend pas de sa colonne héritée d'année. */
    public function test_une_classe_pleine_propose_une_soeur_d_une_autre_annee_heritee(): void
    {
        $pleine = $this->classePleine('LA_ALT_1');
        $autreAnnee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        $soeur = ESBTPClasse::factory()->create(['code' => 'LA_ALT_2', 'filiere_id' => $pleine->filiere_id, 'niveau_etude_id' => $pleine->niveau_etude_id,
            'annee_universitaire_id' => $autreAnnee->id, 'places_totales' => 30, 'is_active' => true]);
        $soeurPleine = ESBTPClasse::factory()->create(['code' => 'LA_ALT_3', 'filiere_id' => $pleine->filiere_id, 'niveau_etude_id' => $pleine->niveau_etude_id,
            'annee_universitaire_id' => $autreAnnee->id, 'places_totales' => 1, 'is_active' => true]);
        ESBTPInscription::factory()->create(['classe_id' => $soeurPleine->id, 'annee_universitaire_id' => $this->annee, 'status' => 'active', 'workflow_step' => 'etudiant_cree']);
        $this->actingAs($this->utilisateur());

        $dispo = app(\App\Services\InscriptionWorkflowService::class)->checkClassAvailability($pleine->id);
        $this->assertFalse($dispo['available']);
        $this->assertSame([(int) $soeur->id], $dispo['alternatives']->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    /** Avec la dérogation, Nanan propose quand même, et annonce le dépassement. */
    public function test_la_derogation_est_annoncee_dans_la_proposition(): void
    {
        $this->classePleine('LA_CAP_3');
        $i = $this->inscription(ESBTPClasse::where('code', 'LA_CAP_3')->sole(), ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($i);
        $agent = $this->utilisateur();
        $agent->givePermissionTo(['inscriptions.validate', 'inscriptions.override_capacity']);
        $this->actingAs($agent);

        $r = app(ValiderInscriptions::class)->executeAuthorized(['inscriptions' => [$i->id]], $agent);
        $this->assertContains('LA CAP 3 passera à 2 inscrits pour 1 places (dérogation).', $r['avertissements']);
    }

    /** Les places se comptent sur l'année de l'inscription, et la lecture ne journalise rien. */
    public function test_les_places_se_comptent_sur_l_annee_de_l_inscription_sans_journal(): void
    {
        $classe = $this->classePleine('LA_CAP_4');
        $ancienne = ESBTPAnneeUniversitaire::factory()->create(['is_current' => false]);
        $i = $this->inscription($classe, ['annee_universitaire_id' => $ancienne->id, 'status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($i);
        $agent = $this->utilisateur();
        $agent->givePermissionTo('inscriptions.validate');
        $this->actingAs($agent);

        $this->assertSame(1, app(\App\Domain\Inscriptions\ObstacleALaValidation::class)->placesRestantes($i), 'pleine cette année, libre l’an passé');

        $courante = $this->inscription($classe, ['status' => 'en_attente', 'workflow_step' => 'en_validation']);
        $this->versement($courante);
        $journal = [];
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Log\Events\MessageLogged::class, function ($e) use (&$journal) {
            $journal[] = $e->message;
        });
        $r = app(ValiderInscriptions::class)->executeAuthorized(['inscriptions' => [$courante->id]], $agent);
        $this->assertStringContainsString('classe pleine', implode(' ', $r['manques'] ?? []));
        $this->assertNotContains('Classe en surcapacité détectée', $journal, 'une proposition ne journalise pas de dépassement');
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

    /** Un avoir rapproché qui annule le versement à répartir bouge avec lui : refusé. */
    public function test_repartir_refuse_si_un_avoir_du_versement_est_rapproche(): void
    {
        [$i, $v] = $this->tropVerse();
        $v->update(['date_paiement' => now()->toDateString()]);
        $avoir = app(\App\Services\AvoirService::class)->issue($v, 10000, 'credit', 'Avoir partiel pour le test', $this->admin->id);
        $avoir->forceFill(['reconciliation_locked_at' => now()])->save();
        $comptable = $this->utilisateur();
        $comptable->givePermissionTo('paiements.reventiler');
        $this->actingAs($comptable);

        $this->refuse(app(RepartirTropPercu::class)->executeAuthorized(['inscription_id' => $i->id, 'motif' => 'Versement unique pour deux frais'], $comptable), $avoir->numero_recu);
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

    /** Le cache des montants ne se vide qu'une fois le barème validé en base. */
    public function test_le_cache_des_montants_se_vide_apres_le_commit(): void
    {
        $filiere = ESBTPFiliere::factory()->create(['code' => 'LACAC']);
        $niveau = ESBTPNiveauEtude::factory()->create(['code' => 'LAN5', 'year' => 1, 'type' => 'BTS']);
        $cle = 'frais_cache__class_configs_global_BTS_'.$filiere->id.'_null_'.$niveau->id.'_null';
        \Illuminate\Support\Facades\Cache::put($cle, 'ancien', 600);
        $bareme = ['categories' => [['code' => 'LACACS', 'name' => 'Scolarité cache']], 'confirmer_statut' => false,
            'configurations' => [['category_code' => 'LACACS', 'systeme' => 'BTS', 'filiere_id' => $filiere->id, 'parcours_id' => null, 'niveau_id' => $niveau->id, 'amount' => 1000]]];

        \Illuminate\Support\Facades\DB::beginTransaction();
        app(\App\Services\Frais\PoseDeBareme::class)->appliquer($bareme, $this->admin->id);
        $this->assertSame('ancien', \Illuminate\Support\Facades\Cache::get($cle), 'pas avant la fin de la transaction');
        \Illuminate\Support\Facades\DB::commit();
        // Sous DatabaseTransactions (Laravel 9), la transaction du test ne se valide
        // jamais : on joue ce que la vraie validation jouerait.
        app('db.transactions')->getTransactions()->each->executeCallbacks();
        $this->assertNull(\Illuminate\Support\Facades\Cache::get($cle));
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
