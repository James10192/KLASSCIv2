<?php

namespace Tests\Feature\Admissions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFacture;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisConfiguration;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPieceDeposee;
use App\Models\ESBTPPieceDossier;
use App\Models\ESBTPRdvCreneau;
use App\Models\ESBTPRdvReservation;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admissions\AdmissionAccountActivator;
use App\Services\Admissions\FinalizeManagedInscription;
use App\Services\Admissions\InscriptionWorkflowSettings;
use App\Services\Admissions\ManagedInscriptionSequence;
use App\Services\Admissions\ManagedInscriptionWorkflow;
use App\Services\Frais\SoldesParSouscription;
use App\Services\MailPulse\MailPulseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

/**
 * Parcours complet : candidature acceptée → RDV → caisse → pièces →
 * activation → profil → choix de classe → inscription, et ses bords.
 */
class ManagedInscriptionEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPAnneeUniversitaire $anneeDossier;
    private ESBTPFiliere $filiere;
    private ESBTPNiveauEtude $niveau;
    private ESBTPClasse $classe;
    private ESBTPFraisCategory $fraisInscription;
    private ESBTPPieceDossier $piece;
    private User $agent;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $mailpulse = Mockery::mock(MailPulseClient::class)->shouldIgnoreMissing();
        $this->app->instance(MailPulseClient::class, $mailpulse);

        app(InscriptionWorkflowSettings::class)->ensureDefaults();
        $this->reglage(InscriptionWorkflowSettings::ENABLED, '1');
        $this->reglage(InscriptionWorkflowSettings::MODE, InscriptionWorkflowSettings::MODE_CAISSE_AVANT_PIECES);
        $this->reglage(InscriptionWorkflowSettings::REQUIRE_RDV, '1');
        $this->reglage(InscriptionWorkflowSettings::CLASS_CHOICE_ACTOR, InscriptionWorkflowSettings::CLASS_ACTOR_STUDENT);
        $this->reglage(InscriptionWorkflowSettings::CLASS_CHOICE_ONCE, '1');
        $this->reglage(InscriptionWorkflowSettings::NOTIFY_WHATSAPP, '0');

        // L'année courante globale n'est PAS celle du dossier : les places et
        // l'inscription doivent suivre l'année de la candidature.
        ESBTPAnneeUniversitaire::factory()->create(['name' => '2025-2026', 'is_current' => true]);
        $this->anneeDossier = ESBTPAnneeUniversitaire::factory()->create(['name' => '2026-2027', 'is_current' => false]);
        $this->filiere = ESBTPFiliere::factory()->create();
        $this->niveau = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 1', 'year' => 1]);
        $this->classe = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'places_totales' => 1,
            'is_active' => true,
        ]);
        $this->agent = User::factory()->create();

        // Le garde « installed » renvoie vers l'assistant tant qu'aucun
        // superAdmin n'existe.
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole(\Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web'));

        $this->fraisInscription = $this->frais("FRAIS D'INSCRIPTION", 50000, 1);
        $this->frais('FRAIS DE SCOLARITE', 450000, 2);

        $this->piece = ESBTPPieceDossier::create([
            'code' => 'EXTRAIT',
            'libelle' => 'Extrait de naissance',
            'is_obligatoire' => true,
            'forme_attendue' => 'original',
            'exemplaires_par_inscription' => 2,
            'appartenance' => 'inscription',
            'echeance' => 'inscription',
            'is_active' => true,
            'ordre' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function parcours_complet_caisse_puis_pieces_jusqua_l_inscription(): void
    {
        $candidature = $this->candidature();
        $managed = app(ManagedInscriptionWorkflow::class);

        $workflow = $managed->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $this->assertTrue($workflow->paymentRecorded());
        $this->assertSame($this->fraisInscription->id, (int) $workflow->paiement->frais_category_id);
        $this->assertSame('validé', $workflow->paiement->status);
        $this->assertSame(ESBTPCandidatureWorkflow::STATE_AWAITING_DOCUMENTS, $workflow->state);
        $this->assertNotNull($workflow->activation_token_hash, 'Activation après paiement : un lien est émis.');

        // Double clic / second guichet : aucun second paiement.
        $managed->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $this->assertSame(1, ESBTPPaiement::where('candidature_id', $candidature->id)->count());

        $managed->receivePiece($workflow, $this->piece->id, 2, $this->agent->id);
        $workflow = $managed->validateDocuments($workflow->fresh(), $this->agent->id);
        $this->assertTrue($workflow->documentsValidated());

        $token = $managed->issueActivation($workflow)['token'];
        $workflow = $managed->activate($token, 'MotDePasse123');
        $this->assertTrue($workflow->accessActivated());
        $this->assertNotNull($workflow->etudiant->user->email_verified_at, 'Lien reçu par e-mail : adresse prouvée.');

        $workflow->forceFill(['profile_completed_at' => now(), 'profile_payload' => []])->save();

        // En production, la finalisation part de la requête de l'étudiant connecté.
        $this->actingAs($workflow->etudiant->user);
        $inscription = app(FinalizeManagedInscription::class)->chooseAndFinalize($workflow, $this->classe->id, $workflow->etudiant->user_id);

        $this->assertSame('première_inscription', $inscription->type_inscription);
        $this->assertSame('active', $inscription->status);
        $this->assertSame('etudiant_cree', $inscription->workflow_step);
        $this->assertSame($this->anneeDossier->id, (int) $inscription->annee_universitaire_id);
        $this->assertSame(ESBTPCandidature::STATUT_CONVERTIE, $candidature->fresh()->statut);
        $this->assertStringStartsNotWith('PRE-', $workflow->etudiant->fresh()->matricule, 'Inscrit : le matricule provisoire est remplacé.');

        // Finance : 500 000 dus, 50 000 versés à la préinscription → 450 000.
        $soldes = app(SoldesParSouscription::class)->pourInscription($inscription);
        $this->assertEqualsWithDelta(500000, $soldes['total_due'], 0.01);
        $this->assertEqualsWithDelta(50000, $soldes['total_paid'], 0.01);
        $this->assertEqualsWithDelta(450000, $soldes['total_remaining'], 0.01);

        $facture = ESBTPFacture::where('inscription_id', $inscription->id)->firstOrFail();
        $this->assertEqualsWithDelta(50000, (float) $facture->montant_regle, 0.01);
        $this->assertEqualsWithDelta(450000, (float) $facture->montant_du, 0.01);

        // Le dépôt provisoire devient celui de l'inscription.
        $this->assertSame(1, ESBTPPieceDeposee::where('inscription_id', $inscription->id)->count());

        // Après finalisation, plus aucune correction par le parcours.
        $this->expectException(ValidationException::class);
        $managed->assignClassByAdministration($workflow->fresh(), $this->classe->id, $this->agent->id);
    }

    /** @test */
    public function la_caisse_refuse_sans_rendez_vous_et_au_dessus_du_tarif_configure(): void
    {
        $managed = app(ManagedInscriptionWorkflow::class);

        $sansRdv = $this->candidature(rdv: false);
        $this->assertRefus(fn () => $managed->recordPayment($sansRdv, $this->paiement(50000), $this->agent->id), 'rendez_vous');

        $avecRdv = $this->candidature();
        $this->assertRefus(fn () => $managed->recordPayment($avecRdv, $this->paiement(60000), $this->agent->id), 'montant');
        $this->assertSame(0, ESBTPPaiement::where('candidature_id', $avecRdv->id)->count());
    }

    /** @test */
    public function deux_etudiants_ne_prennent_pas_la_derniere_place_et_l_echec_ne_verrouille_rien(): void
    {
        $premier = $this->dossierPretAChoisir();
        $second = $this->dossierPretAChoisir();
        $finalizer = app(FinalizeManagedInscription::class);

        $this->actingAs($premier->etudiant->user);
        $finalizer->chooseAndFinalize($premier, $this->classe->id, $premier->etudiant->user_id);
        $this->actingAs($second->etudiant->user);

        $this->assertRefus(fn () => $finalizer->chooseAndFinalize($second->fresh(), $this->classe->id, $second->etudiant->user_id), 'classe_id');

        $second->refresh();
        $this->assertNull($second->class_locked_at, "Un échec de finalisation ne doit pas bloquer l'étudiant.");
        $this->assertNull($second->selected_class_id);
        $this->assertNull($second->final_inscription_id);

        // Une nouvelle place s'ouvre : le second peut choisir à nouveau.
        $this->classe->update(['places_totales' => 2]);
        $inscription = $finalizer->chooseAndFinalize($second->fresh(), $this->classe->id, $second->etudiant->user_id);
        $this->assertSame($this->classe->id, (int) $inscription->classe_id);
    }

    /** @test */
    public function une_piece_refusee_apres_validation_rouvre_le_controle_et_bloque_la_finalisation(): void
    {
        $workflow = $this->dossierPretAChoisir();
        $managed = app(ManagedInscriptionWorkflow::class);

        $depot = ESBTPPieceDeposee::where('etudiant_id', $workflow->etudiant_id)->firstOrFail();
        $managed->decidePiece($workflow, $depot, false, 'Copie illisible', $this->agent->id);

        $this->assertNull($workflow->fresh()->documents_validated_at);
        $this->actingAs($workflow->etudiant->user);
        $this->assertRefus(
            fn () => app(FinalizeManagedInscription::class)->chooseAndFinalize($workflow->fresh(), $this->classe->id, $workflow->etudiant->user_id),
            'pieces'
        );
    }

    /** @test */
    public function l_activation_whatsapp_ne_vaut_pas_verification_de_l_email(): void
    {
        $candidature = $this->candidature();
        $workflow = app(ManagedInscriptionWorkflow::class)->recordPayment($candidature, $this->paiement(50000), $this->agent->id);

        $workflow = app(AdmissionAccountActivator::class)->activateWorkflow($workflow, 'MotDePasse123');

        $this->assertTrue($workflow->accessActivated());
        $this->assertNull($workflow->etudiant->user->email_verified_at);

        $this->assertRefus(fn () => app(AdmissionAccountActivator::class)->activateWorkflow($workflow, 'AutreMotDePasse1'), 'activation');
    }

    /** @test */
    public function un_lien_regenere_invalide_l_ancien(): void
    {
        $candidature = $this->candidature();
        $managed = app(ManagedInscriptionWorkflow::class);
        $workflow = $managed->recordPayment($candidature, $this->paiement(50000), $this->agent->id);

        $ancien = $managed->issueActivation($workflow)['token'];
        $versionAncienne = ManagedInscriptionWorkflow::linkVersion($workflow->fresh());
        $managed->issueActivation($workflow->fresh());

        $this->assertRefus(fn () => $managed->workflowForActivationToken($ancien), 'token');

        // Le lien courant s'ouvre pour un visiteur non connecté, sans l'habillage d'administration.
        $courant = $managed->issueActivation($workflow->fresh())['token'];
        $this->get(route('esbtp.admissions.workflow.activation.form', ['token' => $courant]))
            ->assertOk()
            ->assertSee('Activez votre espace étudiant');
        $this->assertNotSame($versionAncienne, ManagedInscriptionWorkflow::linkVersion($workflow->fresh()));
    }

    /** @test */
    public function le_lien_ne_part_pas_vers_un_email_non_verifie_quand_la_verification_est_active(): void
    {
        $this->reglage(\App\Services\TenantScolariteSettings::VERIFICATION_CONTACT, '1');
        if (! app(\App\Services\TenantScolariteSettings::class)->verificationContactActive()) {
            $this->markTestSkipped('Réglage de vérification des contacts nommé autrement sur cette version.');
        }

        $candidature = $this->candidature(emailVerifie: false);
        $result = app(ManagedInscriptionWorkflow::class)->issueActivation(
            app(ManagedInscriptionWorkflow::class)->recordPayment($candidature, $this->paiement(50000), $this->agent->id)
        );

        $this->assertFalse($result['email_sent']);
    }

    /** @test */
    public function mode_pieces_avant_caisse_impose_le_rdv_et_l_ordre(): void
    {
        $this->reglage(InscriptionWorkflowSettings::MODE, InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE);
        $managed = app(ManagedInscriptionWorkflow::class);
        $sequence = app(ManagedInscriptionSequence::class);

        $sansRdv = $this->candidature(rdv: false);
        $workflow = $managed->ensure($sansRdv);
        $this->assertSame(ESBTPCandidatureWorkflow::STATE_AWAITING_DOCUMENTS, $workflow->state);
        $this->assertRefus(fn () => $managed->receivePiece($workflow, $this->piece->id, 2, $this->agent->id), 'rendez_vous');

        $avecRdv = $this->candidature();
        $workflow = $managed->ensure($avecRdv);
        $this->assertRefus(fn () => $sequence->assertPaymentAllowed($workflow), 'workflow');
        $this->assertCount(0, $sequence->cashierQueue()->items());

        $managed->receivePiece($workflow, $this->piece->id, 2, $this->agent->id);
        $managed->validateDocuments($workflow->fresh(), $this->agent->id);
        $this->assertSame([$avecRdv->id], collect($sequence->cashierQueue()->items())->pluck('id')->all());
    }

    /** @test */
    public function workflow_desactive_ne_change_rien(): void
    {
        $this->reglage(InscriptionWorkflowSettings::ENABLED, '0');

        $this->assertNull(app(ManagedInscriptionSequence::class)->cashierQueue());
        $this->assertRefus(fn () => app(ManagedInscriptionWorkflow::class)->ensure($this->candidature()), 'workflow');
        $this->assertSame(0, ESBTPCandidatureWorkflow::count());
    }

    /** @test */
    public function un_dossier_sans_sexe_n_est_pas_finalise_et_garde_son_matricule_provisoire(): void
    {
        // Le contrôle de complétude ne joue que sur un matricule PRE- : le
        // remplacer d'abord laissait finaliser un dossier sans sexe, avec un
        // matricule numéroté sur un sexe deviné.
        $workflow = $this->dossierPretAChoisir();
        $workflow->etudiant->forceFill(['sexe' => null])->save();
        $this->actingAs($workflow->etudiant->user);

        $this->assertRefus(
            fn () => app(FinalizeManagedInscription::class)->chooseAndFinalize($workflow->fresh(), $this->classe->id, $workflow->etudiant->user_id),
            'finalisation'
        );

        $this->assertStringStartsWith('PRE-', $workflow->etudiant->fresh()->matricule);
        $this->assertNull($workflow->fresh()->final_inscription_id);
    }

    /** @test */
    public function un_etudiant_ne_voit_que_son_propre_dossier(): void
    {
        $a = $this->dossierPretAChoisir();
        $b = $this->dossierPretAChoisir();

        $this->actingAs($a->etudiant->user)
            ->get(route('esbtp.admissions.workflow.student'))
            ->assertOk()
            ->assertSee($a->candidature->referencePubliqueAffichee())
            ->assertDontSee($b->candidature->referencePubliqueAffichee());

        // Un étudiant n'atteint pas les écrans des guichets.
        $this->actingAs($a->etudiant->user)
            ->get(route('esbtp.admissions.workflow.show', $b->candidature))
            ->assertForbidden();
    }

    /** @test */
    public function la_caisse_n_affecte_pas_de_classe_et_ne_finalise_pas(): void
    {
        foreach (['paiements.create', 'paiements.validate', 'inscriptions.create', 'admin.access', 'inscriptions.validate'] as $nom) {
            \Spatie\Permission\Models\Permission::findOrCreate($nom, 'web');
        }
        $caissier = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $caissier->givePermissionTo(['paiements.create', 'paiements.validate', 'inscriptions.create', 'admin.access']);

        $workflow = $this->dossierPretAChoisir();

        $enAttente = $this->candidature();
        $this->actingAs($caissier)
            ->get(route('esbtp.admissions.workflow.index'))
            ->assertOk()
            ->assertSee($enAttente->referencePubliqueAffichee())
            ->assertDontSee($workflow->candidature->referencePubliqueAffichee());

        $this->actingAs($caissier)
            ->get(route('esbtp.admissions.workflow.show', $workflow->candidature))
            ->assertOk()
            ->assertSee('Préinscription à la caisse');

        $this->actingAs($caissier)
            ->post(route('esbtp.admissions.workflow.finalize', $workflow))
            ->assertForbidden();
        $this->actingAs($caissier)
            ->post(route('esbtp.admissions.workflow.class.choose-admin', $workflow), ['classe_id' => $this->classe->id])
            ->assertForbidden();

        $this->assertNull($workflow->fresh()->final_inscription_id);
    }

    /** @test */
    public function un_dossier_paye_reste_accessible_au_guichet_des_pieces(): void
    {
        $candidature = $this->candidature();
        app(ManagedInscriptionWorkflow::class)->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $sequence = app(ManagedInscriptionSequence::class);

        $this->assertCount(0, $sequence->dossiers(ManagedInscriptionSequence::ETAPE_CAISSE)->items());
        $this->assertSame([$candidature->id], collect($sequence->dossiers(ManagedInscriptionSequence::ETAPE_PIECES)->items())->pluck('id')->all());
        $this->assertSame([$candidature->id], collect($sequence->dossiers(ManagedInscriptionSequence::ETAPE_TOUS)->items())->pluck('id')->all());
    }

    /** @test */
    public function un_contact_non_prouve_est_annonce_et_se_confirme_au_guichet(): void
    {
        $this->reglage(\App\Services\TenantScolariteSettings::VERIFICATION_CONTACT, '1');
        $this->reglage(InscriptionWorkflowSettings::ACCOUNT_ACTIVATION_STEP, InscriptionWorkflowSettings::ACTIVATION_AFTER_DOCUMENTS);

        $managed = app(ManagedInscriptionWorkflow::class);
        $candidature = $this->candidature(emailVerifie: false);
        $workflow = $managed->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $managed->receivePiece($workflow, $this->piece->id, 2, $this->agent->id);
        ESBTPPieceDeposee::where('etudiant_id', $workflow->etudiant_id)->update(['etat' => 'validee']);
        $workflow = $managed->validateDocuments($workflow->fresh(), $this->agent->id);

        // Rien n'est parti : l'écran le dit au lieu d'annoncer un lien reçu.
        $this->assertSame(
            "Lien d'activation non envoyé : aucun e-mail ni numéro vérifié. Confirmez le contact avec l'étudiant.",
            app(\App\Services\Admissions\ManagedWorkflowPresenter::class)->prochaineEtape($workflow->fresh())
        );

        foreach (['pieces_dossier.suivre', 'admin.access'] as $nom) {
            \Spatie\Permission\Models\Permission::findOrCreate($nom, 'web');
        }
        $secretariat = User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()]);
        $secretariat->givePermissionTo(['pieces_dossier.suivre', 'admin.access']);

        $this->actingAs($secretariat)
            ->get(route('esbtp.admissions.workflow.show', $candidature))
            ->assertOk()
            ->assertSee("Aucun canal n'est actuellement autorisé pour un nouvel envoi.", false)
            ->assertSee($candidature->email);

        $this->actingAs($secretariat)
            ->post(route('esbtp.admissions.workflow.activation.confirm-contact', $workflow), [
                'empreinte' => 'perime',
            ])
            ->assertSessionHas('warning');
        $this->assertNull($candidature->fresh()->contact_confirme_at, 'Une empreinte périmée ne confirme rien.');

        $this->actingAs($secretariat)
            ->post(route('esbtp.admissions.workflow.activation.confirm-contact', $workflow), [
                'empreinte' => $candidature->fresh()->empreinteContact(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $m) => str_starts_with($m, 'Contact confirmé.'));

        $candidature->refresh();
        // Sous RefreshDatabase, l'e-mail part au commit : on vérifie l'état
        // qui le rend possible plutôt que le message rendu.
        $this->assertTrue(app(\App\Services\Admissions\AdmissionActivationNotifier::class)->emailUsable($workflow->fresh()));
        $this->assertNotNull($candidature->contact_confirme_at);
        $this->assertSame($secretariat->id, (int) $candidature->contact_confirme_par);
        // Même confirmation que la file des demandes : le badge tombe aussi.
        $this->assertFalse($candidature->contactMarque());
    }

    /** @test */
    public function le_lien_par_e_mail_part_par_mailpulse_comme_les_convocations(): void
    {
        // Le mailer de l'application n'est pas configuré partout ; MailPulse,
        // qui porte déjà les convocations, l'est. Le lien prend ce chemin.
        $candidature = $this->candidature();
        $mailpulse = Mockery::mock(MailPulseClient::class);
        $mailpulse->shouldReceive('createOrUpdateContact')->andReturn(new \App\Services\MailPulse\MailPulseResult(true, 'ok'));
        $mailpulse->shouldReceive('sendEmailMessage')->atLeast()->once()
            ->withArgs(fn (array $message) => ($message['recipient']['value'] ?? null) === $candidature->email
                && str_contains($message['content']['text'] ?? '', '/activation/')
                // La version mise en page : gabarit commun, bouton vers le même lien.
                && str_contains($message['metadata']['email_html'] ?? '', 'Activer mon espace')
                && str_contains($message['metadata']['email_html'] ?? '', '/activation/'))
            ->andReturn(new \App\Services\MailPulse\MailPulseResult(true, 'queued', 202, null, 'msg-1', null, null, null, 'accepted'));
        $this->app->instance(MailPulseClient::class, $mailpulse);

        $managed = app(ManagedInscriptionWorkflow::class);
        $workflow = $managed->recordPayment($candidature, $this->paiement(50000), $this->agent->id);

        // Hors transaction de test, l'envoi est immédiat : on le prouve sur
        // le notificateur, qui est ce que la caisse et le guichet appellent.
        $this->assertTrue(app(\App\Services\Admissions\AdmissionActivationNotifier::class)
            ->sendEmail($workflow->fresh(), route('esbtp.admissions.workflow.activation.form', ['token' => 'x'])));
        $this->assertDatabaseHas('admission_activation_dispatches', [
            'workflow_id' => $workflow->id,
            'channel' => 'email',
            'status' => 'accepted',
            'provider_message_id' => 'msg-1',
        ]);
    }

    /** @test */
    public function un_numero_verifie_n_empeche_pas_de_confirmer_l_email(): void
    {
        // Cas réel de recette : numéro prouvé, e-mail non. Le lien ne part
        // que par WhatsApp ; si le message n'arrive pas, l'agent doit pouvoir
        // confirmer l'e-mail au lieu de rester sans issue.
        $this->reglage(\App\Services\TenantScolariteSettings::VERIFICATION_CONTACT, '1');
        $this->reglage(InscriptionWorkflowSettings::NOTIFY_WHATSAPP, '1');
        $this->reglage(InscriptionWorkflowSettings::ACCOUNT_ACTIVATION_STEP, InscriptionWorkflowSettings::ACTIVATION_AFTER_PAYMENT);

        $candidature = $this->candidature(emailVerifie: false);
        $candidature->forceFill(['telephone_verifie_at' => now()])->save();
        $workflow = app(ManagedInscriptionWorkflow::class)->recordPayment($candidature, $this->paiement(50000), $this->agent->id);

        $admin = User::role('superAdmin')->first();
        $this->actingAs($admin)
            ->get(route('esbtp.admissions.workflow.show', $candidature))
            ->assertOk()
            ->assertSee("L'e-mail du dossier n'est pas vérifié", false);

        $this->actingAs($admin)
            ->post(route('esbtp.admissions.workflow.activation.confirm-contact', $workflow), [
                'empreinte' => $candidature->fresh()->empreinteContact(),
            ])
            ->assertSessionHas('success', fn (string $m) => str_starts_with($m, 'Contact confirmé.'));

        $this->assertTrue(app(\App\Services\Admissions\AdmissionActivationNotifier::class)->emailUsable($workflow->fresh()));
        $this->actingAs($admin)
            ->get(route('esbtp.admissions.workflow.show', $candidature))
            ->assertDontSee("L'e-mail du dossier n'est pas vérifié", false);
    }

    /** @test */
    public function un_lien_d_activation_perime_affiche_une_page_et_ne_boucle_pas(): void
    {
        // Ouvert depuis un e-mail, le lien n'a pas de page précédente : l'erreur
        // de validation renvoyait vers la même URL, jusqu'au « trop de redirections ».
        $this->get(route('esbtp.admissions.workflow.activation.form', ['token' => 'inconnu']))
            ->assertStatus(410)
            ->assertSee('Ce lien ne peut plus servir', false)
            ->assertSee("Ce lien d'activation est invalide ou expiré.");
    }

    /** @test */
    public function le_formulaire_classique_refuse_une_candidature_du_parcours(): void
    {
        $candidature = $this->candidature();
        $admin = User::role('superAdmin')->first();

        $this->actingAs($admin)
            ->get(route('esbtp.inscriptions.create', ['candidature' => $candidature->id]))
            ->assertRedirect(route('esbtp.admissions.workflow.show', $candidature->id));

        $this->actingAs($admin)
            ->getJson(route('esbtp.demandes.preparer-inscription', $candidature))
            ->assertStatus(422);

        // En attente : pas encore de dossier en cours, donc pas de boucle vers
        // lui ; la file des demandes, où l'on accepte.
        $enAttente = $this->candidature();
        $enAttente->forceFill(['statut' => ESBTPCandidature::STATUT_EN_ATTENTE])->save();
        $this->actingAs($admin)
            ->get(route('esbtp.inscriptions.create', ['candidature' => $enAttente->id]))
            ->assertRedirect(route('esbtp.demandes.index', ['type' => 'nouvelle']));

        // Parcours éteint : le formulaire classique reprend la candidature.
        $this->reglage(InscriptionWorkflowSettings::ENABLED, '0');
        $this->actingAs($admin)
            ->get(route('esbtp.inscriptions.create', ['candidature' => $candidature->id]))
            ->assertOk();
    }

    /** @test */
    public function lien_whatsapp_devenu_expire_refuse_l_ouverture_et_l_activation_meme_avec_signature_valide(): void
    {
        $this->reglage(InscriptionWorkflowSettings::ACCOUNT_ACTIVATION_STEP, InscriptionWorkflowSettings::ACTIVATION_AFTER_PAYMENT);
        $candidature = $this->candidature();
        $workflow = app(ManagedInscriptionWorkflow::class)->recordPayment(
            $candidature,
            $this->paiement(50000),
            $this->agent->id
        );

        $this->assertNotNull($workflow->activation_token_hash);
        $workflow->forceFill(['activation_token_expires_at' => now()->subMinute()])->save();
        $version = ManagedInscriptionWorkflow::linkVersion($workflow->fresh());
        $getUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'esbtp.admissions.workflow.activation.signed.form',
            now()->addMinutes(10),
            ['workflow' => $workflow->id, 'v' => $version]
        );
        $postUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'esbtp.admissions.workflow.activation.signed.submit',
            now()->addMinutes(10),
            ['workflow' => $workflow->id, 'v' => $version]
        );

        // Même si la signature est encore valide, le jeton métier expire.
        $this->get($getUrl)->assertStatus(410)->assertSee('expiré');
        $this->post($postUrl, [
            'password' => 'MotDePasse123!',
            'password_confirmation' => 'MotDePasse123!',
        ])->assertSessionHasErrors('activation');
        $this->assertFalse($workflow->fresh()->accessActivated());
    }

    /** @test */
    public function un_envoi_mailpulse_en_attente_n_est_pas_considere_comme_livre_et_une_tentative_est_idempotente(): void
    {
        $candidature = $this->candidature();
        $workflow = app(ManagedInscriptionWorkflow::class)
            ->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $log = app(\App\Services\Admissions\AdmissionActivationDispatchLog::class);
        $result = new \App\Services\MailPulse\MailPulseResult(
            true, 'pending', 202, 'test-request-1', 'provider-123', null, null, null, 'pending_reconciliation'
        );

        $log->record($workflow, 'whatsapp', 'admission-unique-test-1', $result);
        $log->record($workflow, 'whatsapp', 'admission-unique-test-1', $result);

        $this->assertSame(1, \App\Models\AdmissionActivationDispatch::query()
            ->where('request_id', 'admission-unique-test-1')->count());
        $this->assertDatabaseHas('admission_activation_dispatches', [
            'workflow_id' => $workflow->id,
            'channel' => 'whatsapp',
            'status' => 'pending',
            'provider_message_id' => 'provider-123',
        ]);
        $this->assertStringNotContainsString('Livré',
            \App\Services\Admissions\AdmissionActivationDispatchLog::label('pending'));
        $overview = $log->overview();
        $this->assertTrue($overview['available']);
        $this->assertGreaterThanOrEqual(1, $overview['counts']['pending']);
        $this->assertNotEmpty($overview['entries']);
        $this->assertArrayNotHasKey('request_id', $overview['entries']->first()->getAttributes());
        $this->assertArrayNotHasKey('provider_message_id', $overview['entries']->first()->getAttributes());
    }

    /** @test */
    public function activation_expiree_est_refusee_dans_la_transaction_sans_changer_le_mot_de_passe(): void
    {
        $candidature = $this->candidature();
        $workflow = app(ManagedInscriptionWorkflow::class)
            ->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $workflow->loadMissing('etudiant.user');
        $this->assertNotNull($workflow->activation_token_hash);
        $ancienHash = $workflow->etudiant->user->password;

        $workflow->forceFill(['activation_token_expires_at' => now()->subMinute()])->save();

        $this->assertRefus(
            fn () => app(AdmissionAccountActivator::class)->activateWorkflow($workflow->fresh(), 'SecretUniqueEtudiant123'),
            'activation'
        );
        $apres = $workflow->fresh(['etudiant.user']);
        $this->assertFalse($apres->accessActivated());
        $this->assertSame($ancienHash, $apres->etudiant->user->password);
    }

    /** @test */
    public function une_invitation_en_outbox_est_enregistree_avant_tout_envoi_et_reprise_avec_la_meme_cle(): void
    {
        $this->reglage(InscriptionWorkflowSettings::RELIABLE_OUTBOX, '1');
        $candidature = $this->candidature();
        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('sendEmailMessage')->once()
            ->andReturn(new \App\Services\MailPulse\MailPulseResult(
                true, 'accepted', 202, 'provider-request-1', 'msg-1',
                null, null, null, 'accepted'
            ));
        $client->shouldReceive('createOrUpdateContact')->once()
            ->andReturn(new \App\Services\MailPulse\MailPulseResult(true, 'ok'));
        $this->app->instance(MailPulseClient::class, $client);

        $workflow = app(ManagedInscriptionWorkflow::class)
            ->recordPayment($candidature, $this->paiement(50000), $this->agent->id);

        $queued = \App\Models\AdmissionActivationDispatch::query()
            ->where('workflow_id', $workflow->id)->where('channel', 'email')->firstOrFail();
        $this->assertSame('queued', $queued->status);
        $this->assertNotEmpty($queued->encrypted_payload);
        $this->assertStringNotContainsString('/activation/', $queued->encrypted_payload);

        $result = app(\App\Services\Admissions\AdmissionActivationOutbox::class)->process(
            10, app(\App\Services\Admissions\AdmissionActivationNotifier::class),
            app(ManagedInscriptionWorkflow::class)
        );
        $this->assertSame(1, $result['accepted']);
        $queued->refresh();
        $this->assertSame('accepted', $queued->status);
        $this->assertNull($queued->encrypted_payload);
        $this->assertSame(1, \App\Models\AdmissionActivationDispatch::query()
            ->where('request_id', $queued->request_id)->count());

        // Le second passage ne renvoie rien.
        $result = app(\App\Services\Admissions\AdmissionActivationOutbox::class)->process(
            10, app(\App\Services\Admissions\AdmissionActivationNotifier::class),
            app(ManagedInscriptionWorkflow::class)
        );
        $this->assertSame(0, $result['accepted']);
    }

    /** @test */
    public function le_rollback_annule_egalement_la_demande_d_invitation_persistante(): void
    {
        $this->reglage(InscriptionWorkflowSettings::RELIABLE_OUTBOX, '1');
        $candidature = $this->candidature();
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($candidature): void {
                app(ManagedInscriptionWorkflow::class)
                    ->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
                throw new \RuntimeException('force_rollback_for_outbox_test');
            });
            $this->fail('Le rollback était attendu');
        } catch (\RuntimeException $e) {
            $this->assertSame('force_rollback_for_outbox_test', $e->getMessage());
        }
        $this->assertDatabaseCount('admission_activation_dispatches', 0);
    }

    /** @test */
    public function le_portail_familial_exige_un_compte_distinct_une_habilitation_et_un_consentement_du_majeur(): void
    {
        $this->reglage(InscriptionWorkflowSettings::FAMILY_PORTAL, '1');
        $candidature = $this->candidature();
        $workflow = app(ManagedInscriptionWorkflow::class)
            ->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $etudiant = $workflow->fresh(['etudiant'])->etudiant;
        $responsableUser = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $parent = \App\Models\ESBTPParent::create([
            'user_id' => $responsableUser->id,
            'nom' => 'KOUAME',
            'prenoms' => 'Fatou',
            'sexe' => 'F',
            'telephone' => '+2250700000001',
        ]);
        $etudiant->parents()->attach($parent->id, ['relation' => 'parent', 'is_tuteur' => true]);

        $acces = app(\App\Services\Familles\AccesFamilial::class);
        $this->assertRefus(
            fn () => $acces->approuver($parent, $etudiant, $this->agent, 'identite_guichet', 'piece-ref-01', null),
            'consent_reference'
        );

        $grant = $acces->approuver(
            $parent, $etudiant, $this->agent, 'identite_guichet',
            'piece-ref-01', 'autorisation-signee-01'
        );
        $this->assertTrue($acces->peutConsulter($grant->fresh(['parent', 'etudiant']), $responsableUser));

        $responsableUser->assignRole(\Spatie\Permission\Models\Role::findOrCreate('etudiant', 'web'));
        $this->assertFalse($acces->peutConsulter($grant->fresh(['parent', 'etudiant']), $responsableUser));
        $responsableUser->removeRole('etudiant');

        $acces->revoquer($grant, $this->agent);
        $this->assertFalse($acces->peutConsulter($grant->fresh(['parent', 'etudiant']), $responsableUser));
    }

    /** @test */
    public function le_responsable_recoit_une_invitation_distincte_et_un_lien_personnel_a_usage_unique(): void
    {
        $this->reglage(InscriptionWorkflowSettings::FAMILY_PORTAL, '1');
        $candidature = $this->candidature();
        $workflow = app(ManagedInscriptionWorkflow::class)
            ->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $etudiant = $workflow->fresh(['etudiant'])->etudiant;
        $parent = \App\Models\ESBTPParent::create([
            'nom' => 'KOUAME', 'prenoms' => 'Yao', 'sexe' => 'M',
            'telephone' => '+2250700000002', 'email' => 'responsable@example.test',
        ]);
        $etudiant->parents()->attach($parent->id, ['relation' => 'parent', 'is_tuteur' => true]);
        $grant = app(\App\Services\Familles\AccesFamilial::class)
            ->approuver($parent, $etudiant, $this->agent, 'identite_guichet',
                'reference-piece-02', 'accord-etudiant-majeur-02');

        $service = app(\App\Services\Familles\InvitationResponsable::class);
        $this->assertRefus(
            fn () => $service->preparer($grant, $this->agent, 'autre@example.test'),
            'confirmed_email'
        );
        $invite = $service->preparer($grant, $this->agent, 'responsable@example.test');
        $parent->refresh();
        $this->assertNotNull($parent->user_id);
        $this->assertNotSame((int) $etudiant->user_id, (int) $parent->user_id);
        $this->assertSame('queued', $invite->status);
        $this->assertNotEmpty($invite->encrypted_url);
        $this->assertStringNotContainsString('/invitation/', $invite->encrypted_url);
        $url = \Illuminate\Support\Facades\Crypt::decryptString($invite->encrypted_url);
        $token = basename(parse_url($url, PHP_URL_PATH));

        $client = Mockery::mock(MailPulseClient::class);
        $client->shouldReceive('sendEmailMessage')->once()
            ->andReturn(new \App\Services\MailPulse\MailPulseResult(
                true, 'accepted', 202, 'family-test-1', 'msg-family',
                null, null, null, 'accepted'
            ));
        $result = $service->traiter(10, $client);
        $this->assertSame(1, $result['accepted']);
        $invite->refresh();
        $this->assertSame('accepted', $invite->status);
        $this->assertNull($invite->encrypted_url);

        $user = $service->activer($token, 'MotDePasseFamillePrive123!');
        $this->assertTrue($user->is_active);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('MotDePasseFamillePrive123!', $user->password));
        $this->assertNotNull($invite->fresh()->used_at);
        $this->assertRefus(fn () => $service->activer($token, 'AutreMotDePasse123456!'), 'token');
    }

    // ── Préparation ─────────────────────────────────────────────────────

    private function dossierPretAChoisir(): ESBTPCandidatureWorkflow
    {
        $managed = app(ManagedInscriptionWorkflow::class);
        $candidature = $this->candidature();
        $workflow = $managed->recordPayment($candidature, $this->paiement(50000), $this->agent->id);
        $managed->receivePiece($workflow, $this->piece->id, 2, $this->agent->id);
        ESBTPPieceDeposee::where('etudiant_id', $workflow->etudiant_id)->update(['etat' => 'validee']);
        $workflow = $managed->validateDocuments($workflow->fresh(), $this->agent->id);
        $workflow = app(AdmissionAccountActivator::class)->activateWorkflow($workflow, 'MotDePasse123');
        $workflow->forceFill(['profile_completed_at' => now(), 'profile_payload' => []])->save();

        return $workflow->fresh(['candidature', 'etudiant.user']);
    }

    private function candidature(bool $rdv = true, bool $emailVerifie = true): ESBTPCandidature
    {
        $suffixe = uniqid();
        $candidature = ESBTPCandidature::forceCreate([
            'statut' => ESBTPCandidature::STATUT_ACCEPTEE,
            'nom' => 'KOUASSI',
            'prenoms' => 'Awa '.$suffixe,
            'sexe' => 'F',
            'date_naissance' => '2006-01-01',
            'lieu_naissance' => 'Yamoussoukro',
            'email' => 'awa.'.$suffixe.'@example.test',
            'telephone' => '+22507'.random_int(10000000, 99999999),
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->anneeDossier->id,
            'email_verifie_at' => $emailVerifie ? now() : null,
            'consentement_at' => now(),
            'traite_at' => now(),
        ]);

        if ($rdv) {
            $creneau = ESBTPRdvCreneau::firstOrCreate([
                'annee_universitaire_id' => $this->anneeDossier->id,
                'date' => now()->addDay()->toDateString(),
                'heure_debut' => '08:00:00',
            ], [
                'heure_fin' => '08:30:00',
                'capacite' => 50,
                'ouvert' => true,
            ]);
            ESBTPRdvReservation::create([
                'creneau_id' => $creneau->id,
                'candidature_id' => $candidature->id,
                'statut' => 'confirmee',
                'nom' => $candidature->nom,
                'prenoms' => $candidature->prenoms,
                'telephone' => $candidature->telephone,
                'date_naissance' => '2006-01-01',
            ]);
        }

        return $candidature->fresh();
    }

    private function frais(string $nom, int $montant, int $ordre): ESBTPFraisCategory
    {
        $categorie = ESBTPFraisCategory::factory()->create([
            'name' => $nom, 'is_mandatory' => true, 'is_active' => true, 'default_amount' => $montant, 'sort_order' => $ordre,
        ]);

        ESBTPFraisConfiguration::create([
            'frais_category_id' => $categorie->id,
            'systeme_academique' => 'BTS',
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $this->niveau->id,
            'annee_universitaire_id' => null,
            'amount' => $montant,
            'amount_affecte' => $montant,
            'amount_reaffecte' => $montant,
            'amount_non_affecte' => $montant,
            'payment_deadline_days' => 30,
            'is_active' => true,
            'created_by' => User::factory()->create()->id,
        ]);

        return $categorie;
    }

    /** @return array<string, mixed> */
    private function paiement(int $montant): array
    {
        return [
            'frais_category_id' => $this->fraisInscription->id,
            'montant' => $montant,
            'mode_paiement' => 'especes',
        ];
    }

    private function reglage(string $cle, string $valeur): void
    {
        Setting::setOrCreate($cle, $valeur, 'scolarite', 'string');
        \Illuminate\Support\Facades\Cache::flush();
    }

    private function assertRefus(callable $action, string $champ): void
    {
        try {
            $action();
            $this->fail("Refus attendu sur « {$champ} ».");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($champ, $e->errors(), json_encode($e->errors(), JSON_UNESCAPED_UNICODE));
        }
    }
}
