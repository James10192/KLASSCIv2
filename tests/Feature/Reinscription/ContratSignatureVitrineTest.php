<?php

namespace Tests\Feature\Reinscription;

use App\Services\Reinscription\PortailSignatureVerifier;
use Tests\TestCase;

/**
 * Le contrat de signature entre le site vitrine et KLASSCI.
 *
 * Ce test existe parce que les deux moities du contrat vivent dans deux depots
 * et deux langages. Rien, dans KLASSCI seul, ne dit si le site vitrine calcule
 * la meme signature — et une divergence ne se manifeste que par un 401 sans
 * aucune explication, cote production, le jour de l'ouverture du canal.
 *
 * Les vecteurs ci-dessous sont produits par le VRAI module du site vitrine
 * (`lib/reinscription/signature.ts`, execute par Node), puis figes ici. Si l'un
 * des deux cotes change sa facon de construire la charge, ce test tombe.
 *
 * Ne JAMAIS regenerer un vecteur en le recalculant avec le code PHP : cela
 * reviendrait a verifier que PHP est d'accord avec lui-meme. Le vecteur se
 * regenere depuis le depot du site vitrine, avec le module reel :
 *
 *   node -e "const{createHmac}=require('node:crypto');
 *     const corps=JSON.stringify({matricule:'DEMO-0001',date_naissance:'2004-03-15',ip_client:'196.207.1.42'});
 *     const ts=Date.now();
 *     const charge=[ts,'POST','api/public/reinscription/lookup',corps].join('.');
 *     console.log(ts, createHmac('sha256','<secret>').update(charge,'utf8').digest('hex'));"
 *
 * Ce test n'a besoin ni de base ni de cache : il ne fait que du calcul.
 */
class ContratSignatureVitrineTest extends TestCase
{
    private const SECRET = 'un-secret-de-test-suffisamment-long-pour-passer';

    /**
     * Vecteur produit par Node, le 2026-08-25.
     *
     * Le corps est la chaine EXACTE emise par `JSON.stringify` — ordre des
     * cles compris. C'est ce que le site vitrine envoie sur le fil, et c'est
     * donc ce que KLASSCI doit signer : le contrat porte sur des octets, pas
     * sur un objet.
     */
    private const CORPS = '{"matricule":"DEMO-0001","date_naissance":"2004-03-15","ip_client":"196.207.1.42"}';

    private const HORODATAGE = 1787792672292;

    private const SIGNATURE_NODE = 'e73db1822bf4f4b1150b5da70cfce6a3d7b2c16ca241d17ecc4647b0e8ad60c5';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.reinscription_portal.secret' => self::SECRET]);
    }

    public function test_php_calcule_la_meme_signature_que_le_site_vitrine(): void
    {
        $calculee = app(PortailSignatureVerifier::class)->signature(
            self::CORPS,
            'POST',
            'api/public/reinscription/lookup',
            self::HORODATAGE,
        );

        $this->assertSame(
            self::SIGNATURE_NODE,
            $calculee,
            "Les deux moities du contrat ont diverge. Une signature du site vitrine ".
            "sera refusee en 401, sans explication, des l'ouverture du canal."
        );
    }

    public function test_le_chemin_avec_barre_de_tete_donne_la_meme_signature(): void
    {
        // KLASSCI passe `$request->path()`, deja sans barre de tete. Le site
        // vitrine, lui, manipule des chemins d'URL qui en portent une. Les deux
        // ecritures doivent converger, sinon le contrat depend de la facon dont
        // l'appelant a tape son chemin.
        $verifieur = app(PortailSignatureVerifier::class);

        $this->assertSame(
            $verifieur->signature(self::CORPS, 'POST', 'api/public/reinscription/lookup', self::HORODATAGE),
            $verifieur->signature(self::CORPS, 'POST', '/api/public/reinscription/lookup/', self::HORODATAGE),
        );
    }

    public function test_une_signature_de_lookup_ne_vaut_pas_pour_submit(): void
    {
        // Sans le chemin dans la charge, une signature interceptee sur la
        // consultation autoriserait un depot.
        $verifieur = app(PortailSignatureVerifier::class);

        $this->assertNotSame(
            $verifieur->signature(self::CORPS, 'POST', 'api/public/reinscription/lookup', self::HORODATAGE),
            $verifieur->signature(self::CORPS, 'POST', 'api/public/reinscription/submit', self::HORODATAGE),
        );
    }

    public function test_un_horodatage_en_secondes_est_refuse(): void
    {
        // Piege classique cote site vitrine : `Math.floor(Date.now()/1000)`.
        // KLASSCI le refuse explicitement plutot que d'echouer plus loin sur
        // une fenetre de validite calculee a la mauvaise echelle.
        $secondes = (int) (self::HORODATAGE / 1000);

        $this->assertFalse(
            app(PortailSignatureVerifier::class)->verifie(
                self::CORPS,
                'POST',
                'api/public/reinscription/lookup',
                'peu-importe',
                $secondes,
            )
        );
    }
}
