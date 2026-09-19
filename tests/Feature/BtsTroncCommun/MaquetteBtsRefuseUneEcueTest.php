<?php

namespace Tests\Feature\BtsTroncCommun;

use App\Domain\BtsTroncCommun\LiaisonsDeMatiere;
use App\Domain\BtsTroncCommun\ResolutionDeMatiere;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * La maquette BTS refuse une ECUE à l'ENTRÉE, et l'accepte à la SORTIE.
 *
 * L'asymétrie est le sujet même de ce test, et elle n'est pas un oubli.
 *
 * Le chantier qui a découvert la fuite a d'abord filtré chez les lecteurs :
 * douze filtres dans neuf fichiers, trouvés en trois passes de revue, et il en
 * restait encore. Filtrer en lecture demande à chaque futur écran de s'en
 * souvenir. Refuser à l'écriture ne le demande qu'une fois — c'est ce que garde
 * le premier test.
 *
 * Mais refuser des deux côtés est précisément ce qui avait rendu le défaut
 * incorrigible : `TPOH243` × (TRAVAUX_PUBLICS, 2A) était listée par le CLI,
 * masquée par l'écran de classification, et refusée au retrait. Visible, et
 * retirable par rien. C'est ce que garde le second test.
 *
 * @see \App\Domain\BtsTroncCommun\LiaisonsDeMatiere::poser()
 * @see \App\Domain\BtsTroncCommun\ResolutionDeMatiere::matiere()
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
 */
class MaquetteBtsRefuseUneEcueTest extends TestCase
{
    use RefreshDatabase;

    private function unSuperAdmin(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');

        return $user;
    }

