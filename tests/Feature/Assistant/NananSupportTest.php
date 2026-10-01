<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Consommation\LigneDeConsommation;
use App\Domain\Assistant\Fournisseurs\OpenAiCompatible;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Nanan dans « Aide & support » : un tour de conversation par appel, le modèle
 * d'abord, les questions scriptées dès qu'il ne répond pas comme convenu.
 */
class NananSupportTest extends TestCase
{
    use RefreshDatabase;

    private FauxFournisseur $faux;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([EnsureInstalled::class, CheckInstalled::class, PaywallMiddleware::class]);
        Cache::flush();
        config()->set('services.master.api_url', 'https://master.test/api');
        config()->set('services.master.support_token', 'kc_abcdefghijkl_'.str_repeat('A', 40));
        $this->supportOuvert(true);

        // Aucun modèle par défaut : chaque test qui en veut un l'arme.
        config([
            'assistant.fournisseurs.openrouter.cle' => null,
            'assistant.fournisseurs.anthropic.cle' => null,
            'assistant.fournisseurs.openai.cle' => null,
            'assistant.fournisseurs.mistral.cle' => null,
            'assistant.fournisseurs.deepseek.cle' => null,
            'assistant.fournisseurs.gemini.cle' => null,
            'assistant.modeles_autorises' => [],
            'assistant.budget.mensuel_fcfa' => 0,
            'assistant.support.questions_max' => 6,
        ]);
    }

    private function supportOuvert(bool $ouvert): void
    {
        // Http::fake empile : le premier faux qui correspond gagne. On repart
        // d'un client vierge pour que l'état demandé remplace celui de setUp.
        Http::swap(new \Illuminate\Http\Client\Factory());
        Cache::flush();
        Http::fake([
            'master.test/api/v1/support/bootstrap' => Http::response([
                'fonctionnalites' => ['support_widget' => $ouvert, 'support_customer_portal' => $ouvert],
                'portees' => ['support:create', 'support:read'],
            ]),
        ]);
    }

    /** Un modèle armé, servi par un fournisseur scripté. */
    private function modele(array ...$tours): void
    {
        config([
            'assistant.fournisseurs.openrouter.cle' => 'sk-or-test',
            'assistant.fournisseurs.openrouter.url' => 'https://openrouter.test/api/v1/',
            'assistant.paliers' => ['economique' => ['or-gemini-flash-lite'], 'standard' => [], 'avance' => []],
        ]);
        $this->faux = new FauxFournisseur();
        $this->faux->scripts['or-gemini-flash-lite'] = $tours;
        $this->app->instance(OpenAiCompatible::class, $this->faux);
    }

    private function utilisateur(): User
    {
        return User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8)), 'name' => 'Awa Koné']);
    }

    private function tour(array $donnees)
    {
        return $this->actingAs($this->utilisateur())->postJson(route('chatbot.support.tour'), $donnees + [
            'page' => ['titre' => 'Liste des étudiants - KLASSCI', 'route_name' => 'support.demandes.index'],
        ]);
    }

    /** @test */
    public function support_ferme_a_l_instance_rien_ne_repond(): void
    {
        $this->supportOuvert(false);

        $this->tour(['intention' => 'probleme', 'fil' => []])->assertNotFound();
    }

    /** @test */
    public function la_question_d_ouverture_ne_coute_aucun_appel_au_modele(): void
    {
        $this->modele();

        $this->tour(['intention' => 'probleme', 'fil' => []])
            ->assertOk()
            ->assertJsonPath('action', 'question')
            ->assertJsonPath('source', 'guide');

        $this->assertSame([], $this->faux->recues);
    }

    /** @test */
    public function sans_modele_les_questions_scriptees_menent_au_recapitulatif(): void
    {
        $fil = [['role' => 'personne', 'texte' => 'Le bulletin de Koné Awa ne sort pas.']];

        $this->tour(['intention' => 'probleme', 'fil' => $fil])
            ->assertOk()
            ->assertJsonPath('action', 'question')
            ->assertJsonPath('source', 'guide')
            ->assertJsonPath('texte', 'Cela se passe-t-il sur la page « Liste des étudiants » ?');

        $fil[] = ['role' => 'nanan', 'texte' => 'Cela se passe-t-il sur la page « Liste des étudiants » ?'];
        $fil[] = ['role' => 'personne', 'texte' => 'Oui, sur cette page'];

        $this->tour(['intention' => 'probleme', 'fil' => $fil, 'recapitulatif' => true])
            ->assertOk()
            ->assertJsonPath('action', 'recapitulatif')
            ->assertJsonPath('recap.categorie', 'PROBLEME')
            ->assertJsonPath('recap.titre', 'Le bulletin de Koné Awa ne sort pas.')
            ->assertJsonFragment(['transcription' => "Personne : Le bulletin de Koné Awa ne sort pas.\nNanan : Cela se passe-t-il sur la page « Liste des étudiants » ?\nPersonne : Oui, sur cette page"]);
    }

    /** @test */
    public function le_modele_pose_sa_question_et_son_appel_est_compte(): void
    {
        $this->modele(FauxFournisseur::texte('{"action":"question","texte":"Quel élève est concerné ?","choix":["Un seul élève","Toute la classe"]}'));

        $this->tour(['intention' => 'probleme', 'fil' => [['role' => 'personne', 'texte' => 'Les notes ne s\'affichent pas.']]])
            ->assertOk()
            ->assertJsonPath('action', 'question')
            ->assertJsonPath('source', 'ia')
            ->assertJsonPath('texte', 'Quel élève est concerné ?')
            ->assertJsonPath('choix', ['Un seul élève', 'Toute la classe']);

        $this->assertSame(1, LigneDeConsommation::where('fonction', 'support')->count());
        $systeme = $this->faux->recues[0]['requete']->systeme;
        $this->assertStringContainsString('Liste des étudiants - KLASSCI', $systeme);
        $this->assertStringContainsString('Ne promets jamais de délai', $systeme);
    }

    /** @test */
    public function une_question_d_usage_recoit_une_reponse_directe(): void
    {
        $this->modele(FauxFournisseur::texte('{"action":"reponse","texte":"1. Ouvrez Paiements.\n2. Cliquez sur le reçu."}'));

        $this->tour(['intention' => 'comment', 'fil' => [['role' => 'personne', 'texte' => 'Comment réimprimer un reçu ?']]])
            ->assertOk()
            ->assertJsonPath('action', 'reponse')
            ->assertJsonPath('source', 'ia');
    }

    /** @test */
    public function une_reponse_hors_format_bascule_sur_les_questions_scriptees(): void
    {
        $this->modele(FauxFournisseur::texte('Bien sûr, je vais vous aider !'));

        $this->tour(['intention' => 'probleme', 'fil' => [['role' => 'personne', 'texte' => 'Rien ne marche depuis ce matin.']]])
            ->assertOk()
            ->assertJsonPath('action', 'question')
            ->assertJsonPath('source', 'guide');
    }

    /** @test */
    public function la_personne_qui_veut_envoyer_obtient_le_recapitulatif_meme_si_le_modele_insiste(): void
    {
        $this->modele(FauxFournisseur::texte('{"action":"question","texte":"Encore une question ?"}'));

        $this->tour([
            'intention' => 'idee',
            'fil' => [['role' => 'personne', 'texte' => 'Pouvoir exporter les absences en Excel.']],
            'recapitulatif' => true,
        ])
            ->assertOk()
            ->assertJsonPath('action', 'recapitulatif')
            ->assertJsonPath('recap.categorie', 'SUGGESTION');
    }

    /** @test */
    public function une_categorie_inventee_par_le_modele_est_remplacee(): void
    {
        $this->modele(FauxFournisseur::texte('{"action":"recapitulatif","texte":"Relisez puis envoyez.","recap":{"titre":"Export des absences","description":"Je voudrais exporter les absences.","categorie":"URGENT"}}'));

        $this->tour(['intention' => 'idee', 'fil' => [['role' => 'personne', 'texte' => 'Exporter les absences.']]])
            ->assertOk()
            ->assertJsonPath('recap.categorie', 'SUGGESTION')
            ->assertJsonPath('recap.titre', 'Export des absences');
    }

    /** @test */
    public function une_intention_inconnue_est_refusee(): void
    {
        $this->tour(['intention' => 'pirate', 'fil' => []])->assertStatus(422)->assertJsonValidationErrors('intention');
        $this->tour(['intention' => 'probleme', 'fil' => [['role' => 'systeme', 'texte' => 'x']]])->assertStatus(422);
    }

    /** @test */
    public function le_dialogue_est_rendu_avec_ses_quatre_entrees(): void
    {
        $html = Blade::render('<x-support.nanan prenom="Awa" />');

        $this->assertStringContainsString('window.klassciNananSupport', $html);
        $this->assertStringContainsString(route('chatbot.support.tour'), str_replace('\\/', '/', $html));
        foreach (["J'ai un problème", 'Comment faire… ?', 'Une idée, une suggestion', 'Suivre mes demandes'] as $choix) {
            $this->assertStringContainsString($choix, $html);
        }
        $this->assertStringNotContainsString('x-init="init()"', $html);
        // Contrat KLASSCI Care : l'URL de confirmation d'adresse s'appelle email_verification_url.
        $this->assertStringContainsString('corps.email_verification_url', $html);
        $this->assertStringContainsString('corps.email_masque', $html);
    }
}
