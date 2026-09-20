<?php

namespace Tests\Feature\Scolarite;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPDocumentApproval;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\User;
use App\Services\DocumentPrintGuard;
use App\Services\PermissionRegistry;
use App\Services\PrintDecision;
use App\Services\TenantScolariteSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Qui debloque l'impression d'un document de scolarite, et pour quel document.
 *
 * L'organigramme scolarite separe deux mains : la responsable ACCORDE, le
 * service IMPRIME. Les tests existants verifient la garde elle-meme
 * (DocumentPrintGatesTest) et le contenu des paquets de droits
 * (ScolariteOrgChartPermissionsTest) ; aucun ne verifiait que les deux roles
 * reels, avec leurs droits reels, jouent bien chacun leur moitie.
 *
 * C'est la question que pose une ecole qui active le reglage : « qu'est-ce que
 * la responsable doit donner pour que le guichet puisse tirer un certificat ? »
 * La reponse tient en une phrase, et ce fichier la fige : rien de plus que
 * `documents.print`, qu'il a deja — il lui manque un ACCORD, document par
 * document, que seul `documents.approve` sait donner.
 */
class ScolariteDocumentsRolesTest extends TestCase
{
    use DatabaseTransactions;

    private User $responsable;

    private User $service;

    private ESBTPInscription $inscription;

    private int $etudiantId;

    protected function setUp(): void
    {
        parent::setUp();

        // Une instance en service a toujours un superAdmin. Sans lui, le
        // middleware « installed » renvoie toute requete vers l'installateur et
        // les routes testees ne s'executent jamais.
        $this->utilisateurAvecLeRole('superAdmin');

        $this->responsable = $this->utilisateurAvecLeRole('responsableScolarite');
        $this->service = $this->utilisateurAvecLeRole('serviceScolarite');

        $annee = ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        $this->inscription = ESBTPInscription::factory()->create([
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
            'created_by' => $this->responsable->id,
            'date_inscription' => now()->subMonths(6),
        ]);
        $this->etudiantId = (int) $this->inscription->etudiant_id;

        // L'etudiant est a jour : sans cela c'est le solde qui bloque, et on ne
        // verrait jamais la porte suivante.
        $this->soldeATaJour(40000);

        // Le reglage de l'instance, ecrit pour de vrai : c'est lui qui ouvre la
        // porte de l'accord. ISLG l'a a 1.
        SettingsHelper::setOrCreate(TenantScolariteSettings::PRINT_REQUIRES_APPROVAL, '1', 'scolarite', 'boolean');
    }

    public function test_le_service_scolarite_imprime_mais_n_approuve_pas(): void
    {
        $this->assertTrue($this->service->can('documents.print'));
        $this->assertFalse($this->service->can('documents.approve'));

        $this->assertTrue($this->responsable->can('documents.approve'));
    }

    public function test_sans_accord_les_trois_documents_restent_bloques(): void
    {
        $guard = app(DocumentPrintGuard::class);

        foreach (['certificat', 'attestation', 'bulletin'] as $type) {
            $decision = $guard->decide($this->service, $type, $this->etudiantId);

            $this->assertFalse($decision->allowed, "{$type} ne doit pas sortir sans accord");
            $this->assertSame(PrintDecision::APPROVAL, $decision->reason, "{$type} : c'est l'accord qui manque, pas le droit");
            $this->assertSame(0.0, $decision->solde, "{$type} : l'etudiant est a jour, le solde ne doit pas etre en cause");
        }
    }

    public function test_l_accord_de_la_responsable_debloque_chacun_des_trois_documents(): void
    {
        $guard = app(DocumentPrintGuard::class);

        foreach (['certificat', 'attestation', 'bulletin'] as $type) {
            $this->accorder($type);

            $this->assertTrue(
                $guard->decide($this->service, $type, $this->etudiantId)->allowed,
                "{$type} doit sortir une fois l'accord donne"
            );
        }
    }

    public function test_un_accord_ne_vaut_que_pour_le_document_qu_il_nomme(): void
    {
        $this->accorder('certificat');

        $guard = app(DocumentPrintGuard::class);

        $this->assertTrue($guard->decide($this->service, 'certificat', $this->etudiantId)->allowed);
        $this->assertSame(
            PrintDecision::APPROVAL,
            $guard->decide($this->service, 'bulletin', $this->etudiantId)->reason,
            'Accorder un certificat ne doit pas ouvrir le bulletin.'
        );
    }

    public function test_le_service_ne_peut_pas_signer_l_accord_a_la_place_de_la_responsable(): void
    {
        $approval = ESBTPDocumentApproval::create([
            'document_type' => 'certificat',
            'etudiant_id' => $this->etudiantId,
            'status' => ESBTPDocumentApproval::STATUS_PENDING,
            'requested_by' => $this->service->id,
        ]);

        $this->actingAs($this->service)
            ->post(route('esbtp.documents.approvals.approve', $approval))
            ->assertForbidden();

        $this->assertSame(
            ESBTPDocumentApproval::STATUS_PENDING,
            $approval->fresh()->status,
            "Le guichet ne doit pas pouvoir faire passer sa propre demande en 'approuve'."
        );
    }

