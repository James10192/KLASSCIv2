<?php

namespace Tests\Unit\Inscription;

use App\Enums\CanalPortailPublic;
use App\Enums\NaturePortailPublic;
use App\Http\Middleware\PortailPublicGuard;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Services\Reinscription\PortailSignatureVerifier;
use App\Support\SeauDeDebit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Les seaux de debit du portail public, et ce qu'ils repondent.
 *
 * Testable sans base : la signature ne lit qu'un secret de configuration, les
 * seaux vivent sur l'enum, et RateLimiter s'appuie sur le magasin de cache
 * (`array` en test). Le garde s'arrete avant d'atteindre le controleur, donc
 * rien n'interroge MySQL.
 *
 * Ce qui est fige ici est le CONTRAT du 429, celui que le site vitrine consomme
 * pour choisir sa phrase. Trois seaux, trois refus, et les confondre revient a
 * accuser un visiteur de ce qu'il n'a pas fait — ou a lui promettre un delai
 * qui n'est pas le bon.
 *
 * Et la separation des seaux compte autant : partages, une affluence sur les
 * candidatures fermait la reinscription, et reciproquement.
 */
class DebitPortailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.reinscription_portal.secret' => str_repeat('k', 48)]);

        foreach ($this->tousLesSeaux() as $seau) {
            RateLimiter::clear($seau->cle);
        }
    }

    /** @return list<SeauDeDebit> */
    private function tousLesSeaux(): array
    {
        $seaux = [];

        foreach (CanalPortailPublic::cases() as $canal) {
            foreach (NaturePortailPublic::cases() as $nature) {
                $seaux = array_merge($seaux, $canal->seaux($nature, 'empreinte'));
            }
        }

        return $seaux;
    }

    /** Sature tous les seaux d'un canal, comme le ferait une affluence reelle. */
    private function saturer(
        CanalPortailPublic $canal,
        NaturePortailPublic $nature = NaturePortailPublic::Identite
    ): void {
        foreach ($canal->seaux($nature, 'empreinte') as $seau) {
            for ($i = 0; $i <= $seau->maximum; $i++) {
                RateLimiter::hit($seau->cle, 60);
            }
        }
    }

    private function bloque(
        CanalPortailPublic $canal,
        NaturePortailPublic $nature = NaturePortailPublic::Identite
    ): bool {
        foreach ($canal->seaux($nature, 'empreinte') as $seau) {
            if (RateLimiter::tooManyAttempts($seau->cle, $seau->maximum)) {
                return true;
            }
        }

        return false;
    }

    /** Une requete signee, telle que le site vitrine l'emet. */
    private function appel(string $chemin, array $corps = []): Request
    {
        $corpsBrut = json_encode($corps + ['ip_client' => '196.1.2.3']);
        $horodatage = (int) (microtime(true) * 1000);

        $requete = Request::create('/'.$chemin, 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $corpsBrut);

        $requete->headers->set('X-Klassci-Timestamp', (string) $horodatage);
        $requete->headers->set(
            'X-Klassci-Signature',
            app(PortailSignatureVerifier::class)->signature($corpsBrut, 'POST', $chemin, $horodatage)
        );

        return $requete;
    }

    private function passer(Request $requete, string $canal, string $nature = 'identite')
    {
        return app(PortailPublicGuard::class)->handle(
            $requete,
            fn () => response()->json(['passe' => true]),
            $canal,
            $nature
        );
    }

    /**
     * Le plafond de l'etablissement n'accuse pas le visiteur.
     *
     * C'est le coeur du contrat : `affluence`, et non `trop_de_tentatives`.
     */
    public function test_le_plafond_global_rend_le_code_affluence(): void
    {
        $this->saturer(CanalPortailPublic::Candidatures);

        $reponse = $this->passer($this->appel('api/public/inscription/submit'), 'candidatures');

        $this->assertSame(429, $reponse->getStatusCode());
        $this->assertSame('affluence', $reponse->getData(true)['code']);
        $this->assertStringNotContainsStringIgnoringCase(
            'trop de tentatives',
            $reponse->getData(true)['message'],
            "Ce visiteur n'a rien tente : lui dire le contraire est faux"
        );
    }

    /**
     * Une affluence sur les candidatures ne doit pas fermer la reinscription.
     *
     * Les seaux etaient communs : a la rentree d'Abidjan, cent vingt jetons par
     * minute pour l'etablissement entier plafonnaient les deux canaux ensemble
     * a une soixantaine de visiteurs par minute.
     */
    public function test_un_canal_sature_ne_ferme_pas_l_autre(): void
    {
        $this->saturer(CanalPortailPublic::Candidatures);

        $this->assertTrue($this->bloque(CanalPortailPublic::Candidatures));
        $this->assertFalse($this->bloque(CanalPortailPublic::Reinscriptions));
    }

    /**
     * Le catalogue a son propre seau.
     *
     * Ouvrir le formulaire coutait autant que le deposer : la file d'attente
     * devant le formulaire fermait le canal des envois.
     */
    public function test_le_catalogue_ne_partage_pas_le_seau_des_envois(): void
    {
        $this->saturer(CanalPortailPublic::Candidatures);

        $this->assertFalse(
            $this->bloque(CanalPortailPublic::Candidatures, NaturePortailPublic::Catalogue)
        );
    }

    /** Et il est plus large, sans quoi le separer ne servirait a rien. */
    public function test_le_seau_du_catalogue_est_plus_large(): void
    {
        $envois = CanalPortailPublic::Candidatures->seaux(NaturePortailPublic::Identite, 'e');
        $catalogue = CanalPortailPublic::Candidatures->seaux(NaturePortailPublic::Catalogue, 'e');

        foreach ($envois as $rang => $seau) {
            $this->assertGreaterThan($seau->maximum, $catalogue[$rang]->maximum);
        }
    }

    /** Une signature absente ferme la porte avant tout le reste. */
    public function test_sans_signature_le_garde_refuse(): void
    {
        $requete = Request::create('/api/public/inscription/submit', 'POST');

        $this->assertSame(401, $this->passer($requete, 'candidatures')->getStatusCode());
    }

    /**
     * Un canal inconnu retombe sur la reinscription, jamais sur une erreur.
     *
     * Le parametre vient du fichier de routes : une faute de frappe doit se
     * voir au premier essai, pas rendre un 500 sur une surface publique.
     */
    public function test_un_canal_inconnu_retombe_sur_la_reinscription(): void
    {
        $this->assertSame(
            CanalPortailPublic::Reinscriptions,
            CanalPortailPublic::depuis('canal-qui-nexiste-pas')
        );
    }

    /**
     * Une nature inconnue retombe sur la PLUS STRICTE.
     *
     * `portail.public:candidatures,catalog` — un caractere de moins — doit
     * donner le seau des envois, pas celui du catalogue. Se tromper dans ce
     * sens coute un seau trop etroit sur une lecture : visible, sans danger.
     * Dans l'autre, cela ouvrirait un seau large sur un point d'entree qui
     * ecrit.
     */
    public function test_une_nature_inconnue_retombe_sur_la_plus_stricte(): void
    {
        $this->assertSame(NaturePortailPublic::Identite, NaturePortailPublic::depuis('catalog'));

        $this->assertEquals(
            CanalPortailPublic::Candidatures->seaux(NaturePortailPublic::Identite, 'e'),
            CanalPortailPublic::Candidatures->seaux(NaturePortailPublic::depuis('catalog'), 'e')
        );
    }

    /**
     * Le seau du matricule ne dit pas au visiteur qu'il a trop essaye.
     *
     * C'est le seul seau qu'un TIERS peut remplir — cinq mauvaises dates de
     * naissance sur le matricule d'un camarade suffisent. « Trop de
     * tentatives » y accuserait quelqu'un qui n'a rien tente.
     *
     * Et il n'annonce AUCUNE duree. Le message affiche vit dans l'autre depot,
     * deploye separement : un chiffre calcule ici n'y arriverait jamais, un
     * chiffre recopie la-bas se figerait au premier durcissement de la fenetre.
     * Sans compter que la fenetre de Laravel part du premier essai, pas du
     * dernier — meme exact, le chiffre serait trompeur.
     */
    public function test_le_seau_du_matricule_a_son_propre_refus(): void
    {
        $refus = SeauDeDebit::parIdentifiant(
            'test-matricule',
            PortailReinscriptionService::DEBIT_MATRICULE_MAX,
            PortailReinscriptionService::DEBIT_MATRICULE_FENETRE_SECONDES
        )->refus();

        $this->assertSame('identification_bloquee', $refus['code']);
        $this->assertStringNotContainsStringIgnoringCase('trop de tentatives', $refus['message']);
        $this->assertDoesNotMatchRegularExpression(
            '/\d+\s*(minute|heure)/i',
            $refus['message'],
            'Aucune duree annoncee : elle ne pourrait pas rester vraie'
        );
    }
}
