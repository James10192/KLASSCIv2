<?php

namespace Tests\Feature\Emails;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Une faute PROBABLE (distance d'edition) ne se corrige qu'avec
 * `inclure_probables`, et seulement si le resolveur dit, a l'instant, que le
 * domaine n'existe pas. Cache, suspension ou reponse inconnue : rien.
 */
class CorrigerFautesProbablesTest extends TestCase
{
    use ConstruitCorrections;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->preparerCorrections();
        $this->dns->mx['gmaill.com'] = false;
    }

    public function test_sans_demande_expresse_une_faute_probable_n_est_ni_proposee_ni_corrigee(): void
    {
        $c = $this->candidature('kone@gmaill.com');
        $this->dns->inexistants['gmaill.com'] = true;

        $this->postJson(self::ROUTE, ['execute' => false])->assertOk()->assertJsonPath('data.propositions_total', 0);
        $this->corriger([$this->cle('esbtp_candidatures', $c->id)])
            ->assertOk()->assertJsonPath('data.ignorees.0.motif', 'faute_probable_non_autorisee');

        $this->assertSame('kone@gmaill.com', $c->fresh()->email);
    }

    public function test_une_reponse_dns_inconnue_n_ecrit_rien(): void
    {
        $c = $this->candidature('kone@gmaill.com');

        $proposition = $this->postJson(self::ROUTE, ['execute' => false, 'inclure_probables' => true])->assertOk()->json('data.propositions.0');
        $this->assertSame('probable', $proposition['nature']);

        $this->corriger([$this->cle('esbtp_candidatures', $c->id)], ['inclure_probables' => true])
            ->assertOk()->assertJsonPath('data.ignorees.0.motif', 'dns_non_confirme');
        $this->assertSame('kone@gmaill.com', $c->fresh()->email);
    }

    public function test_un_verdict_en_cache_ou_une_suspension_ne_suffisent_pas(): void
    {
        $c = $this->candidature('kone@gmaill.com');
        // Ce que la verification MX habituelle aurait memorise : « ne recoit rien », resolveur suspendu.
        Cache::put('emails-joignables:mx:'.hash('sha256', 'gmaill.com'), false, 3600);
        Cache::put('emails-joignables:mx:resolveur-lent', true, 300);

        $this->corriger([$this->cle('esbtp_candidatures', $c->id)], ['inclure_probables' => true])
            ->assertOk()->assertJsonPath('data.ignorees.0.motif', 'dns_non_confirme');

        $this->assertSame(1, $this->dns->appelsStricts, 'La verification stricte est toujours faite, a l\'instant.');
        $this->assertSame('kone@gmaill.com', $c->fresh()->email);
    }

    public function test_un_nxdomain_frais_avec_demande_expresse_corrige(): void
    {
        $c = $this->candidature('kone@gmaill.com');
        $this->dns->inexistants['gmaill.com'] = true;

        $this->corriger([$this->cle('esbtp_candidatures', $c->id)], ['inclure_probables' => true])
            ->assertOk()->assertJsonPath('data.corrigees', 1);

        $this->assertSame('kone@gmail.com', $c->fresh()->email);
    }

    public function test_une_extension_de_pays_valide_n_est_jamais_touchee(): void
    {
        $orange = $this->candidature('awa@orange.cm');
        $proche = $this->candidature('awa@gmial.cm');
        $this->dns->mx['gmial.cm'] = false;
        $this->dns->inexistants['gmial.cm'] = true;

        $this->postJson(self::ROUTE, ['execute' => false, 'inclure_probables' => true])->assertOk()->assertJsonPath('data.propositions_total', 0);
        $this->corriger([$this->cle('esbtp_candidatures', $orange->id), $this->cle('esbtp_candidatures', $proche->id)], ['inclure_probables' => true])
            ->assertOk()->assertJsonPath('data.corrigees', 0);

        $this->assertSame('awa@orange.cm', $orange->fresh()->email);
        $this->assertSame('awa@gmial.cm', $proche->fresh()->email);
    }
}
