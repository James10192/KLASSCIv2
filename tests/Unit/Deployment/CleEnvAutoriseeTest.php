<?php

namespace Tests\Unit\Deployment;

use App\Services\Deployment\CleEnvAutorisee;
use PHPUnit\Framework\TestCase;

/**
 * Les regles qui disent ce que le CLI a le droit d'ecrire dans un .env.
 *
 * Ce test existe parce que la liste blanche est la seule chose qui separe un
 * jeton CLI du controle complet d'une instance. Une entree ajoutee sans garde
 * de forme, ou un defaut qui bascule du mauvais cote, ne se verrait pas a la
 * relecture : cela ne casse rien, cela ouvre.
 */
class CleEnvAutoriseeTest extends TestCase
{
    public function test_seules_les_cles_declarees_sont_ecrivables(): void
    {
        $this->assertTrue(CleEnvAutorisee::estAutorisee('TENANT_CODE'));
        $this->assertTrue(CleEnvAutorisee::estAutorisee('REINSCRIPTION_PORTAL_SECRET'));

        // Les cles qui donneraient le controle de l'instance.
        foreach (['DB_HOST', 'APP_KEY', 'APP_DEBUG', 'MAIL_HOST'] as $interdite) {
            $this->assertFalse(CleEnvAutorisee::estAutorisee($interdite), "{$interdite} ne doit pas etre ecrivable");
        }
    }

    /**
     * @dataProvider codesValides
     */
    public function test_un_code_d_instance_bien_forme_est_accepte(string $code): void
    {
        $this->assertTrue(CleEnvAutorisee::respecteFormat('TENANT_CODE', $code));
    }

    public static function codesValides(): array
    {
        return [
            'court' => ['usat'],
            'minimal' => ['abc'],
            'avec tiret' => ['esbtp-abidjan'],
            'avec chiffre' => ['ephrata2'],
            'longueur maximale' => [str_repeat('a', 32)],
        ];
    }

    /**
     * @dataProvider codesInvalides
     */
    public function test_un_code_mal_forme_est_refuse(string $code, string $pourquoi): void
    {
        $this->assertFalse(CleEnvAutorisee::respecteFormat('TENANT_CODE', $code), $pourquoi);
    }

    public static function codesInvalides(): array
    {
        return [
            ['USAT', 'les majuscules ne sont pas la forme des codes de la flotte'],
            ['us', 'trop court pour designer quoi que ce soit'],
            ['-usat', 'un tiret en tete'],
            ['usat-', 'un tiret en fin'],
            ['usat_bouake', 'le tiret bas appartient aux noms de base, pas aux codes'],
            ['usat bouake', "un espace n'est pas un code"],
            [str_repeat('a', 33), 'au-dela de 32 caracteres'],
            ['', 'vide'],
        ];
    }

    /**
     * Un secret n'a pas de forme imposee : seule sa longueur se controle.
     */
    public function test_une_cle_sans_format_accepte_toute_valeur(): void
    {
        $this->assertTrue(CleEnvAutorisee::respecteFormat('REINSCRIPTION_PORTAL_SECRET', 'nimporte-quoi-de-suffisamment-long'));
        $this->assertTrue(CleEnvAutorisee::respecteFormat('REINSCRIPTION_PORTAL_SECRET', 'MAJUSCULES_ET_SYMBOLES=+/'));
    }

    public function test_un_secret_ne_se_relit_pas_un_identifiant_si(): void
    {
        $this->assertTrue(CleEnvAutorisee::estSecrete('REINSCRIPTION_PORTAL_SECRET'));
        $this->assertFalse(CleEnvAutorisee::estSecrete('TENANT_CODE'));
    }

    /**
     * LE defaut qui compte.
     *
     * Une cle ajoutee demain sans preciser « secrete » ne doit pas voir sa
     * valeur publiee par inadvertance. Le defaut se ferme, il ne s'ouvre pas.
     */
    public function test_une_cle_inconnue_est_tenue_pour_secrete(): void
    {
        $this->assertTrue(CleEnvAutorisee::estSecrete('UNE_CLE_QUI_NEXISTE_PAS'));
    }

    public function test_chaque_cle_ecrivable_annonce_une_longueur_minimale(): void
    {
        foreach (CleEnvAutorisee::toutes() as $cle) {
            $this->assertGreaterThan(0, CleEnvAutorisee::longueurMinimale($cle), "{$cle} accepte une valeur de longueur nulle");
            $this->assertNotSame('', CleEnvAutorisee::description($cle), "{$cle} ne dit pas a quoi elle sert");
        }
    }
}
