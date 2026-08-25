<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La corbeille est ce qui evite que le portail public ne soit qu'un formulaire
 * de contact deguise : la scolarite convertit une demande en vraie
 * reinscription, sans re-saisie, par le flux canonique.
 *
 * Les comptes de test portent un role `secretaire` reel, et surtout PAS
 * `superAdmin` : `Gate::before` rendrait tous les controles de permission
 * passants, et `effectuerReinscription` court-circuite le controle de solde
 * pour qui detient `admin.access`. Tester en superAdmin reviendrait a ne
 * tester aucun des deux garde-fous.
 */
class CorbeilleDemandesTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPEtudiant $etudiant;

    private ESBTPClasse $classeCible;

    private ESBTPAnneeUniversitaire $anneeCible;

    private ESBTPReinscriptionDemande $demande;

    protected function setUp(): void
    {
        parent::setUp();

        // CheckInstalled, middleware global, redirige vers l'assistant tant
        // qu'aucun superAdmin n'existe. Il en faut donc un en base — mais ce
        // n'est PAS lui qui agit : voir le commentaire de classe.
        User::factory()->create()
            ->assignRole(Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']));

        $this->actingAs($this->compteAvec(['reinscriptions.demandes.view', 'reinscriptions.demandes.process']));
        Cache::flush();

        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        $anneePassee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => false,
        ]);
        $this->anneeCible = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-31', 'is_current' => true,
        ]);

        $classePrecedente = ESBTPClasse::factory()->create([
            'name' => 'BTS1 A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true,
        ]);
        $this->classeCible = ESBTPClasse::factory()->create([
            'name' => 'BTS2 A', 'filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'is_active' => true,
        ]);

        $this->etudiant = ESBTPEtudiant::factory()->create(['matricule' => 'DEMO-0002']);

        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classePrecedente->id,
            'annee_universitaire_id' => $anneePassee->id,
            'status' => 'active',
        ]);

        $this->demande = ESBTPReinscriptionDemande::create([
            'etudiant_id' => $this->etudiant->id,
            'annee_universitaire_id' => $this->anneeCible->id,
            'classe_souhaitee_id' => $classePrecedente->id,
            'statut' => ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
            'consentement_at' => now(),
        ]);
    }

    public function test_la_corbeille_liste_les_demandes(): void
    {
        $this->get(route('esbtp.reinscription-demandes.index'))
            ->assertOk()
            ->assertSee('DEMO-0002');
    }

    public function test_convertir_cree_une_vraie_inscription(): void
    {
        $avant = $this->inscriptionsSurAnneeCible();

        $this->convertir()->assertSessionHasNoErrors();

        $this->assertSame(
            $avant + 1,
            $this->inscriptionsSurAnneeCible(),
            'La conversion doit produire une inscription reelle, via le flux canonique.'
        );

        $this->demande->refresh();
        $this->assertSame(ESBTPReinscriptionDemande::STATUT_CONVERTIE, $this->demande->statut);
        $this->assertNotNull($this->demande->traite_at);
        $this->assertNotNull($this->demande->inscription_id);
    }

    public function test_une_demande_ne_se_convertit_qu_une_fois(): void
    {
        // Rejouer une conversion creerait une seconde inscription, donc une
        // seconde facturation pour la meme famille.
        $this->convertir();
        $apresPremiere = $this->inscriptionsSurAnneeCible();

        $this->convertir()->assertSessionHas('error');

        $this->assertSame($apresPremiere, $this->inscriptionsSurAnneeCible());
    }

    public function test_un_etudiant_deja_inscrit_sur_l_annee_cible_ne_se_convertit_pas(): void
    {
        // Cas reel : l'ecole a inscrit l'etudiant au guichet entre le depot de
        // la demande et son traitement. Convertir malgre tout lui vaudrait deux
        // jeux de frais pour la meme annee.
        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'classe_id' => $this->classeCible->id,
            'annee_universitaire_id' => $this->anneeCible->id,
            'status' => 'active',
        ]);
        $avant = $this->inscriptionsSurAnneeCible();

        $this->convertir()->assertSessionHas('error');

        $this->assertSame($avant, $this->inscriptionsSurAnneeCible());
        $this->assertSame(
            ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
            $this->demande->refresh()->statut,
            'La demande reste ouverte : la scolarite doit pouvoir la rejeter en connaissance de cause.'
        );
    }

    public function test_une_demande_visant_une_annee_revolue_ne_se_convertit_pas(): void
    {
        // Une demande porte l'annee qui etait courante au moment du depot.
        // Si l'ecole a bascule depuis, la convertir telle quelle reinscrirait
        // l'etudiant dans une annee terminee.
        $this->anneeCible->update(['is_current' => false]);

        $this->convertir()->assertSessionHas('error');

        $this->assertSame(0, $this->inscriptionsSurAnneeCible());
        $this->assertSame(
            ESBTPReinscriptionDemande::STATUT_EN_ATTENTE,
            $this->demande->refresh()->statut,
            'La demande reste ouverte : la scolarite doit pouvoir la rejeter en connaissance de cause.'
        );
    }

    public function test_un_rejet_sans_motif_est_refuse(): void
    {
        $this->post(route('esbtp.reinscription-demandes.rejeter', $this->demande), [
            'motif_rejet' => 'non',
        ])->assertSessionHasErrors('motif_rejet');

        $this->assertSame(ESBTPReinscriptionDemande::STATUT_EN_ATTENTE, $this->demande->refresh()->statut);
    }

    public function test_un_rejet_motive_cloture_la_demande(): void
    {
        $this->post(route('esbtp.reinscription-demandes.rejeter', $this->demande), [
            'motif_rejet' => 'Dossier incomplet : acte de naissance manquant.',
        ])->assertSessionHasNoErrors();

        $this->demande->refresh();
        $this->assertSame(ESBTPReinscriptionDemande::STATUT_REJETEE, $this->demande->statut);
        $this->assertStringContainsString('acte de naissance', $this->demande->motif_rejet);
    }

    public function test_consulter_et_traiter_sont_deux_droits_distincts(): void
    {
        // Un agent qui peut lire la corbeille ne doit pas pouvoir engager
        // l'ecole en creant une inscription.
        $this->actingAs($this->compteAvec(['reinscriptions.demandes.view']));

        $this->get(route('esbtp.reinscription-demandes.index'))->assertOk();

        $this->convertir()->assertForbidden();
        $this->assertSame(0, $this->inscriptionsSurAnneeCible());
    }

    public function test_sans_permission_la_corbeille_est_fermee(): void
    {
        $this->actingAs($this->compteAvec([]));

        $this->get(route('esbtp.reinscription-demandes.index'))->assertForbidden();
    }

    private function convertir()
    {
        return $this->post(route('esbtp.reinscription-demandes.convertir', $this->demande), [
            'classe_id' => $this->classeCible->id,
            'decision' => 'passage',
        ]);
    }

    private function inscriptionsSurAnneeCible(): int
    {
        return ESBTPInscription::where('annee_universitaire_id', $this->anneeCible->id)->count();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function compteAvec(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'secretaire-'.md5(implode(',', $permissions)), 'guard_name' => 'web']);

        foreach ($permissions as $nom) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']));
        }

        $user = User::factory()->create([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
