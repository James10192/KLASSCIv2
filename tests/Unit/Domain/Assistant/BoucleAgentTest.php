<?php

namespace Tests\Unit\Domain\Assistant;

use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\FilDeReponse;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Modeles\RegistreDesModeles;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Services\Chatbot\ChatbotSetupGuideService;
use App\Services\Chatbot\Tools\ChatbotTool;
use Mockery;
use Tests\TestCase;

/**
 * Boucle d'agent avec un fournisseur scripté : aucun réseau, aucune base.
 */
class BoucleAgentTest extends TestCase
{
    private FauxFournisseur $faux;

    /** @var string[] */
    private array $frames = [];

    private OutilEspion $permis;

    private OutilEspion $interdit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $this->faux);
        config([
            'assistant.adaptateurs.faux' => FauxFournisseur::class,
            'assistant.limites.tours' => 4,
            'assistant.limites.budget_tokens' => 0,
            'assistant.limites.delai_secondes' => 60,
            'chatbot.tools.outil_permis' => ['enabled' => true, 'any_permissions' => ['classes.view'], 'libelle' => 'Recherche des classes…'],
            'chatbot.tools.outil_interdit' => ['enabled' => true, 'any_permissions' => ['paiements.view'], 'libelle' => 'Recherche des paiements…'],
        ]);

        $this->permis = new OutilEspion('outil_permis');
        $this->interdit = new OutilEspion('outil_interdit');
    }

    private function modele(string $cle): ModeleIa
    {
        return new ModeleIa($cle, 'faux', 'faux', $cle, ucfirst($cle), true, true, 'cle', 'https://faux.test/');
    }

    private function utilisateur()
    {
        $user = Mockery::mock();
        $user->shouldReceive('can')->with('classes.view')->andReturnTrue();
        $user->shouldReceive('can')->with('paiements.view')->andReturnFalse();
        return $user;
    }

    private function boucle(): array
    {
        $catalogue = new CatalogueOutils(Mockery::mock(ChatbotSetupGuideService::class), [$this->permis, $this->interdit]);
        $ui = new UiMessageStream(function (string $frame) {
            $this->frames[] = $frame;
        });

        return [new BoucleAgent($catalogue), $catalogue, $ui];
    }

    private function parts(): array
    {
        return array_map(fn ($f) => json_decode(substr(trim($f), 6), true), $this->frames);
    }

    private function types(): array
    {
        return array_column($this->parts(), 'type');
    }

    public function test_reponse_texte_en_un_tour(): void
    {
        $this->faux->scripts['a'] = [FauxFournisseur::texte('Bonjour !')];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();

        $r = $boucle->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Salut']], $catalogue->schemas($user)), $user, $ui);

        $this->assertSame('ok', $r->statut);
        $this->assertSame('Bonjour !', $r->texte);
        $this->assertSame('a', $r->modele);
        $this->assertSame(['start-step', 'text-start', 'text-delta', 'text-end', 'finish-step'], $this->types());
        $this->assertSame([10, 5, 1], [$r->tokensEntree, $r->tokensSortie, $r->tours]);
    }

    public function test_aller_retour_d_outil(): void
    {
        $this->faux->scripts['a'] = [
            FauxFournisseur::outil('t1', 'outil_permis', ['search' => 'BTS'], 'Je regarde.'),
            FauxFournisseur::texte('Voici les classes.'),
        ];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();
        $vus = [];

        $r = $boucle->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Classes ?']], $catalogue->schemas($user)), $user, $ui,
            function ($nom, $args, $res) use (&$vus) { $vus[] = [$nom, $args, $res['count']]; });

        $this->assertTrue($this->permis->execute);
        $this->assertSame([['outil_permis', ['search' => 'BTS'], 1]], $vus);
        $this->assertSame("Je regarde.\n\nVoici les classes.", $r->texte);
        $this->assertSame('Voici les classes.', $r->texteDernierTour);
        $this->assertSame(2, $r->tours);
        $this->assertSame([['tool' => 'outil_permis', 'args' => ['search' => 'BTS'], 'result_count' => 1]], $r->appels);

        // Le second tour reçoit l'appel et son résultat, au format neutre.
        $second = $this->faux->recues[1]['requete']->messages;
        $this->assertSame(['role' => 'assistant', 'texte' => 'Je regarde.', 'appels' => [['id' => 't1', 'nom' => 'outil_permis', 'arguments' => ['search' => 'BTS']]]], $second[1]);
        $this->assertSame('outil', $second[2]['role']);
        $this->assertSame('t1', $second[2]['id']);

        $outils = array_values(array_filter($this->parts(), fn ($p) => $p['type'] === 'data-etape'));
        $this->assertSame(['en_cours', 'termine'], array_column(array_column($outils, 'data'), 'etat'));
        $this->assertSame('Recherche des classes…', $outils[0]['data']['libelle']);
        $this->assertSame(['t1', 't1'], array_column($outils, 'id'));
        $this->assertSame(2, count(array_keys($this->types(), 'start-step')));
    }

    public function test_widget_sous_son_etape_et_resultat_compact_pour_le_modele(): void
    {
        $this->faux->scripts['a'] = [
            FauxFournisseur::outil('t1', 'outil_permis', ['search' => 'BTS'], 'Je regarde.'),
            FauxFournisseur::texte('Voici les classes.'),
        ];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();
        $fil = new FilDeReponse();

        $r = $boucle->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Classes ?']], $catalogue->schemas($user)), $user, $ui,
            fn ($nom, $args, $res) => ['kind' => 'table', 'rows' => $res['results']], $fil);

        // L'étape terminée porte un résumé, puis son widget arrive, au même identifiant.
        $parts = $this->parts();
        $fin = collect($parts)->where('type', 'data-etape')->last();
        $this->assertSame('termine', $fin['data']['etat']);
        $this->assertSame('1 résultat', $fin['data']['resume']);
        $this->assertSame('search : BTS', $fin['data']['detail']);
        $widget = collect($parts)->firstWhere('type', 'data-widget');
        $this->assertSame('t1', $widget['id']);
        $this->assertSame('table', $widget['data']['kind']);
        $this->assertLessThan(array_search($widget, $parts, true), array_search($fin, $parts, true));

        // Le modèle ne reçoit qu'un résumé, avec la consigne de ne pas recopier le widget.
        $pourModele = json_decode($this->faux->recues[1]['requete']->messages[2]['resultat'], true);
        $this->assertSame(1, $pourModele['nombre']);
        $this->assertSame([['nom' => 'BTS 1']], $pourModele['elements']);
        $this->assertStringContainsString('widget', $pourModele['affichage']);

        // Le fil garde l'ordre d'affichage, et la trace rejouera l'appel dans l'historique.
        $this->assertSame(['texte', 'etape', 'widget', 'texte'], array_column($fil->toArray(), 'type'));
        $this->assertSame(['assistant', 'outil'], array_column($r->trace, 'role'));
    }

    public function test_un_appel_identique_n_est_ni_rejoue_ni_reaffiche(): void
    {
        $this->faux->scripts['a'] = [
            FauxFournisseur::outil('t1', 'outil_permis', ['search' => 'BTS']),
            FauxFournisseur::outil('t2', 'outil_permis', ['search' => 'BTS']),
            FauxFournisseur::texte('Fini.'),
        ];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();

        $boucle->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Classes ?']], $catalogue->schemas($user)), $user, $ui,
            fn () => ['kind' => 'table'], new FilDeReponse());

        $this->assertSame(1, $this->permis->appels);
        $this->assertSame(1, collect($this->parts())->where('type', 'data-widget')->count());
        $this->assertSame('retire', collect($this->parts())->where('type', 'data-etape')->where('id', 't2')->last()['data']['etat']);
        $troisieme = end($this->faux->recues[2]['requete']->messages);
        $this->assertStringContainsString('Appel identique', $troisieme['resultat']);
    }

    public function test_le_dernier_tour_demande_de_conclure(): void
    {
        config(['assistant.limites.tours' => 2]);
        $this->faux->scripts['a'] = [
            FauxFournisseur::outil('t1', 'outil_permis', ['search' => 'BTS']),
            FauxFournisseur::texte('Conclusion.'),
        ];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();

        $r = $boucle->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Classes ?']], $catalogue->schemas($user)), $user, $ui);

        $this->assertSame('Conclusion.', $r->texteDernierTour);
        $this->assertStringContainsString('Dernier tour', end($this->faux->recues[1]['requete']->messages)['resultat']);
    }

    public function test_outil_non_autorise_ni_declare_ni_execute(): void
    {
        $this->faux->scripts['a'] = [
            FauxFournisseur::outil('t9', 'outil_interdit'),
            FauxFournisseur::texte('Je ne peux pas.'),
        ];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();

        $schemas = $catalogue->schemas($user);
        $this->assertSame(['outil_permis'], array_column($schemas, 'nom'));

        $r = $boucle->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Paiements ?']], $schemas), $user, $ui);

        $this->assertFalse($this->interdit->execute);
        $this->assertSame([], $r->appels);
        $resultat = $this->faux->recues[1]['requete']->messages[2];
        $this->assertSame(['error' => 'Outil indisponible.'], json_decode($resultat['resultat'], true));
        $dernier = array_values(array_filter($this->parts(), fn ($p) => $p['type'] === 'data-etape'));
        $this->assertSame('echec', end($dernier)['data']['etat']);
    }

    public function test_repli_sur_le_modele_suivant_si_le_premier_tombe_avant_de_repondre(): void
    {
        $this->faux->scripts['a'] = [[EvenementModele::erreur('http_529')]];
        $this->faux->scripts['b'] = [FauxFournisseur::texte('Réponse du secours.')];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();

        $r = $boucle->executer([$this->modele('a'), $this->modele('b')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Q']]), $user, $ui);

        $this->assertSame('ok', $r->statut);
        $this->assertSame('b', $r->modele);
        $this->assertSame([['modele' => 'a', 'code' => 'http_529']], $r->essais);
        $this->assertSame('Réponse du secours.', $r->texte);
        $this->assertNotContains('error', $this->types());
    }

    public function test_pas_de_repli_silencieux_apres_un_debut_de_reponse(): void
    {
        $this->faux->scripts['a'] = [[EvenementModele::texte('Je commence'), EvenementModele::erreur('flux_overloaded_error')]];
        $this->faux->scripts['b'] = [FauxFournisseur::texte('Ne doit pas servir.')];
        [$boucle, $catalogue, $ui] = $this->boucle();

        $r = $boucle->executer([$this->modele('a'), $this->modele('b')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Q']]), $this->utilisateur(), $ui);

        $this->assertSame('erreur', $r->statut);
        $this->assertSame('Je commence', $r->texte);
        $this->assertCount(1, $this->faux->recues);
        $erreur = collect($this->parts())->firstWhere('type', 'error');
        $this->assertStringNotContainsString('overloaded', $erreur['errorText']);
    }

    public function test_tous_les_modeles_en_panne_donnent_une_partie_error(): void
    {
        $this->faux->scripts['a'] = [[EvenementModele::erreur('reseau')]];
        $this->faux->scripts['b'] = [[EvenementModele::erreur('http_500')]];
        [$boucle, $catalogue, $ui] = $this->boucle();

        $r = $boucle->executer([$this->modele('a'), $this->modele('b')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Q']]), $this->utilisateur(), $ui);

        $this->assertSame('erreur', $r->statut);
        $this->assertCount(2, $r->essais);
        $this->assertContains('error', $this->types());
    }

    public function test_aucun_modele_configure(): void
    {
        [$boucle, , $ui] = $this->boucle();

        $r = $boucle->executer([], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Q']]), $this->utilisateur(), $ui);

        $this->assertSame('erreur', $r->statut);
        $this->assertSame(['error'], $this->types());
    }

    public function test_nombre_de_tours_borne(): void
    {
        config(['assistant.limites.tours' => 2]);
        $this->faux->scripts['a'] = [
            FauxFournisseur::outil('t1', 'outil_permis'),
            FauxFournisseur::outil('t2', 'outil_permis'),
            FauxFournisseur::texte('jamais atteint'),
        ];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();

        $r = $boucle->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Q']], $catalogue->schemas($user)), $user, $ui);

        $this->assertSame('limite', $r->statut);
        $this->assertSame(2, $r->tours);
        $this->assertCount(2, $this->faux->recues);
    }

    public function test_budget_de_jetons_arrete_la_boucle(): void
    {
        config(['assistant.limites.budget_tokens' => 25]);
        $this->faux->scripts['a'] = [
            FauxFournisseur::outil('t1', 'outil_permis'),
            FauxFournisseur::texte('jamais atteint'),
        ];
        [$boucle, $catalogue, $ui] = $this->boucle();
        $user = $this->utilisateur();

        $r = $boucle->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Q']], $catalogue->schemas($user)), $user, $ui);

        $this->assertSame('limite', $r->statut);
        $this->assertCount(1, $this->faux->recues);
    }

    public function test_arret_demande_par_le_navigateur(): void
    {
        $this->faux->scripts['a'] = [[EvenementModele::texte('Début'), EvenementModele::texte(' suite'), EvenementModele::fin('fin')]];
        $catalogue = new CatalogueOutils(Mockery::mock(ChatbotSetupGuideService::class), [$this->permis]);
        $deltas = 0;
        $ui = new UiMessageStream(function (string $frame) use (&$deltas) {
            $this->frames[] = $frame;
            if (str_contains($frame, 'text-delta')) {
                $deltas++;
            }
        }, function () use (&$deltas) {
            return $deltas >= 1;
        });

        $r = (new BoucleAgent($catalogue))->executer([$this->modele('a')], new RequeteModele('sys', [['role' => 'user', 'texte' => 'Q']]), $this->utilisateur(), $ui);

        $this->assertSame('interrompu', $r->statut);
        $this->assertSame('Début', $r->texte);
    }

    public function test_registre_candidats_respecte_defaut_autorises_et_cles(): void
    {
        config([
            'assistant.fournisseurs' => [
                'p1' => ['adaptateur' => 'faux', 'cle' => 'k', 'url' => 'https://p1.test'],
                'p2' => ['adaptateur' => 'faux', 'cle' => null, 'url' => 'https://p2.test'],
            ],
            'assistant.modeles' => [
                'un' => ['fournisseur' => 'p1', 'modele' => 'm1', 'libelle' => 'Un'],
                'deux' => ['fournisseur' => 'p1', 'modele' => 'm2', 'libelle' => 'Deux'],
                'sans-cle' => ['fournisseur' => 'p2', 'modele' => 'm3', 'libelle' => 'Sans clé'],
            ],
            'assistant.modele_defaut' => 'sans-cle',
            'assistant.repli' => ['deux', 'un'],
            'assistant.modeles_autorises' => [],
        ]);

        $registre = new class extends RegistreDesModeles {
            protected function reglage(string $cle): mixed
            {
                return null;
            }
        };

        $this->assertSame(['un', 'deux'], array_keys($registre->disponibles()));
        $this->assertSame(['deux', 'un'], array_map(fn ($m) => $m->cle, $registre->candidats()));
        $this->assertSame(['un', 'deux'], array_map(fn ($m) => $m->cle, $registre->candidats('un')));
        $this->assertSame('https://p1.test/', $registre->tous()['un']->url);
        $this->assertArrayNotHasKey('cleApi', $registre->tous()['un']->versPublic());

        config(['assistant.modeles_autorises' => ['un']]);
        $this->assertSame(['un'], array_map(fn ($m) => $m->cle, $registre->candidats('deux')));
    }
}

/**
 * Outil de test : note s'il a été exécuté.
 */
class OutilEspion extends ChatbotTool
{
    public bool $execute = false;

    public int $appels = 0;

    public function __construct(private string $nom)
    {
    }

    public function name(): string
    {
        return $this->nom;
    }

    public function description(): string
    {
        return 'Outil de test';
    }

    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => ['search' => ['type' => 'string']]];
    }

    public function execute(array $args, $user): array
    {
        $this->execute = true;
        $this->appels++;

        return ['results' => [['nom' => 'BTS 1']], 'count' => 1, 'display_type' => 'table'];
    }
}
