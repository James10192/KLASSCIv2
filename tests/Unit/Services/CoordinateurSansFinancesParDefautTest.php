<?php

namespace Tests\Unit\Services;

use App\Providers\AuthServiceProvider;
use App\Services\PermissionRegistry;
use Tests\TestCase;

/**
 * Le coordinateur est un profil pédagogique : par défaut, il ne lit pas ce
 * qu'un étudiant a payé ou doit. Aucune des permissions qui ouvrent la porte
 * `finances.etudiants.voir` ne figure donc dans son lot d'usine. Une école qui
 * veut le contraire la coche sur le rôle.
 */
class CoordinateurSansFinancesParDefautTest extends TestCase
{
    public function test_le_lot_par_defaut_du_coordinateur_n_ouvre_pas_la_porte_financiere(): void
    {
        $defaults = (new PermissionRegistry())->defaultPermissionsFor('coordinateur');

        $this->assertNotEmpty($defaults);
        foreach (AuthServiceProvider::PERMISSIONS_FINANCES_ETUDIANTS as $permission) {
            $this->assertNotContains($permission, $defaults, $permission);
        }
    }
}
