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

    /**
     * LA MATRICE COMPLETE DE `liaisons`, parce que deux versions s'y sont
     * trompees en raisonnant au lieu de mesurer.
     *
     * `present|array` semble laisser passer une chaine vide : `Array` n'est pas
     * une regle implicite, donc `Validator::presentOrRuleIsImplicit()` la saute
     * quand la valeur est `''`. Un garde a donc ete ajoute deux fois pour
     * rattraper ce cas — et il n'a jamais pu s'executer, parce que le
     * raisonnement oubliait `ConvertEmptyStringsToNull` : le middleware change
     * `''` en `null` AVANT la validation, `null` n'est pas une chaine, la regle
     * reprend la main et refuse.
     *
     * CE TEST EXISTE POUR QUE L'ABSENCE DE GARDE SOIT SURE. Il gele les sept
     * entrees et la frontiere qui compte : seule la liste vide passe, et elle
     * retire tout. Si un jour le middleware bouge ou que la regle change, c'est
     * ici que ca se voit — pas en production, sur un effacement silencieux.
     *
     * @dataProvider entreesDeLiaisons
     */
    public function test_seule_une_liste_franchit_la_validation_des_liaisons(
        array $charge,
        int $statutAttendu,
        int $liaisonsRestantes,
        string $pourquoi
    ): void {
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
            'Temoin de montage : sans liaison de depart, ce test ne prouve rien.'
        );

        $reponse = $this->actingAs($this->unSuperAdmin())
            ->postJson("/esbtp/matieres/{$matiere->id}/update-liaisons", $charge);

        $reponse->assertStatus($statutAttendu);

        $this->assertSame(
            $liaisonsRestantes,
            ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->count(),
            $pourquoi
        );

        // Un refus de forme n'est pas une panne : il ne doit jamais se
        // presenter comme telle. C'est ce que le `catch (\Exception)` de la
        // methode aurait fait d'un `abort()`.
        if ($statutAttendu === 422) {
            $this->assertStringNotContainsString(
                'Erreur lors de la sauvegarde',
                (string) $reponse->getContent()
            );
        }
    }

    /** @return array<string, array{0: array<string, mixed>, 1: int, 2: int, 3: string}> */
    public static function entreesDeLiaisons(): array
    {
        return [
            'chaine vide'     => [['liaisons' => ''], 422, 1, 'Une chaine vide ne doit RIEN effacer.'],
            'null explicite'  => [['liaisons' => null], 422, 1, 'Un nul ne doit RIEN effacer.'],
            'chaine non vide' => [['liaisons' => 'x'], 422, 1, 'Une chaine ne doit RIEN effacer.'],
            'entier'          => [['liaisons' => 3], 422, 1, 'Un entier ne doit RIEN effacer.'],
            'booleen'         => [['liaisons' => true], 422, 1, 'Un booleen ne doit RIEN effacer.'],
            'cle absente'     => [[], 422, 1, 'Une requete malformee ne doit RIEN effacer.'],
            'liste vide'      => [['liaisons' => []], 200, 0, 'Le vide EST une instruction : tout retirer.'],
        ];
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

    /**
     * LA FICHE D'UNE MATIERE N'ECRIT PAS LA MAQUETTE — ET C'EST UNE CORRECTION.
     *
     * Ce test garde le RETRAIT d'un comportement, pas son ajout : la premiere
     * version du correctif « cocher une filiere rattache vraiment » posait,
     * depuis `update()`, le PRODUIT CARTESIEN des deux listes du formulaire
     * dans la table que lit le bulletin.
     *
     * Le piege est que les cases y arrivent PRECOCHEES depuis les deux pivots
     * plats, dont le produit sur-rapporte. Une matiere en filieres [A, B] et
     * niveaux [1, 2] dont la maquette ne porte que (A,1) gagnait donc (A,2),
     * (B,1) et (B,2) au premier enregistrement venu — trois apparitions au
     * bulletin de classes que personne n'a nommees, et aucun moyen de revenir
     * en arriere depuis cet ecran, puisque deux listes ne decrivent pas un
     * ensemble de couples qui n'est pas un rectangle plein.
     *
     * Le controle a rejouer avant de toucher `update()` : remettre l'appel a
     * `poserLesCouplesDuFormulaire()`. Ce test doit passer de 1 a 4.
     */
    public function test_enregistrer_la_fiche_d_une_matiere_n_ajoute_rien_a_la_maquette(): void
    {
        $matiere = ESBTPMatiere::factory()->create([
            'unite_enseignement_id' => null,
            'is_active' => true,
        ]);

        $niveauUn = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $niveauDeux = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $filiereA = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $filiereB = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        // La maquette ne porte QU'UN couple : (A, 1).
        ESBTPMatiereFilierNiveau::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $filiereA->id,
            'niveau_etude_id' => $niveauUn->id,
        ]);

        // Les deux pivots plats, eux, en portent deux chacun : c'est ce que
        // l'ecran precoche, et son produit vaut quatre couples.
        $matiere->filieres()->sync([$filiereA->id, $filiereB->id]);
        $matiere->niveaux()->sync([$niveauUn->id, $niveauDeux->id]);

        $reponse = $this->actingAs($this->unSuperAdmin())
            ->put("/esbtp/matieres/{$matiere->id}", [
                'name' => $matiere->name,
                'code' => $matiere->code,
                'coefficient' => 1,
                'is_active' => 1,
                'type_formation' => 'generale',
                // Exactement ce que le formulaire renvoie quand personne ne
                // touche a rien : les cases telles qu'elles etaient cochees.
                'liaisons_presentes' => 1,
                'filieres' => [$filiereA->id, $filiereB->id],
                'niveaux' => [$niveauUn->id, $niveauDeux->id],
            ]);

        $reponse->assertRedirect(route('esbtp.matieres.index'));

        $this->assertSame(
            1,
            ESBTPMatiereFilierNiveau::where('matiere_id', $matiere->id)->count(),
            "Enregistrer la fiche ne doit RIEN ajouter a la maquette : le produit "
            ."des deux listes precochees vaut quatre couples, la maquette n'en porte qu'un.",
        );

        // Et le couple d'origine est intact : on ne retire rien non plus.
        $this->assertDatabaseHas('esbtp_matiere_filiere_niveau', [
            'matiere_id' => $matiere->id,
            'filiere_id' => $filiereA->id,
            'niveau_etude_id' => $niveauUn->id,
        ]);
    }

    /**
     * Les deux pivots plats, eux, suivent bien les cases — c'est leur travail.
     *
     * Le test precedent pourrait passer pour une raison creuse : « la fiche
     * n'enregistre plus rien du tout ». Celui-ci le refute. Les deux listes
     * portent `coefficient` et `heures_cours`, elles ont un role propre, et le
     * vrai defaut d'origine — le formulaire envoyait `filieres[]`, le code ne
     * lisait que `filiere_id`, donc enregistrer DETACHAIT tout — reste corrige.
     */
    public function test_enregistrer_la_fiche_synchronise_bien_les_deux_listes(): void
    {
        $matiere = ESBTPMatiere::factory()->create([
            'unite_enseignement_id' => null,
            'is_active' => true,
        ]);

        $niveau = ESBTPNiveauEtude::factory()->create(['type' => 'BTS']);
        $ancienne = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);
        $nouvelle = ESBTPFiliere::factory()->create(['is_tronc_commun' => false, 'parent_id' => null]);

        $matiere->filieres()->sync([$ancienne->id]);
        $matiere->niveaux()->sync([$niveau->id]);

        $this->actingAs($this->unSuperAdmin())
            ->put("/esbtp/matieres/{$matiere->id}", [
                'name' => $matiere->name,
                'code' => $matiere->code,
                'coefficient' => 1,
                'is_active' => 1,
                'type_formation' => 'generale',
                'liaisons_presentes' => 1,
                'filieres' => [$nouvelle->id],
                'niveaux' => [$niveau->id],
            ])
            ->assertRedirect(route('esbtp.matieres.index'));

        $this->assertSame(
            [$nouvelle->id],
            $matiere->fresh()->filieres->pluck('id')->all(),
            'La liste des filieres doit suivre les cases cochees.',
        );
    }
}
