<?php

namespace Tests\Unit\Logging;

use App\Logging\CaviarderLeContexte;
use PHPUnit\Framework\TestCase;

/**
 * Ce filet est la derniere ligne avant le disque : ce qu'il laisse passer
 * s'ecrit dans un fichier que personne ne relira avant l'incident.
 */
class CaviarderLeContexteTest extends TestCase
{
    /** Appelle le processeur comme Monolog 2 le fait : un tableau de record. */
    private function traiter(array $contexte): array
    {
        $processeur = new CaviarderLeContexte();
        $methode = new \ReflectionMethod($processeur, 'traiter');
        $methode->setAccessible(true);

        $record = $methode->invoke($processeur, [
            'message' => 'peu importe',
            'context' => $contexte,
            'extra' => [],
        ]);

        return $record['context'];
    }

    public function test_un_mot_de_passe_ne_traverse_pas(): void
    {
        $sortie = $this->traiter([
            'password' => 'Secret2026!',
            'password_confirmation' => 'Secret2026!',
            'mot_de_passe' => 'Secret2026!',
        ]);

        $this->assertSame('[caviardé]', $sortie['password']);
        $this->assertSame('[caviardé]', $sortie['password_confirmation']);
        $this->assertSame('[caviardé]', $sortie['mot_de_passe']);
    }

    public function test_l_en_tete_cookie_ne_traverse_pas(): void
    {
        // C'est la fuite qui portait le `remember_web_*`, valable cinq ans.
        $sortie = $this->traiter([
            'request_headers' => [
                'cookie' => ['laravel_session=abc; remember_web_59ba=def'],
                'user-agent' => ['Mozilla/5.0'],
            ],
        ]);

        $this->assertSame(
            ['champs' => ['cookie', 'user-agent'], 'total' => 2, 'note' => 'contenu caviardé — vidage de requête'],
            $sortie['request_headers']
        );
    }

    public function test_un_jeton_est_caviarde_quelle_que_soit_la_casse(): void
    {
        $sortie = $this->traiter([
            'API_TOKEN' => 'zzz',
            'Authorization' => 'Bearer zzz',
            'client_secret' => 'zzz',
        ]);

        $this->assertSame('[caviardé]', $sortie['API_TOKEN']);
        $this->assertSame('[caviardé]', $sortie['Authorization']);
        $this->assertSame('[caviardé]', $sortie['client_secret']);
    }

    public function test_un_vidage_de_requete_ne_garde_que_les_noms_de_champs(): void
    {
        // L'etat civil d'un mineur n'a rien a faire dans un journal.
        $sortie = $this->traiter([
            'request' => [
                'nom' => 'KOUAME',
                'date_naissance' => '2009-04-11',
                'lieu_naissance' => 'Yamoussoukro',
                'nom_pere' => 'KOUAME Jean',
            ],
        ]);

        $this->assertSame(
            ['nom', 'date_naissance', 'lieu_naissance', 'nom_pere'],
            $sortie['request']['champs']
        );
        $this->assertArrayNotHasKey('nom_pere', $sortie['request']);
    }

    public function test_un_appel_precis_reste_intact(): void
    {
        // Un controleur qui journalise un champ nomme sait ce qu'il fait :
        // caviarder ici rendrait les journaux inutiles.
        $sortie = $this->traiter([
            'par_utilisateur' => 42,
            'inscription_id' => 1337,
            'email' => 'directrice@ecole.ci',
            'montant' => 150000,
        ]);

        $this->assertSame(42, $sortie['par_utilisateur']);
        $this->assertSame(1337, $sortie['inscription_id']);
        $this->assertSame('directrice@ecole.ci', $sortie['email']);
        $this->assertSame(150000, $sortie['montant']);
    }

    public function test_un_secret_imbrique_est_atteint(): void
    {
        $sortie = $this->traiter([
            'tenant' => ['code' => 'esbtp-yakro', 'api_token' => 'zzz'],
        ]);

        $this->assertSame('esbtp-yakro', $sortie['tenant']['code']);
        $this->assertSame('[caviardé]', $sortie['tenant']['api_token']);
    }

    public function test_une_structure_trop_profonde_est_coupee_sans_boucler(): void
    {
        $profond = 'fond';
        for ($i = 0; $i < 12; $i++) {
            $profond = ['niveau' => $profond];
        }

        $sortie = $this->traiter(['racine' => $profond]);

        $this->assertIsArray($sortie);
        $this->assertStringContainsString('profondeur', json_encode($sortie, JSON_UNESCAPED_UNICODE));
    }
}
