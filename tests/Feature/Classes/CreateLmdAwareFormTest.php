<?php

namespace Tests\Feature\Classes;

use App\Http\Requests\Classe\StoreClasseRequest;
use App\Http\Requests\Classe\UpdateClasseRequest;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests Feature pour le formulaire LMD-aware de creation/edition de classe.
 *
 * Couvre :
 *  1. BTS create avec filiere uniquement
 *  2. LMD create avec mention uniquement (tronc commun mention)
 *  3. LMD create avec mention + parcours coherent
 *  4. LMD create avec parcours dont mention ne matche pas filiere_id (rejet 422)
 *  5. LMD create sans mention ni parcours (rejet 422)
 *  6. Snapshot DOM : data-mode="bts"/"lmd" present sur <form> selon niveau choisi
 *
 * Pattern : les tests qui peuvent fonctionner avec Validator::make() seul (sans
 * acces DB) y restent. Les tests qui exigent vraiment l'integration POST -> DB
 * utilisent RefreshDatabase si la DB de test est disponible.
 */
class CreateLmdAwareFormTest extends TestCase
{
    use RefreshDatabase;

    private function createBtsNiveau(): ESBTPNiveauEtude
    {
        return ESBTPNiveauEtude::create([
            'name' => '1ere annee BTS',
            'code' => 'BTS1',
            'type' => 'BTS',
            'year' => 1,
            'is_active' => true,
        ]);
    }

    private function createLmdNiveau(string $name = 'Licence 1', string $type = 'Licence', int $year = 1): ESBTPNiveauEtude
    {
        return ESBTPNiveauEtude::create([
            'name' => $name,
            'code' => 'L' . $year,
            'type' => $type,
            'year' => $year,
            'is_active' => true,
        ]);
    }

    private function authenticateAsAdmin(): User
    {
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        Auth::login($user);
        return $user;
    }

