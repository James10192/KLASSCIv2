<?php

namespace Tests\Unit\Domain\Assistant;

use App\Domain\Assistant\Fournisseurs\Anthropic;
use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\Gemini;
use App\Domain\Assistant\Fournisseurs\LecteurSse;
use App\Domain\Assistant\Fournisseurs\OpenAiCompatible;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Modeles\ModeleIa;
use Illuminate\Http\Client\Request as RequeteHttp;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Chaque adaptateur, rejoué sur un flux enregistré : requête envoyée et
 * événements normalisés rendus. Aucun appel réseau (Http::fake).
 */
class AdaptateursTest extends TestCase
{
    private function modele(string $adaptateur, string $url, ?string $cle = 'cle-test'): ModeleIa
    {
        return new ModeleIa('m', $adaptateur, $adaptateur, 'modele-x', 'Modèle X', true, true, $cle, $url);
    }

    private function requete(): RequeteModele
    {
        return new RequeteModele(
            systeme: 'Système',
            messages: [
                ['role' => 'user', 'texte' => 'Classes ?'],
                ['role' => 'assistant', 'texte' => '', 'appels' => [['id' => 'a1', 'nom' => 'search_classes', 'arguments' => []]]],
                ['role' => 'outil', 'id' => 'a1', 'nom' => 'search_classes', 'resultat' => '{"count":0}'],
                ['role' => 'user', 'texte' => 'Et en BTS ?'],
            ],
            outils: [[
                'nom' => 'search_classes',
                'description' => 'Rechercher des classes',
                'parametres' => ['type' => 'object', 'properties' => ['search' => ['type' => 'string', 'default' => 'x']], 'additionalProperties' => false],
            ]],
        );
    }

    /** @return array<int, array{0: string, 1: array}> */
    private function evenements(iterable $flux): array
    {
        $sortie = [];
        foreach ($flux as $e) {
            $this->assertInstanceOf(EvenementModele::class, $e);
            $sortie[] = [$e->type, $e->donnees];
        }
        return $sortie;
    }

    public function test_anthropic_traduit_texte_outil_usage_et_fin(): void
    {
        Http::fake(['api.anthropic.test/*' => Http::response(FluxEnregistres::anthropicTexteEtOutil(), 200, ['Content-Type' => 'text/event-stream'])]);

        $evts = $this->evenements((new Anthropic())->diffuser($this->requete(), $this->modele('anthropic', 'https://api.anthropic.test/v1/'), fn () => false));

        $this->assertSame([
            ['texte', ['delta' => 'Je cherche ']],
            ['texte', ['delta' => 'les inscriptions.']],
            ['outil_debut', ['id' => 'toolu_01', 'nom' => 'search_inscriptions']],
            ['outil', ['id' => 'toolu_01', 'nom' => 'search_inscriptions', 'arguments' => ['search' => 'KONAN', 'limit' => 5]]],
            ['usage', ['entree' => 112, 'sortie' => 42]],
            ['fin', ['raison' => 'outils']],
        ], $evts);

        Http::assertSent(function (RequeteHttp $r) {
            $corps = $r->data();
            $this->assertSame('https://api.anthropic.test/v1/messages', $r->url());
            $this->assertTrue($corps['stream']);
            $this->assertSame('ephemeral', $corps['system'][0]['cache_control']['type']);
            $this->assertSame('search_classes', $corps['tools'][0]['name']);
            $this->assertArrayNotHasKey('default', $corps['tools'][0]['input_schema']['properties']['search']);
            // Le résultat d'outil repart en bloc tool_result dans un message utilisateur.
            $this->assertSame('tool_result', $corps['messages'][2]['content'][0]['type']);
            $this->assertSame('a1', $corps['messages'][2]['content'][0]['tool_use_id']);
            $this->assertSame('cle-test', $r->header('x-api-key')[0]);
            return true;
        });
    }

