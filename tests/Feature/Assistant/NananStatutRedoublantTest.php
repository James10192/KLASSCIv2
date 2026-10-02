<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Inscriptions\EtablirStatutRedoublant;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Inscriptions\StatutRedoublant;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\Chatbot\Tools\SearchInscriptionsTool;
use App\Services\Inscriptions\NormalisationTypeInscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Nanan confirme ou corrige le statut redoublant : elle constate avec
 * search_inscriptions, propose, et rien n'est écrit avant « Valider ». Une
 * correction exige le motif de la personne.
 */
class NananStatutRedoublantTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private int $annee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class, \App\Http\Middleware\ForcePasswordChange::class]);
        Role::findOrCreate('superAdmin', 'web');
        foreach ([StatutRedoublant::PERMISSION, 'inscriptions.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = $this->utilisateur();
        $this->admin->assignRole('superAdmin');

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        $this->annee = (int) ESBTPAnneeUniversitaire::factory()->create(['is_current' => true])->id;
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

    private function reinscription(ESBTPClasse $classe, array $attributs = []): ESBTPInscription
    {
        return ESBTPInscription::factory()->create($attributs + [
            'classe_id' => $classe->id, 'annee_universitaire_id' => $this->annee,
            'type_inscription' => NormalisationTypeInscription::REINSCRIPTION,
            'is_redoublant' => false, 'redoublant_source' => StatutRedoublant::SOURCE_DEDUIT,
            'etudiant_id' => ESBTPEtudiant::factory()->create(['matricule' => 'RD'.Str::upper(Str::random(8))])->id,
        ]);
    }

    public function test_confirmer_une_classe_n_ecrit_rien_avant_valider(): void
    {
        $classe = $this->classe('RD_CONF_1A');
        $a = $this->reinscription($classe);
        $b = $this->reinscription($classe);
        $nouveau = $this->reinscription($classe, ['type_inscription' => 'première_inscription', 'redoublant_source' => null]);

        $r = app(EtablirStatutRedoublant::class)->executeAuthorized(['classe' => 'rd_conf_1a'], $this->admin);
        $this->assertSame(StatutRedoublant::SOURCE_DEDUIT, $a->fresh()->redoublant_source);

        $this->valider($r);
        foreach ([$a, $b] as $i) {
            $i = $i->fresh();
            $this->assertSame(StatutRedoublant::SOURCE_CONFIRME, $i->redoublant_source);
            $this->assertFalse((bool) $i->is_redoublant);
            $this->assertSame($this->admin->id, (int) $i->redoublant_confirme_par);
        }
        $this->assertNull($nouveau->fresh()->redoublant_source, 'un nouvel étudiant n’attend aucune confirmation');
    }

    public function test_corriger_exige_le_motif_de_la_personne(): void
    {
        $i = $this->reinscription($this->classe('RD_CORR_1A'));
        $action = app(EtablirStatutRedoublant::class);

        $this->refuse($action->executeAuthorized(['matricules' => [$i->etudiant->matricule], 'valeur' => true], $this->admin), 'motif');
        $this->refuse($action->executeAuthorized(['matricules' => [$i->etudiant->matricule], 'valeur' => true, 'motif' => 'court'], $this->admin), 'motif');
        $this->refuse($action->executeAuthorized(['classe' => 'RD_CORR_1A', 'valeur' => true, 'motif' => 'Redouble selon le conseil'], $this->admin), 'un par un');
        $this->assertFalse((bool) $i->fresh()->is_redoublant);

        $r = $action->executeAuthorized(['matricules' => [$i->etudiant->matricule], 'valeur' => true, 'motif' => 'Redouble selon le conseil de classe'], $this->admin);
        $this->assertFalse((bool) $i->fresh()->is_redoublant, 'rien n’est écrit avant « Valider »');
        $this->valider($r);

        $i = $i->fresh();
        $this->assertTrue((bool) $i->is_redoublant);
        $this->assertSame(StatutRedoublant::SOURCE_CORRIGE, $i->redoublant_source);
        $this->assertSame('Redouble selon le conseil de classe', $i->redoublant_motif);
    }

    public function test_un_statut_change_entre_proposition_et_validation_perime_la_proposition(): void
    {
        $i = $this->reinscription($this->classe('RD_PER_1A'));
        $r = app(EtablirStatutRedoublant::class)->executeAuthorized(['inscriptions' => [$i->id]], $this->admin);

        app(StatutRedoublant::class)->etablir($i->fresh('anneeUniversitaire'), $this->admin, true, 'Corrigé depuis la fiche entre-temps');
        $this->valider($r, null, 'perimee');
        $this->assertSame(StatutRedoublant::SOURCE_CORRIGE, $i->fresh()->redoublant_source);
    }

    public function test_un_statut_deja_confirme_a_la_meme_valeur_est_sans_objet(): void
    {
        $i = $this->reinscription($this->classe('RD_DEJA_1A'), ['redoublant_source' => StatutRedoublant::SOURCE_CONFIRME]);
        $r = app(EtablirStatutRedoublant::class)->executeAuthorized(['inscriptions' => [$i->id]], $this->admin);

        $this->assertArrayNotHasKey('widget', $r);
    }

    public function test_sans_le_droit_l_action_n_est_pas_offerte(): void
    {
        $lecteur = $this->utilisateur();
        $lecteur->givePermissionTo('inscriptions.view');
        $noms = array_column(app(CatalogueOutils::class)->schemas($lecteur), 'nom');
        $this->assertContains('search_inscriptions', $noms);
        $this->assertNotContains('proposer_statut_redoublant', $noms);
        $this->assertArrayHasKey('error', app(EtablirStatutRedoublant::class)->executeAuthorized(['classe' => 'X'], $lecteur));

        $this->assertContains('proposer_statut_redoublant', array_column(app(CatalogueOutils::class)->schemas($this->admin), 'nom'));
    }

    public function test_la_validation_reverifie_le_droit(): void
    {
        $agent = $this->utilisateur();
        $agent->givePermissionTo(StatutRedoublant::PERMISSION);
        $i = $this->reinscription($this->classe('RD_DROIT_1A'));
        $r = app(EtablirStatutRedoublant::class)->executeAuthorized(['inscriptions' => [$i->id]], $agent);

        $agent->revokePermissionTo(StatutRedoublant::PERMISSION);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($agent)->postJson($r['widget']['valider_url'], ['jeton' => $r['widget']['jeton']])
            ->assertStatus(409)->assertJsonMissing(['statut' => 'executee']);
        $this->assertSame(StatutRedoublant::SOURCE_DEDUIT, $i->fresh()->redoublant_source);
    }

    public function test_search_inscriptions_filtre_et_montre_le_statut_redoublant(): void
    {
        $classe = $this->classe('RD_LIRE_1A');
        $attente = $this->reinscription($classe);
        $confirme = $this->reinscription($classe, ['is_redoublant' => true, 'redoublant_source' => StatutRedoublant::SOURCE_CONFIRME]);

        $outil = app(SearchInscriptionsTool::class);
        $r = $outil->execute(['classe' => 'RD LIRE 1A', 'redoublant' => 'a_confirmer', 'annee_courante' => true], $this->admin);
        $this->assertSame([$attente->id], array_column($r['results'], 'id'));
        $this->assertSame('a_confirmer', $r['results'][0]['statut_redoublant']);
        $this->assertSame('Non redoublant', $r['results'][0]['redoublant']);

        $r = $outil->execute(['matricule' => $confirme->etudiant->matricule, 'redoublant' => 'oui'], $this->admin);
        $this->assertSame([$confirme->id], array_column($r['results'], 'id'));
        $this->assertSame('confirme', $r['results'][0]['statut_redoublant']);
    }

    /**
     * La vraie boucle, le vrai catalogue, le vrai prompt : sans motif, l'outil
     * renvoie la question au modèle ; avec, il propose ; rien n'est écrit.
     */
    public function test_seance_d_entrainement_corriger_un_statut(): void
    {
        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 4, 'assistant.limites.budget_tokens' => 0]);
        $i = $this->reinscription($this->classe('RD_SE_1A'));
        $matricule = $i->etudiant->matricule;
        $faux->scripts['m'] = [
            FauxFournisseur::outil('t1', 'proposer_statut_redoublant', ['matricules' => [$matricule], 'valeur' => true]),
            FauxFournisseur::outil('t2', 'proposer_statut_redoublant', ['matricules' => [$matricule], 'valeur' => true, 'motif' => 'Redouble selon le conseil de classe']),
            FauxFournisseur::texte('Je propose de passer cet élève en redoublant : relisez puis validez.'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        $this->assertStringContainsString('proposer_statut_redoublant', $systeme);

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => $matricule.' redouble, corrige son statut']], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        $this->assertStringContainsString('motif', json_encode($faux->recues[1]['requete']->messages, JSON_UNESCAPED_UNICODE));
        $this->assertSame(['proposer_statut_redoublant'], array_values(array_unique(array_column($r->appels, 'tool'))));
        $this->assertFalse((bool) $i->fresh()->is_redoublant);
    }
}