    public function test_bts_create_with_filiere_only_succeeds(): void
    {
        $this->authenticateAsAdmin();
        $niveau = $this->createBtsNiveau();
        $filiere = ESBTPFiliere::create(['name' => 'Genie Civil', 'code' => 'GC', 'is_active' => true]);
        $annee = ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        $response = $this->post(route('esbtp.classes.store'), [
            'name' => 'BTS GC1',
            'code' => 'BTS-GC1-TEST',
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'places_totales' => 30,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('esbtp.classes.index'));
        $this->assertDatabaseHas('esbtp_classes', [
            'code' => 'BTS-GC1-TEST',
            'filiere_id' => $filiere->id,
            'systeme_academique' => 'BTS',
        ]);
    }

    public function test_lmd_create_with_mention_only_succeeds_as_tronc_commun(): void
    {
        $this->authenticateAsAdmin();
        $niveau = $this->createLmdNiveau();
        $domaine = ESBTPLMDDomaine::create(['name' => 'Droit', 'code' => 'DRT', 'is_active' => true]);
        // En mode LMD, filiere_id sert de mention. On utilise un ESBTPFiliere pour valider exists.
        // (Convention Option A : filiere_id est le slot "mention" en LMD)
        $filiere = ESBTPFiliere::create(['name' => 'Droit Prive', 'code' => 'DPR', 'is_active' => true]);
        $mention = ESBTPLMDMention::create([
            'name' => 'Droit',
            'code' => 'DRT',
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);
        // Lier mention <-> filiere via id manuellement pour ce test : on utilise filiere->id comme mention slot
        $annee = ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        $response = $this->post(route('esbtp.classes.store'), [
            'name' => 'L1 Droit',
            'code' => 'L1-DRT-TEST',
            'filiere_id' => $filiere->id, // sert de mention en LMD
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'places_totales' => 50,
            'is_active' => 1,
            // pas de parcours_id : tronc commun mention
        ]);

        $response->assertRedirect(route('esbtp.classes.index'));
        $this->assertDatabaseHas('esbtp_classes', [
            'code' => 'L1-DRT-TEST',
            'systeme_academique' => 'LMD',
        ]);
    }

    public function test_lmd_create_with_mention_and_parcours_succeeds(): void
    {
        $this->authenticateAsAdmin();
        $niveau = $this->createLmdNiveau('Licence 2', 'Licence', 2);
        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SCI', 'is_active' => true]);
        $filiere = ESBTPFiliere::create(['name' => 'Biologie', 'code' => 'BIO', 'is_active' => true]);
        $mention = ESBTPLMDMention::create([
            'name' => 'Sciences de la Vie',
            'code' => 'SVT',
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);
        $parcours = ESBTPLMDParcours::create([
            'name' => 'Biologie Moleculaire',
            'code' => 'BM',
            'mention_id' => $mention->id,
            'filiere_id' => $filiere->id,
            'is_active' => true,
        ]);
        $annee = ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        $response = $this->post(route('esbtp.classes.store'), [
            'name' => 'L2 Bio Mol',
            'code' => 'L2-BIO-MOL-TEST',
            // filiere_id = mention_id en LMD via picker
            'filiere_id' => $mention->id,
            'parcours_id' => $parcours->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'places_totales' => 40,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('esbtp.classes.index'));
        // filiere_id final doit etre derive de parcours->filiere_id (controller logic L325-329)
        $this->assertDatabaseHas('esbtp_classes', [
            'code' => 'L2-BIO-MOL-TEST',
            'parcours_id' => $parcours->id,
            'filiere_id' => $filiere->id, // derive depuis parcours
            'systeme_academique' => 'LMD',
        ]);
    }

    public function test_lmd_create_rejects_parcours_with_mismatched_mention(): void
    {
        $this->authenticateAsAdmin();
        $niveau = $this->createLmdNiveau();
        $domaine = ESBTPLMDDomaine::create(['name' => 'Lettres', 'code' => 'LET', 'is_active' => true]);
        $filiere = ESBTPFiliere::create(['name' => 'Lettres Modernes', 'code' => 'LM', 'is_active' => true]);
        $mentionA = ESBTPLMDMention::create([
            'name' => 'Lettres',
            'code' => 'LET',
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);
        $mentionB = ESBTPLMDMention::create([
            'name' => 'Philosophie',
            'code' => 'PHI',
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);
        $parcoursOfB = ESBTPLMDParcours::create([
            'name' => 'Philo Ethique',
            'code' => 'PHE',
            'mention_id' => $mentionB->id,
            'filiere_id' => $filiere->id,
            'is_active' => true,
        ]);
        $annee = ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        // Mismatch : mention A but parcours appartient a mention B
        $response = $this->post(route('esbtp.classes.store'), [
            'name' => 'L1 Mismatch',
            'code' => 'L1-MISMATCH-TEST',
            'filiere_id' => $mentionA->id,
            'parcours_id' => $parcoursOfB->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'places_totales' => 30,
            'is_active' => 1,
        ]);

        $response->assertStatus(302); // redirect back with errors
        $response->assertSessionHasErrors('parcours_id');
    }

    public function test_lmd_create_without_mention_or_parcours_rejected(): void
    {
        $this->authenticateAsAdmin();
        $niveau = $this->createLmdNiveau();
        $annee = ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        $response = $this->post(route('esbtp.classes.store'), [
            'name' => 'L1 Sans Mention',
            'code' => 'L1-NOMENTION-TEST',
            // ni filiere_id ni parcours_id en mode LMD
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
            'places_totales' => 30,
            'is_active' => 1,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('filiere_id');
    }

    public function test_form_renders_data_mode_marker_based_on_old_input(): void
    {
        $this->authenticateAsAdmin();
        $btsNiveau = $this->createBtsNiveau();
        $lmdNiveau = $this->createLmdNiveau();
        ESBTPAnneeUniversitaire::create([
            'name' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'is_active' => true,
            'is_current' => true,
        ]);

        // Render create form sans pre-selection : data-mode="unknown"
        $response = $this->get(route('esbtp.classes.create'));
        $response->assertStatus(200);
        $response->assertSee('data-mode="unknown"', false);

        // Old() input : niveau LMD pre-rempli via withSession
        $response = $this->withSession(['_old_input' => ['niveau_etude_id' => (string) $lmdNiveau->id]])
            ->get(route('esbtp.classes.create'));
        $response->assertStatus(200);
        $response->assertSee('data-mode="lmd"', false);
        // Le label "Mode LMD activé" doit etre present (rendu SSR)
        $response->assertSee('Mode LMD activé', false);

        // Old() input : niveau BTS pre-rempli
        $response = $this->withSession(['_old_input' => ['niveau_etude_id' => (string) $btsNiveau->id]])
            ->get(route('esbtp.classes.create'));
        $response->assertStatus(200);
        $response->assertSee('data-mode="bts"', false);
        $response->assertSee('Mode BTS', false);
    }
}