    public function test_openai_compatible_recompose_les_arguments_et_suit_l_usage(): void
    {
        Http::fake(['api.mistral.test/*' => Http::response(FluxEnregistres::openAiTexteEtOutil(), 200, ['Content-Type' => 'text/event-stream'])]);

        $evts = $this->evenements((new OpenAiCompatible())->diffuser($this->requete(), $this->modele('openai', 'https://api.mistral.test/v1/'), fn () => false));

        $this->assertSame([
            ['texte', ['delta' => 'Je cherche ']],
            ['texte', ['delta' => 'les classes.']],
            ['outil_debut', ['id' => 'call_1', 'nom' => 'search_classes']],
            ['outil', ['id' => 'call_1', 'nom' => 'search_classes', 'arguments' => ['search' => 'BTS']]],
            ['usage', ['entree' => 120, 'sortie' => 18]],
            ['fin', ['raison' => 'outils']],
        ], $evts);

        Http::assertSent(function (RequeteHttp $r) {
            $corps = $r->data();
            $this->assertSame('https://api.mistral.test/v1/chat/completions', $r->url());
            $this->assertSame('Bearer cle-test', $r->header('Authorization')[0]);
            $this->assertSame('system', $corps['messages'][0]['role']);
            $this->assertSame('tool', $corps['messages'][3]['role']);
            $this->assertSame('a1', $corps['messages'][3]['tool_call_id']);
            $this->assertSame('{}', $corps['messages'][2]['tool_calls'][0]['function']['arguments']);
            $this->assertSame('function', $corps['tools'][0]['type']);
            $this->assertTrue($corps['stream_options']['include_usage']);
            return true;
        });
    }

    public function test_gemini_donne_un_identifiant_aux_appels_et_renvoie_le_nom_avec_la_reponse(): void
    {
        Http::fake(['gemini.test/*' => Http::response(FluxEnregistres::geminiTexteEtOutil(), 200, ['Content-Type' => 'text/event-stream'])]);

        $evts = $this->evenements((new Gemini())->diffuser($this->requete(), $this->modele('gemini', 'https://gemini.test/v1beta/'), fn () => false));

        $this->assertSame([
            ['texte', ['delta' => 'Voici ']],
            ['outil_debut', ['id' => 'gemini_1', 'nom' => 'search_classes']],
            ['outil', ['id' => 'gemini_1', 'nom' => 'search_classes', 'arguments' => ['search' => 'BTS']]],
            ['usage', ['entree' => 50, 'sortie' => 9]],
            ['fin', ['raison' => 'outils']],
        ], $evts);

        Http::assertSent(function (RequeteHttp $r) {
            $corps = $r->data();
            $this->assertSame('https://gemini.test/v1beta/models/modele-x:streamGenerateContent?alt=sse', $r->url());
            $this->assertSame('cle-test', $r->header('x-goog-api-key')[0]);
            $this->assertSame('model', $corps['contents'][1]['role']);
            $this->assertSame('search_classes', $corps['contents'][2]['parts'][0]['functionResponse']['name']);
            $this->assertArrayNotHasKey('additionalProperties', $corps['tools'][0]['functionDeclarations'][0]['parameters']);
            return true;
        });
    }

    public function test_le_dernier_tour_interdit_les_appels_sans_retirer_les_outils(): void
    {
        $requete = $this->requete()->pourConclure();

        $openai = app(\App\Domain\Assistant\Fournisseurs\OpenAiCompatible::class)->corps($requete, $this->modele('openai', 'https://x.test/'));
        $this->assertSame('none', $openai['tool_choice']);
        $this->assertNotEmpty($openai['tools']);

        $anthropic = app(\App\Domain\Assistant\Fournisseurs\Anthropic::class)->corps($requete, $this->modele('anthropic', 'https://x.test/'));
        $this->assertSame(['type' => 'none'], $anthropic['tool_choice']);
        $this->assertNotEmpty($anthropic['tools']);

        $gemini = app(\App\Domain\Assistant\Fournisseurs\Gemini::class)->corps($requete, $this->modele('gemini', 'https://x.test/'));
        $this->assertSame('NONE', $gemini['toolConfig']['functionCallingConfig']['mode']);

        $normal = app(\App\Domain\Assistant\Fournisseurs\OpenAiCompatible::class)->corps($this->requete(), $this->modele('openai', 'https://x.test/'));
        $this->assertSame('auto', $normal['tool_choice']);
    }