    private function ecue(string $nom = 'Alimentation en eau et QTE'): ESBTPMatiere
    {
        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE Ouvrages hydrauliques',
            'code' => 'UE-TPOH24-'.uniqid(),
        ]);

        return ESBTPMatiere::factory()->create([
            'name' => $nom,
            'code' => 'TPOH243',
            'is_active' => true,
            'unite_enseignement_id' => $ue->id,
        ]);
    }

    public function test_poser_refuse_une_ecue_et_n_ecrit_rien(): void
    {
        $ecue = $this->ecue();
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        $this->expectException(\InvalidArgumentException::class);

        try {
            app(LiaisonsDeMatiere::class)->poser((int) $ecue->id, (int) $filiere->id, (int) $niveau->id);
        } finally {
            // Le refus doit être TOTAL : une ligne canonique écrite puis
            // l'exception levée laisserait exactement la ligne qu'on refuse.
            $this->assertSame(0, ESBTPMatiereFilierNiveau::query()
                ->where('matiere_id', $ecue->id)
                ->count());
        }
    }

    public function test_poser_accepte_une_matiere_bts(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['name' => 'Topographie', 'is_active' => true]);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        app(LiaisonsDeMatiere::class)->poser((int) $matiere->id, (int) $filiere->id, (int) $niveau->id);

        $this->assertSame(1, ESBTPMatiereFilierNiveau::query()
            ->where('matiere_id', $matiere->id)
            ->where('filiere_id', $filiere->id)
            ->where('niveau_etude_id', $niveau->id)
            ->count());
    }

    public function test_la_resolution_refuse_une_ecue_au_chargement_et_l_accepte_au_retrait(): void
    {
        $ecue = $this->ecue();
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        $resolution = app(ResolutionDeMatiere::class);

        // Chargement : elle n'existe pas, pour que rien ne l'écrive.
        $auChargement = $resolution->matiere(
            ['id' => (int) $ecue->id],
            (int) $filiere->id,
            (int) $niveau->id,
        );
        $this->assertSame('introuvable', $auChargement['statut']);

        // Retrait : elle existe, pour qu'on puisse l'enlever.
        $auRetrait = $resolution->matierePourRetrait(
            ['id' => (int) $ecue->id],
            (int) $filiere->id,
            (int) $niveau->id,
        );
        $this->assertSame('ok', $auRetrait['statut']);
        $this->assertSame((int) $ecue->id, (int) $auRetrait['matiere']->id);
    }

    /**
     * Le garde à l'ajout ne doit pas se transformer en effacement de masse.
     *
     * Une première version vidait la liste voulue quand la matière était une
     * ECUE. Le diff qui suit lit « aucun couple voulu » comme « retire-les
     * tous » : l'appel effaçait TOUTES les lignes de la matière, journalisait
     * « Ajout refusé » pour une suppression, et répondait succès. Le chemin
     * réellement atteignable — le bouton « Retirer de la classe », qui renvoie
     * les liaisons restantes — transformait donc le retrait d'un couple en
     * effacement de tous les autres.
     */
    public function test_refuser_un_ajout_sur_une_ecue_n_efface_pas_ses_autres_couples(): void
    {
        $ecue = $this->ecue();
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $garde = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $nouveau = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        // Deux lignes deja posees par erreur, avant que le garde n'existe.
        foreach ([$garde, $nouveau] as $f) {
            ESBTPMatiereFilierNiveau::create([
                'matiere_id' => $ecue->id,
                'filiere_id' => $f->id,
                'niveau_etude_id' => $niveau->id,
            ]);
        }

        $reponse = $this->actingAs($this->unSuperAdmin())
            ->postJson("/esbtp/matieres/{$ecue->id}/update-liaisons", [
                'liaisons' => [
                    // Celui-ci existe deja : ce n'est pas un ajout.
                    ['filiere_id' => $garde->id, 'niveau_id' => $niveau->id],
                    // Celui-la n'existe pas : c'est un ajout, et il est refuse.
                    ['filiere_id' => ESBTPFiliere::factory()->create([
                        'is_tronc_commun' => false, 'parent_id' => null,
                    ])->id, 'niveau_id' => $niveau->id],
                ],
            ]);

        $reponse->assertStatus(422);

        // Et surtout : RIEN n'a ete efface.
        $this->assertSame(2, ESBTPMatiereFilierNiveau::where('matiere_id', $ecue->id)->count());
    }

    /**
     * « TOUT RETIRER » EST UNE ACTION DE L'ECRAN, PAS UNE REQUETE MALFORMEE.
     *
     * L'utilisateur decoche toutes les combinaisons, l'ecran ouvre une
     * confirmation qui annonce « Cela supprimera toutes les liaisons
     * existantes », il confirme — et le front poste `liaisons: []`.
     *
     * Avec `required|array`, cet envoi rendait 422 « Le champ liaisons est
     * obligatoire » : une capacite que l'interface annonce, confirme et
     * documente etait devenue inatteignable, et le message de succes du cas
     * « zero liaison » etait du code mort. `present|array` exige la cle sans
     * exiger son contenu.
     */
    public function test_tout_retirer_est_accepte_et_efface_bien_tout(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        $this->assertSame(
            1,
            ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->count(),
            'Temoin : sans une liaison a retirer, ce test ne prouve rien.'
        );

        $reponse = $this->actingAs($this->unSuperAdmin())
            ->postJson("/esbtp/matieres/{$matiere->id}/update-liaisons", [
                'liaisons' => [],
            ]);

        $reponse->assertOk();

        $this->assertSame(
            0,
            ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->count(),
            'Le vide EST une instruction : tout retirer.'
        );
    }

    /**
     * La cle ABSENTE, elle, reste refusee.
     *
     * C'est la distinction que `present` preserve et que `required` ecrasait :
     * une requete malformee ne doit pas etre lue comme « retire-les tous ».
     */
    public function test_une_requete_qui_omet_la_cle_est_refusee(): void
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
        ]);

        $reponse = $this->actingAs($this->unSuperAdmin())
            ->postJson("/esbtp/matieres/{$matiere->id}/update-liaisons", []);

        $reponse->assertStatus(422);

        $this->assertSame(
            1,
            ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->count(),
            'Une requete malformee ne doit RIEN effacer.'
        );
    }

    public function test_retirer_un_couple_d_une_ecue_passe_et_ne_touche_que_lui(): void
    {
        $ecue = $this->ecue();
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $garde = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $retire = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        foreach ([$garde, $retire] as $f) {
            ESBTPMatiereFilierNiveau::create([
                'matiere_id' => $ecue->id,
                'filiere_id' => $f->id,
                'niveau_etude_id' => $niveau->id,
            ]);
        }

        // Ce que renvoie « Retirer de la classe » : les liaisons RESTANTES.
        $this->actingAs($this->unSuperAdmin())
            ->postJson("/esbtp/matieres/{$ecue->id}/update-liaisons", [
                'liaisons' => [['filiere_id' => $garde->id, 'niveau_id' => $niveau->id]],
            ])
            ->assertOk();

        $restantes = ESBTPMatiereFilierNiveau::where('matiere_id', $ecue->id)->get();
        $this->assertCount(1, $restantes);
        $this->assertSame((int) $garde->id, (int) $restantes->first()->filiere_id);
    }

    public function test_le_retrait_par_libelle_retrouve_aussi_une_ecue(): void
    {
        $ecue = $this->ecue();
        $filiere = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);

        // Le garde n'existait d'abord que sur le libellé, et l'identifiant
        // passait à côté ; en levant la garde pour le retrait, il faut la lever
        // des DEUX côtés, sinon le retrait par nom reste impossible.
        $resolue = app(ResolutionDeMatiere::class)->matierePourRetrait(
            $ecue->name,
            (int) $filiere->id,
            (int) $niveau->id,
        );

        $this->assertSame('ok', $resolue['statut']);
        $this->assertSame((int) $ecue->id, (int) $resolue['matiere']->id);
    }
}
