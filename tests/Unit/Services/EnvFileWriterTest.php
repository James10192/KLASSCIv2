<?php

namespace Tests\Unit\Services;

use App\Services\Deployment\EnvFileWriter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Ce service ecrit dans le fichier dont depend le demarrage de l'instance.
 *
 * Une ecriture ratee ne se manifeste pas par une erreur : elle se manifeste par
 * une ecole entiere qui ne demarre plus. Ces tests verrouillent donc ce qui
 * doit rester vrai — le reste du fichier intact, une cle en double resolue, et
 * un refus franc plutot qu'une ecriture partielle.
 */
class EnvFileWriterTest extends TestCase
{
    private string $chemin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chemin = sys_get_temp_dir().'/env-test-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->chemin.'-backups/*') ?: [] as $fichier) {
            @unlink($fichier);
        }
        @rmdir($this->chemin.'-backups');

        foreach (glob($this->chemin.'*') ?: [] as $fichier) {
            @unlink($fichier);
        }

        parent::tearDown();
    }

    private function poser(string $contenu): EnvFileWriter
    {
        file_put_contents($this->chemin, $contenu);

        return new EnvFileWriter($this->chemin, $this->chemin.'-backups');
    }

    public function test_une_cle_absente_est_ajoutee_sans_toucher_au_reste(): void
    {
        $ecrivain = $this->poser("APP_NAME=KLASSCI\nDB_HOST=127.0.0.1\n");

        $this->assertTrue($ecrivain->ecrire('REINSCRIPTION_PORTAL_SECRET', 'x'.str_repeat('a', 40)));

        $contenu = file_get_contents($this->chemin);
        $this->assertStringContainsString('APP_NAME=KLASSCI', $contenu);
        $this->assertStringContainsString('DB_HOST=127.0.0.1', $contenu);
        $this->assertStringContainsString('REINSCRIPTION_PORTAL_SECRET=x'.str_repeat('a', 40), $contenu);
    }

    public function test_une_cle_existante_est_remplacee_a_sa_place(): void
    {
        $ecrivain = $this->poser("APP_NAME=KLASSCI\nMA_CLE=ancienne\nDB_HOST=127.0.0.1\n");

        $this->assertFalse($ecrivain->ecrire('MA_CLE', 'nouvelle'));

        $lignes = array_values(array_filter(explode(PHP_EOL, file_get_contents($this->chemin)), 'strlen'));

        // La position compte : un .env se relit a l'oeil, et deplacer une cle
        // en fin de fichier a chaque ecriture le rendrait illisible.
        $this->assertSame('MA_CLE=nouvelle', $lignes[1]);
        $this->assertSame('DB_HOST=127.0.0.1', $lignes[2]);
        $this->assertStringNotContainsString('ancienne', file_get_contents($this->chemin));
    }

    public function test_une_cle_en_double_est_ramenee_a_une_seule(): void
    {
        // phpdotenv retient la PREMIERE occurrence. Laisser la seconde ferait
        // que le fichier montre la nouvelle valeur pendant que l'application
        // continue d'utiliser l'ancienne.
        $ecrivain = $this->poser("MA_CLE=un\nAUTRE=x\nMA_CLE=deux\n");

        $ecrivain->ecrire('MA_CLE', 'trois');

        $contenu = file_get_contents($this->chemin);
        $this->assertSame(1, substr_count($contenu, 'MA_CLE='));
        $this->assertStringContainsString('MA_CLE=trois', $contenu);
        $this->assertStringContainsString('AUTRE=x', $contenu);
    }

    /**
     * La faille que la suite precedente ne voyait pas.
     *
     * Une valeur multiligne s'ecrivait sur N lignes physiques. A la reecriture
     * suivante, la boucle de remplacement — qui raisonne en lignes — remplacait
     * la premiere et abandonnait les N-1 autres, devenues des entrees .env a
     * part entiere. Deux appels sur la SEULE cle autorisee suffisaient donc a
     * poser DB_SOCKET, APP_DEBUG ou MAIL_MAILER.
     */
    public function test_une_valeur_multiligne_ne_peut_pas_injecter_une_autre_cle(): void
    {
        $ecrivain = $this->poser("APP_NAME=KLASSCI\nDB_HOST=127.0.0.1\n");

        $this->expectException(RuntimeException::class);

        $ecrivain->ecrire('MA_CLE', "valeur\nAPP_DEBUG=true");
    }

