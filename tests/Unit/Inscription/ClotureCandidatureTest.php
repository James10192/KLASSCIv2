<?php

namespace Tests\Unit\Inscription;

use App\Enums\ClotureCandidature;
use App\Models\ESBTPInscription;
use App\Services\Inscription\PortailCandidatureService;
use App\Services\Inscription\RattachementCandidature;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

/**
 * Ce que l'agent lit quand la candidature ne se ferme pas.
 *
 * Trois fins differentes partagent ce chemin, et elles ne demandent pas la
 * meme chose : « vous n'avez pas le droit » s'adresse a un collegue, « elle
 * avait deja change d'etat » demande d'aller voir la corbeille, et la panne
 * seule justifie le support. Les confondre envoie l'agent chercher au mauvais
 * endroit en pleine rentree.
 *
 * La date de naissance ne figure plus ici : ce controle-la est passe AVANT la
 * creation (voir NaissanceDivergenteTest), la ou la decision est encore
 * reversible.
 *
 * Les deux issues normales sont des VALEURS, plus un melange de booleen et
 * d'exception. C'etait la forme precedente, et elle faisait dependre le message
 * montre a l'agent d'un `catch` dont la classe devait etre importee : un
 * `catch` sur une classe absente ne leve aucune erreur en PHP, il ne correspond
 * simplement jamais, en silence, et l'agent recevait « signalez-le au support »
 * a la place. La revue avait deja trouve exactement cette faute sur le
 * controleur public.
 */
class ClotureCandidatureTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Un utilisateur dont on connait la reponse, sans base de donnees.
     *
     * Le `can()` reel passe par Spatie, qui lit les permissions en base. Ce
     * qui est teste ici n'est pas la permission mais ce que l'agent LIT selon
     * qu'il l'a ou non.
     */
    private function utilisateur(bool $habilite): Authenticatable
    {
        return new class($habilite) implements Authenticatable
        {
            public function __construct(private bool $habilite) {}

            public function can(string $ability): bool
            {
                return $this->habilite;
            }

            public function getAuthIdentifierName()
            {
                return 'id';
            }

            public function getAuthIdentifier()
            {
                return 1;
            }

            public function getAuthPassword()
            {
                return '';
            }

            public function getRememberToken()
            {
                return null;
            }

            public function setRememberToken($value) {}

            public function getRememberTokenName()
            {
                return null;
            }
        };
    }

    /**
     * Appelle la fermeture avec un service qui repond ce qu'on lui dit.
     *
     * @param  ClotureCandidature|\Throwable  $reponse  ce que rend (ou jette) le service
     */
    private function message($reponse, bool $habilite = true): ?string
    {
        Auth::setUser($this->utilisateur($habilite));

        $candidatures = Mockery::mock(PortailCandidatureService::class);

        if ($reponse instanceof \Throwable) {
            $candidatures->shouldReceive('fermerApresInscription')->once()->andThrow($reponse);
        } elseif ($habilite) {
            $candidatures->shouldReceive('fermerApresInscription')->once()->andReturn($reponse);
        }

        $inscription = new ESBTPInscription;
        $inscription->forceFill(['id' => 42, 'etudiant_id' => 7]);

        return (new RattachementCandidature($candidatures))->fermer(
            Request::create('/', 'POST', ['candidature_id' => 12]),
            $inscription
        );
    }

    public function test_une_fermeture_reussie_ne_dit_rien_a_l_agent(): void
    {
        $this->assertNull($this->message(ClotureCandidature::Fermee));
    }

    /**
     * Un collegue a traite la candidature entre-temps : ce n'est pas une
     * panne, et le message ne doit pas envoyer l'agent au support.
     */
    public function test_une_candidature_deja_traitee_est_dite_a_l_agent(): void
    {
        $message = $this->message(ClotureCandidature::DejaChangee);

        $this->assertNotNull($message);
        $this->assertStringContainsString('corbeille', $message);
        $this->assertStringNotContainsStringIgnoringCase(
            'support',
            $message,
            "Ce n'est pas une panne : envoyer l'agent au support lui ferait perdre sa journee"
        );
    }

    /** Les trois fins doivent rester distinctes, sinon les separer ne sert a rien. */
    public function test_les_trois_fins_ne_disent_pas_la_meme_chose(): void
    {
        $messages = [
            $this->message(ClotureCandidature::DejaChangee),
            $this->message(new \RuntimeException('panne quelconque')),
            $this->message(ClotureCandidature::Fermee, habilite: false),
        ];

        $this->assertCount(3, array_unique($messages));
    }

    /**
     * Le `match` doit couvrir toutes les issues.
     *
     * Un `match` non exhaustif leve `UnhandledMatchError` — un `\Error`, que le
     * `catch (\Exception)` de `store()` ne rattrape pas : un 500 sur un
     * enregistrement d'inscription reussi. Ce test parcourt l'enum plutot que
     * de citer ses cas : une troisieme issue ajoutee un jour sans son message
     * fera echouer ici, pas chez un agent en pleine rentree.
     */
    public function test_chaque_issue_a_son_message(): void
    {
        foreach (ClotureCandidature::cases() as $issue) {
            $message = $this->message($issue);

            $this->assertTrue(
                $message === null || $message !== '',
                "L'issue {$issue->value} doit produire soit rien a dire, soit une phrase"
            );
        }
    }
}
