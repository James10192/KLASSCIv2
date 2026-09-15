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

    public function test_chaque_cle_exposee_est_semee_par_une_migration(): void
    {
        // Une case affichée dont la clé n'existe pas en base se coche, se
        // soumet, et n'est jamais enregistrée : la boucle d'enregistrement
        // ignore en silence toute clé absente de la table `settings`. C'est une
        // panne muette, et c'est celle que ce contrôle attrape — au moment où
        // quelqu'un ajoute une quatrième règle sans sa migration.
        //
        // On cherche la clé dans TOUTES les migrations, pas dans une seule :
        // une règle ajoutée plus tard aura légitimement la sienne.
        $migrations = glob(__DIR__.'/../../../../database/migrations/*.php') ?: [];
        $this->assertNotEmpty($migrations);

        $contenu = '';
        foreach ($migrations as $fichier) {
            $contenu .= (string) file_get_contents($fichier);
        }

        foreach (SeparationOfDutiesService::clesDeReglage() as $cle) {
            $this->assertStringContainsString(
                "'".$cle."'",
                $contenu,
                "La règle « {$cle} » n'est semée par aucune migration : la case s'affichera sans jamais s'enregistrer.",
            );
        }
    }

    /**
     * Le contrôle qui manquait, et sans lequel tout le reste était décoratif.
     *
     * `config('sod.rules.lmd.jury.publish.enabled')` ne peut pas marcher :
     * `Arr::get()` découpe sur les points et cherche un tableau imbriqué
     * `rules → lmd → jury → publish`, alors que la clé est littéralement
     * `'lmd.jury.publish'`, en un seul morceau. Il rendait donc toujours le
     * défaut — `false` —, `violation()` sortait au premier garde, et les trois
     * règles OHADA n'ont jamais rien empêché. Aucune erreur, aucun journal.
     *
     * Ce test échoue sur l'ancien code et passe sur le nouveau. Les précédents,
     * qui ne lisaient `config('sod.rules')` que sans sous-chemin, passaient dans
     * les deux cas : c'est exactement pourquoi ils n'ont rien vu.
     */
    public function test_les_trois_regles_livrees_sont_reellement_actives(): void
    {
        $service = new SeparationOfDutiesService;

        foreach (array_keys($this->configurationLivree()['rules']) as $regle) {
            $this->assertTrue(
                $service->enabled($regle),
                "La règle « {$regle} » est déclarée active et ne l'est pas : elle n'empêchera rien.",
            );
        }
    }

    public function test_une_regle_inconnue_reste_inactive(): void
    {
        $this->assertFalse((new SeparationOfDutiesService)->enabled('regle.qui.n.existe.pas'));
    }

    public function test_chaque_regle_rend_son_propre_message_de_refus(): void
    {
        // Un message générique signale que la définition n'a pas été atteinte —
        // c'est le symptôme exact du chemin pointé qui échoue.
        $service = new SeparationOfDutiesService;

        foreach ($this->configurationLivree()['rules'] as $regle => $definition) {
            $this->assertSame($definition['message'], $service->messageDeRefus($regle));
        }

        $this->assertSame(
            'Separation des devoirs requise pour cette action.',
            $service->messageDeRefus('regle.qui.n.existe.pas'),
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