    public function test_le_fichier_reste_intact_apres_une_valeur_refusee(): void
    {
        $ecrivain = $this->poser("APP_NAME=KLASSCI\nDB_HOST=127.0.0.1\n");
        $avant = file_get_contents($this->chemin);

        foreach (["a\nB=1", "a\r\nB=1", "a\0B=1", "a\rB=1"] as $charge) {
            try {
                $ecrivain->ecrire('MA_CLE', $charge);
                $this->fail('Une valeur a caractere de controle aurait du etre refusee : '.addcslashes($charge, "\0..\37"));
            } catch (RuntimeException) {
                // attendu
            }
        }

        $this->assertSame($avant, file_get_contents($this->chemin), 'Un refus ne doit rien ecrire du tout.');
    }

    public function test_une_valeur_a_espaces_est_refusee(): void
    {
        // Les secrets poses ici sont des jetons opaques. Accepter un espace
        // obligeait a un mecanisme de guillemets dont la relecture etait
        // asymetrique : refuser a la frontiere supprime le probleme.
        $this->expectException(RuntimeException::class);

        $this->poser("APP_NAME=KLASSCI\n")->ecrire('MA_CLE', 'valeur avec espaces');
    }

    public function test_l_empreinte_identifie_la_valeur_sans_la_reveler(): void
    {
        $secret = 'secret-de-test-suffisamment-long-pour-passer';
        $ecrivain = $this->poser("MA_CLE={$secret}\n");

        $empreinte = $ecrivain->empreinte('MA_CLE');

        $this->assertSame(substr(hash('sha256', $secret), 0, 12), $empreinte);
        $this->assertStringNotContainsString('secret', (string) $empreinte);
    }

    public function test_l_empreinte_est_nulle_quand_la_cle_manque(): void
    {
        $this->assertNull($this->poser("APP_NAME=KLASSCI\n")->empreinte('ABSENTE'));
    }

    public function test_les_deux_cotes_d_un_meme_secret_ont_la_meme_empreinte(): void
    {
        // C'est tout l'usage : comparer l'instance et le site vitrine sans
        // jamais faire circuler le secret.
        $secret = 'un-secret-partage-suffisamment-long-ici-ok';

        $instance = $this->poser("REINSCRIPTION_PORTAL_SECRET={$secret}\n");

        $this->assertSame(
            substr(hash('sha256', $secret), 0, 12),
            $instance->empreinte('REINSCRIPTION_PORTAL_SECRET')
        );
    }

    public function test_une_sauvegarde_est_posee_avant_toute_modification(): void
    {
        $ecrivain = $this->poser("APP_NAME=KLASSCI\nMA_CLE=ancienne\n");

        $ecrivain->ecrire('MA_CLE', 'nouvelle');

        // Hors du depot : une copie en clair du .env a la racine du projet
        // n'etait pas ignoree par git et se retrouvait balayee dans .git/ par
        // le `git stash --include-untracked` de /api/cli/pull.
        $sauvegardes = glob($this->chemin.'-backups/env-*') ?: [];
        $this->assertCount(1, $sauvegardes);
        $this->assertStringContainsString('MA_CLE=ancienne', file_get_contents($sauvegardes[0]));
    }

    public function test_les_sauvegardes_ne_s_accumulent_pas_sans_limite(): void
    {
        // A soixante ecritures par minute, une accumulation sans limite
        // saturerait le disque d'un hebergement mutualise.
        $ecrivain = $this->poser("MA_CLE=depart\n");

        for ($i = 0; $i < 9; $i++) {
            $ecrivain->ecrire('MA_CLE', 'valeur'.$i);
        }

        $this->assertLessThanOrEqual(5, count(glob($this->chemin.'-backups/env-*') ?: []));
    }

    public function test_un_fichier_absent_leve_plutot_que_d_en_creer_un(): void
    {
        // Creer un .env vide sur une instance qui n'en a pas la rendrait
        // muette au demarrage, sans indiquer pourquoi.
        $this->expectException(RuntimeException::class);

        (new EnvFileWriter($this->chemin.'-inexistant', $this->chemin.'-backups'))->ecrire('MA_CLE', 'valeur');
    }

    public function test_aucun_fichier_temporaire_ne_subsiste(): void
    {
        $ecrivain = $this->poser("MA_CLE=ancienne\n");

        $ecrivain->ecrire('MA_CLE', 'nouvelle');

        $this->assertSame([], glob($this->chemin.'.tmp-*') ?: []);
    }
}