    public function test_une_panne_http_devient_un_evenement_erreur_sans_exception(): void
    {
        config(['assistant.limites.pause_ms' => 0]);
        Http::fake(['*' => Http::response(['error' => ['type' => 'overloaded_error', 'message' => 'détail interne']], 529)]);

        foreach ([new Anthropic(), new OpenAiCompatible(), new Gemini()] as $adaptateur) {
            $evts = $this->evenements($adaptateur->diffuser($this->requete(), $this->modele('x', 'https://x.test/'), fn () => false));
            $this->assertSame([['erreur', ['code' => 'http_529']]], $evts);
        }
    }

    public function test_une_surcharge_passagere_se_retente_sur_le_meme_modele(): void
    {
        config(['assistant.limites.pause_ms' => 0, 'assistant.limites.tentatives' => 3]);
        Http::fakeSequence()
            ->push(['error' => ['type' => 'overloaded_error']], 529)
            ->push(['error' => ['type' => 'rate_limit_error']], 429)
            ->push(FluxEnregistres::anthropicTexte(), 200, ['Content-Type' => 'text/event-stream']);

        $evts = $this->evenements((new Anthropic())->diffuser($this->requete(), $this->modele('anthropic', 'https://api.anthropic.test/'), fn () => false));

        $this->assertNotContains('erreur', array_column($evts, 0));
        $this->assertContains('texte', array_column($evts, 0));
        Http::assertSentCount(3);
    }

    public function test_une_erreur_de_requete_ne_se_retente_pas(): void
    {
        config(['assistant.limites.pause_ms' => 0, 'assistant.limites.tentatives' => 3]);
        Http::fake(['*' => Http::response(['error' => ['type' => 'invalid_request_error']], 400)]);

        $evts = $this->evenements((new Anthropic())->diffuser($this->requete(), $this->modele('anthropic', 'https://api.anthropic.test/'), fn () => false));

        $this->assertSame([['erreur', ['code' => 'http_400']]], $evts);
        Http::assertSentCount(1);
    }

    public function test_sans_cle_rien_n_est_envoye(): void
    {
        Http::fake();

        $evts = $this->evenements((new OpenAiCompatible())->diffuser($this->requete(), $this->modele('openai', 'https://x.test/', null), fn () => false));

        $this->assertSame([['erreur', ['code' => 'non_configure']]], $evts);
        Http::assertNothingSent();
    }

    public function test_flux_coupe_avant_la_fin_devient_une_erreur(): void
    {
        $coupe = substr(FluxEnregistres::anthropicTexte(), 0, strpos(FluxEnregistres::anthropicTexte(), 'event: message_delta'));
        $lecteur = new LecteurSse();
        $sse = array_merge($lecteur->alimenter($coupe), $lecteur->vider());

        $evts = $this->evenements((new Anthropic())->traduire($sse));

        $this->assertSame(['erreur', ['code' => 'flux_interrompu']], end($evts));
    }

    public function test_evenement_error_anthropic_en_cours_de_flux(): void
    {
        $lecteur = new LecteurSse();
        $sse = $lecteur->alimenter("event: error\ndata: {\"type\":\"error\",\"error\":{\"type\":\"overloaded_error\",\"message\":\"Overloaded\"}}\n\n");

        $this->assertSame([['erreur', ['code' => 'flux_overloaded_error']]], $this->evenements((new Anthropic())->traduire($sse)));
    }

    public function test_le_lecteur_sse_supporte_des_morceaux_arbitraires(): void
    {
        foreach ([1, 7, 64] as $taille) {
            $lecteur = new LecteurSse();
            $evenements = [];
            foreach (str_split(FluxEnregistres::openAiTexteEtOutil(), $taille) as $morceau) {
                $evenements = array_merge($evenements, $lecteur->alimenter($morceau));
            }
            $evenements = array_merge($evenements, $lecteur->vider());

            // 8 objets JSON ; le [DONE] final n'est pas du JSON et est ignoré.
            $this->assertCount(8, $evenements, "morceaux de {$taille} octets");
        }
    }
}
