<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPResultat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * La suppression des moyennes sans note est un pouvoir de destruction de
 * donnees academiques : elle exige `bulletins.delete`, pas seulement le droit
 * de generer. Et la garde « plus aucune note » est reappliquee sur la
 * suppression elle-meme : une note revenue entre l'affichage et le clic rend
 * la ligne legitime, elle doit survivre.
 */
class SuppressionMoyennesSansNoteTest extends TestCase
{
    use MonteUneClasseBts, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->monterLaClasse();
        // Le groupe de routes exige aussi un niveau d acces global : les
        // deux acteurs le portent, seul `bulletins.delete` les distingue.
        Permission::findOrCreate('admin.access', 'web');
        Permission::findOrCreate('bulletins.delete', 'web');
        Permission::findOrCreate('bulletins.generate', 'web');

        // Sans un superAdmin en base, EnsureInstalled considere l application
        // non installee et redirige TOUT vers /install.
        $superAdmin = \Spatie\Permission\Models\Role::findOrCreate('superAdmin', 'web');
        User::factory()->create(['must_change_password' => false, 'password_changed_at' => now()])
            ->assignRole($superAdmin);
    }

    private function moyenneSansNote(int $etudiantId, int $matiereId, float $moyenne = 12): ESBTPResultat
    {
        return ESBTPResultat::create([
            'etudiant_id' => $etudiantId,
            'classe_id' => $this->classe->id,
            'matiere_id' => $matiereId,
            'periode' => 'semestre1',
            'annee_universitaire_id' => $this->annee->id,
            'moyenne' => $moyenne,
            'coefficient' => 2,
        ]);
    }

    private function demander(User $acteur, int $matiereId)
    {
        $reponse = $this->actingAs($acteur)->deleteJson(route('esbtp.bulletins.moyennes-sans-note.destroy'), [
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'matiere_id' => $matiereId,
        ]);

        if (! in_array($reponse->status(), [200, 403], true)) {
            fwrite(STDERR, 'STATUT '.$reponse->status().' : '.substr((string) $reponse->getContent(), 0, 300).PHP_EOL);
        }

        return $reponse;
    }

    public function test_generer_ne_suffit_pas_pour_supprimer(): void
    {
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();
        $this->moyenneSansNote((int) $etudiant->id, (int) $matiere->id);

        // password_changed_at recent : sans lui, ForcePasswordChange redirige
        // tout en 302 vers le changement de mot de passe.
        $generateur = User::factory()->create(["must_change_password" => false, "password_changed_at" => now()]);
        $generateur->givePermissionTo(['admin.access', 'bulletins.generate']);

        $this->demander($generateur, (int) $matiere->id)->assertForbidden();
        $this->assertDatabaseHas('esbtp_resultats', [
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'deleted_at' => null,
        ]);
    }

    public function test_supprime_puis_ne_trouve_plus_rien(): void
    {
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();
        $this->moyenneSansNote((int) $etudiant->id, (int) $matiere->id);

        $habilite = User::factory()->create(["must_change_password" => false, "password_changed_at" => now()]);
        $habilite->givePermissionTo(['admin.access', 'bulletins.delete']);

        $this->demander($habilite, (int) $matiere->id)
            ->assertOk()
            ->assertJsonPath('supprimees', 1);

        // La detection ne recompte pas la ligne supprimee : sans quoi l'ecran
        // redemanderait sans fin la meme suppression.
        $restantes = ESBTPResultat::query()
            ->sansNoteSurLaPeriode((int) $this->classe->id, (int) $this->annee->id, ['semestre1'])
            ->count();
        $this->assertSame(0, $restantes);

        // Un second clic ne detruit rien et le dit.
        $this->demander($habilite, (int) $matiere->id)
            ->assertOk()
            ->assertJsonPath('supprimees', 0);
    }

    public function test_une_note_revenue_rend_la_ligne_intouchable(): void
    {
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();
        $this->moyenneSansNote((int) $etudiant->id, (int) $matiere->id);

        // La note revient AVANT le clic : la ligne redevient legitime.
        $this->noter($etudiant, $this->evaluationDe($matiere));

        $habilite = User::factory()->create(["must_change_password" => false, "password_changed_at" => now()]);
        $habilite->givePermissionTo(['admin.access', 'bulletins.delete']);

        $this->demander($habilite, (int) $matiere->id)
            ->assertOk()
            ->assertJsonPath('supprimees', 0);

        $this->assertDatabaseHas('esbtp_resultats', [
            'etudiant_id' => $etudiant->id,
            'matiere_id' => $matiere->id,
            'deleted_at' => null,
        ]);
    }
}
