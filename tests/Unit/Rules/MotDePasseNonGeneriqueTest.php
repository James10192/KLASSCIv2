<?php

namespace Tests\Unit\Rules;

use App\Rules\MotDePasseNonGenerique;
use App\Services\UserService;
use Tests\TestCase;

/**
 * Execute la regle telle que le validateur l'appelle : on observe si `$fail`
 * a ete invoque, on ne lit pas le texte de la regle.
 *
 * Sur `Tests\TestCase` et non sur PHPUnit nu : la valeur par defaut se lit
 * dans la configuration, qui demande le conteneur. Aucune base n'est touchee.
 */
class MotDePasseNonGeneriqueTest extends TestCase
{
    /** @return array{refuse: bool, message: ?string} */
    private function evaluer(mixed $valeur): array
    {
        $message = null;
        (new MotDePasseNonGenerique)('password', $valeur, function (string $m) use (&$message): void {
            $message = $m;
        });

        return ['refuse' => $message !== null, 'message' => $message];
    }

    public function test_le_mot_de_passe_par_defaut_est_refuse(): void
    {
        $resultat = $this->evaluer(UserService::defaultPassword());

        $this->assertTrue($resultat['refuse']);
        $this->assertStringContainsString('par défaut', $resultat['message']);
    }

    /**
     * La regle suit la valeur configuree, pas seulement le motif historique :
     * une ecole qui choisit son propre mot de passe par defaut reste protegee.
     */
    public function test_la_regle_suit_la_valeur_configuree_par_l_instance(): void
    {
        config(['securite.mot_de_passe_par_defaut' => 'Akwaba#Ecole1']);

        $this->assertSame('Akwaba#Ecole1', UserService::defaultPassword());
        $this->assertTrue($this->evaluer('Akwaba#Ecole1')['refuse']);
        $this->assertFalse($this->evaluer('Akwaba#Ecole2')['refuse']);
    }

    public function test_les_valeurs_par_defaut_des_annees_passees_sont_refusees_aussi(): void
    {
        $this->assertTrue($this->evaluer('Bonjour@2025')['refuse']);
        $this->assertTrue($this->evaluer('bonjour@2024')['refuse']);
        $this->assertTrue($this->evaluer('  BONJOUR@2026 ')['refuse']);
    }

    public function test_un_mot_de_passe_personnel_passe(): void
    {
        $this->assertFalse($this->evaluer('Bonjour@2026x')['refuse']);
        $this->assertFalse($this->evaluer('Bonjour2026')['refuse']);
        $this->assertFalse($this->evaluer('Kouassi#Ecole2026')['refuse']);
    }

    public function test_une_valeur_non_textuelle_n_est_pas_jugee_par_cette_regle(): void
    {
        $this->assertFalse($this->evaluer(null)['refuse']);
        $this->assertFalse($this->evaluer(12345678)['refuse']);
    }

    public function test_la_creation_et_la_reinitialisation_partagent_la_meme_valeur(): void
    {
        $this->assertSame(UserService::defaultPassword(), (new UserService)->generateDefaultPassword());
        $this->assertSame('Bonjour@'.date('Y'), UserService::defaultPassword());
    }
}