    public function test_la_responsable_approuve_la_demande_du_guichet(): void
    {
        $approval = ESBTPDocumentApproval::create([
            'document_type' => 'attestation',
            'etudiant_id' => $this->etudiantId,
            'status' => ESBTPDocumentApproval::STATUS_PENDING,
            'requested_by' => $this->service->id,
        ]);

        $this->actingAs($this->responsable)
            ->post(route('esbtp.documents.approvals.approve', $approval))
            ->assertRedirect();

        $this->assertSame(ESBTPDocumentApproval::STATUS_APPROVED, $approval->fresh()->status);
        $this->assertTrue(
            app(DocumentPrintGuard::class)->decide($this->service, 'attestation', $this->etudiantId)->allowed
        );
    }

    public function test_la_page_du_certificat_n_offre_l_impression_qu_une_fois_l_accord(): void
    {
        // Cette page reste ouverte sans accord — c'est d'elle qu'on le demande —
        // mais elle ne donne pas les boutons qui sortent le document.
        $this->actingAs($this->service);

        $avant = $this->get(route('esbtp.etudiants.certificat.preview', $this->etudiantId));
        $avant->assertOk();
        $avant->assertSee("Demander l'approbation", false);
        $avant->assertDontSee('Générer PDF', false);

        $this->accorder('certificat');

        $apres = $this->get(route('esbtp.etudiants.certificat.preview', $this->etudiantId));
        $apres->assertOk();
        $apres->assertSee('Générer PDF', false);
        $apres->assertDontSee("Demander l'approbation", false);
    }

    public function test_le_pdf_du_certificat_reste_ferme_sans_accord(): void
    {
        // Le PDF, lui, est la sortie : il se refuse tant que l'accord manque.
        $this->actingAs($this->service);

        $this->refus($this->get(route('esbtp.etudiants.certificat.preview-pdf', $this->etudiantId)));

        $this->accorder('certificat');

        // L'accord leve le refus. Le rendu du PDF lui-meme depend des donnees de
        // l'etudiant (annee, classe, moyennes) et n'est pas l'objet de ce test :
        // on verifie que la porte s'ouvre, pas ce qui sort derriere.
        $this->assertNotSame(
            403,
            $this->get(route('esbtp.etudiants.certificat.preview-pdf', $this->etudiantId))->status(),
            "L'accord donne, le PDF ne doit plus etre refuse."
        );
    }

    public function test_les_deux_roles_de_scolarite_atteignent_la_route_du_certificat(): void
    {
        // Le certificat de scolarite se tirait derriere le seul admin.access,
        // que ni la responsable ni le service ne portent : le metier repondait
        // « Acces restreint » a ceux dont c'est justement le metier.
        $this->accorder('certificat');

        foreach (['responsable' => $this->responsable, 'service' => $this->service] as $qui => $agent) {
            $this->actingAs($agent)
                ->get(route('esbtp.etudiants.certificat.preview', $this->etudiantId))
                ->assertOk("Le {$qui} scolarite doit atteindre le certificat.");
        }
    }

    public function test_sans_aucun_des_trois_droits_c_est_le_droit_qui_manque_pas_l_accord(): void
    {
        $quidam = User::factory()->create();

        $decision = app(DocumentPrintGuard::class)->decide($quidam, 'certificat', $this->etudiantId);

        $this->assertSame(PrintDecision::PERMISSION, $decision->reason);
    }

    /**
     * Le refus d'impression prend deux formes selon ce que le client accepte :
     * une redirection avec le message en session pour un navigateur, un 403
     * pour un appel qui attend du JSON. Les deux disent la meme chose.
     */
    private function refus(\Illuminate\Testing\TestResponse $reponse): void
    {
        $this->assertContains(
            $reponse->status(),
            [302, 403],
            'Le document ne doit pas sortir sans accord ; recu : '.$reponse->status()
        );
    }

    /**
     * Un utilisateur portant le paquet de droits reel du registre pour ce role.
     */
    private function utilisateurAvecLeRole(string $role): User
    {
        $registry = app(PermissionRegistry::class);
        $roleModel = Role::findOrCreate($role, 'web');

        foreach ($registry->defaultPermissionsFor($role) as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $roleModel->syncPermissions($registry->defaultPermissionsFor($role));

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user->fresh();
    }

    private function accorder(string $type): void
    {
        ESBTPDocumentApproval::create([
            'document_type' => $type,
            'etudiant_id' => $this->etudiantId,
            'status' => ESBTPDocumentApproval::STATUS_APPROVED,
            'requested_by' => $this->service->id,
            'approved_by' => $this->responsable->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Une souscription soldee par un versement qui DESIGNE le frais : un
     * versement sans frais designe tombe dans le pool global et n'eteint
     * aucune tranche rattachee a une categorie.
     */
    private function soldeATaJour(int $montant): void
    {
        $categorie = ESBTPFraisCategory::factory()->create([
            'is_mandatory' => true,
            'is_active' => true,
            'default_amount' => $montant,
        ]);

        ESBTPFraisSubscription::factory()->create([
            'inscription_id' => $this->inscription->id,
            'frais_category_id' => $categorie->id,
            'amount' => $montant,
            'is_active' => true,
            'satisfied_in_kind' => false,
            'created_by' => $this->responsable->id,
        ]);

        ESBTPPaiement::factory()->pour($this->inscription)->surCategorie((int) $categorie->id)
            ->create(['montant' => $montant]);
    }
}
