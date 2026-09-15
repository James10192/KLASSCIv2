<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Services\Security\SeparationOfDutiesService;
use Tests\TestCase;

/**
 * Verrouille le fait que les regles de separation des devoirs soient
 * REELLEMENT atteignables depuis l'ecran des reglages.
 *
 * Le service lisait deja trois reglages d'instance, mais aucun des trois
 * n'etait expose : l'ecole ne pouvait ni les voir ni les changer. Trois
 * controles OHADA pilotables seulement par un fichier de configuration
 * livre — c'est-a-dire pas pilotables.
 *
 * Aucune base ici : on boote l’application pour lire sa configuration, rien de plus.
 */
class SeparationOfDutiesExpositionTest extends TestCase
{
    /** @return array<string, mixed> */
    private function configurationLivree(): array
    {
        return require __DIR__.'/../../../../config/sod.php';
    }

    public function test_chaque_regle_declare_sa_cle_de_reglage_et_son_libelle(): void
    {
        $regles = $this->configurationLivree()['rules'];

        $this->assertNotEmpty($regles);

        foreach ($regles as $nom => $definition) {
            // Sans `setting`, la regle n'est pilotable par personne.
            $this->assertArrayHasKey('setting', $definition, "Règle {$nom} sans clé de réglage");
            $this->assertIsString($definition['setting']);
            $this->assertNotSame('', $definition['setting']);

            // Sans libellé, l'écran afficherait le nom technique de la règle.
            $this->assertArrayHasKey('label', $definition, "Règle {$nom} sans libellé");
            $this->assertNotSame('', trim((string) $definition['label']));

            // Le message de refus reste obligatoire : c'est lui que voit
            // l'utilisateur bloqué.
            $this->assertArrayHasKey('message', $definition, "Règle {$nom} sans message de refus");
        }
    }

    public function test_les_cles_de_reglage_sont_distinctes(): void
    {
        // Deux règles qui partagent une clé ne seraient plus indépendantes :
        // désactiver l'une désactiverait l'autre, sans que rien ne le dise.
        $cles = SeparationOfDutiesService::clesDeReglage();

        $this->assertSame($cles, array_values(array_unique($cles)));
    }

    public function test_l_ecran_recoit_exactement_les_regles_de_la_configuration(): void
    {
        $exposables = SeparationOfDutiesService::reglesExposables();
        $regles = $this->configurationLivree()['rules'];

        $this->assertCount(count($regles), $exposables);

        foreach ($exposables as $regle) {
            $this->assertArrayHasKey('cle', $regle);
            $this->assertArrayHasKey('label', $regle);
            $this->assertArrayHasKey('hint', $regle);
            $this->assertArrayHasKey('defaut', $regle);
            $this->assertIsBool($regle['defaut']);
        }
    }

    public function test_la_migration_seme_toutes_les_cles_exposees(): void
    {
        // Une case affichée dont la clé n'existe pas en base se coche, se
        // soumet, et n'est jamais enregistrée : la boucle d'enregistrement
        // ignore en silence toute clé absente de la table `settings`.
        $migration = file_get_contents(
            __DIR__.'/../../../../database/migrations/2026_09_15_002201_seed_reglages_separation_des_devoirs.php'
        );

        $this->assertIsString($migration);

        // La migration sème depuis le service, jamais depuis des chaînes
        // recopiées : c'est ce qui garantit qu'aucune règle ajoutée plus tard
        // ne sera oubliée.
        $this->assertStringContainsString(
            'SeparationOfDutiesService::reglesExposables()',
            $migration,
        );
    }

    public function test_la_permission_de_contournement_est_lue_depuis_la_configuration(): void
    {
        $this->assertSame(
            $this->configurationLivree()['bypass_permission'],
            SeparationOfDutiesService::permissionDeContournement(),
        );
    }
}
