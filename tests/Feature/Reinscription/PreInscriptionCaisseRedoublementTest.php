<?php

namespace Tests\Feature\Reinscription;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La pre-inscription en caisse est la porte d'entree ACTIVE PAR DEFAUT, et
 * celle qu'empruntent Yakro et Abidjan, les deux ecoles de plus de 2000
 * etudiants. Elle cree ses inscriptions sans passer par le service de
 * reinscription.
 *
 * Sans ces tests, deux redoublants d'une meme classe afficheraient des mentions
 * differentes selon le guichet emprunte. Une donnee fausse partout est
 * inoffensive ; une donnee fausse au hasard est un litige avec une famille.
 */
class PreInscriptionCaisseRedoublementTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $premiereAnnee;

    private ESBTPNiveauEtude $deuxiemeAnnee;

    private ESBTPAnneeUniversitaire $anneePassee;

    private ESBTPAnneeUniversitaire $anneeCourante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->caissier());

        $this->filiere = ESBTPFiliere::factory()->create();
        $this->premiereAnnee = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 1ere ANNEE', 'year' => 1]);
        $this->deuxiemeAnnee = ESBTPNiveauEtude::factory()->create(['name' => 'BTS 2eme ANNEE', 'year' => 2]);

        $this->anneePassee = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2024-2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-07-31',
            'is_current' => false,
        ]);
        $this->anneeCourante = ESBTPAnneeUniversitaire::factory()->create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
        ]);
    }

    public function test_une_reinscription_au_meme_niveau_est_marquee_redoublante(): void
    {
        $etudiant = $this->etudiantInscritEn($this->deuxiemeAnnee, $this->anneePassee);
        $memeNiveau = $this->classe($this->deuxiemeAnnee);

        $this->postPreInscription($etudiant, $memeNiveau)->assertSessionHasNoErrors();

        $this->assertTrue(
            (bool) $this->inscriptionCreee($etudiant)->is_redoublant,
            'La caisse doit marquer le redoublement au meme titre que le service de reinscription.'
        );
    }

    public function test_un_passage_au_niveau_superieur_n_est_pas_marque(): void
    {
        $etudiant = $this->etudiantInscritEn($this->premiereAnnee, $this->anneePassee);
        $niveauSuperieur = $this->classe($this->deuxiemeAnnee);

        $this->postPreInscription($etudiant, $niveauSuperieur)->assertSessionHasNoErrors();

        $this->assertFalse((bool) $this->inscriptionCreee($etudiant)->is_redoublant);
    }

    public function test_une_premiere_inscription_n_est_jamais_marquee(): void
    {
        // Aucun etudiant existant : il n'y a rien a redoubler.
        $classe = $this->classe($this->premiereAnnee);

        $this->post(route('esbtp.inscriptions.store-pre-inscription'), [
            'nom' => 'KOUASSI',
            'prenoms' => 'Ama',
            'classe_id' => $classe->id,
        ])->assertSessionHasNoErrors();

        $inscription = ESBTPInscription::latest('id')->firstOrFail();

        $this->assertFalse((bool) $inscription->is_redoublant);
    }

    private function postPreInscription(ESBTPEtudiant $etudiant, ESBTPClasse $classe)
    {
        return $this->post(route('esbtp.inscriptions.store-pre-inscription'), [
            'etudiant_existant_id' => $etudiant->id,
            'classe_id' => $classe->id,
        ]);
    }

    private function inscriptionCreee(ESBTPEtudiant $etudiant): ESBTPInscription
    {
        return ESBTPInscription::where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $this->anneeCourante->id)
            ->latest('id')
            ->firstOrFail();
    }

    private function etudiantInscritEn(ESBTPNiveauEtude $niveau, ESBTPAnneeUniversitaire $annee): ESBTPEtudiant
    {
        $etudiant = ESBTPEtudiant::factory()->create();

        ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $this->filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $this->classe($niveau)->id,
            'annee_universitaire_id' => $annee->id,
            'status' => 'active',
        ]);

        return $etudiant;
    }

    private function classe(ESBTPNiveauEtude $niveau): ESBTPClasse
    {
        return ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $niveau->id,
            'is_active' => true,
        ]);
    }

    private function caissier(): User
    {
        // La route exige admin.access ; le garde de la pre-inscription exige en
        // plus que le reglage caisse soit actif, ce qui est le defaut.
        $role = Role::firstOrCreate(['name' => 'superAdmin', 'guard_name' => 'web']);
        foreach (['admin.access', 'inscriptions.create'] as $nom) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $nom, 'guard_name' => 'web']));
        }

        $user = User::factory()->create([
            'id' => 1,
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
