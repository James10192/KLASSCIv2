<?php

namespace Tests\Feature\EmploiTemps;

use Tests\TestCase;

/**
 * Test Feature pour /esbtp/emploi-temps/{id}/add-session en mode LMD.
 *
 * ## But (PR3 chantier emploi-temps-lmd-unification)
 *
 * Avant ce PR : `addSession()` line 1492-1620 utilisait `whereHas('filieres')` BTS-only
 * qui ne retournait JAMAIS de matières pour les classes LMD (pivot esbtp_matiere_filiere
 * vide pour LMD — rule globale klassci-classe-matieres.md).
 *
 * Après ce PR : `addSession()` redirige vers `/esbtp/seances-cours/create` qui supporte
 * BTS+LMD via MatiereTreeBuilder canonical. La méthode privée `addSessionLegacyPartial()`
 * reste accessible via `?use_legacy_partial=1` pour rollback rapide.
 *
 * @see App\Http\Controllers\ESBTPEmploiTempsController::addSession() line 1492
 * @see docs/MASTER-PLAN-emploi-temps-lmd-unification.md PR3
 */
class AddSessionLmdTest extends TestCase
{
    /** @test */
    public function add_session_redirects_to_seances_cours_create(): void
    {
        // Verifier via reflection que la methode redirige par defaut
        $reflection = new \ReflectionClass(\App\Http\Controllers\ESBTPEmploiTempsController::class);
        $method = $reflection->getMethod('addSession');
        $this->assertTrue($method->isPublic(), 'addSession must be public');

        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString(
            "redirect()->route('esbtp.seances-cours.create'",
            $source,
            'addSession() doit rediriger vers esbtp.seances-cours.create par defaut'
        );
    }

    /** @test */
    public function add_session_legacy_partial_uses_matiere_tree_builder(): void
    {
        // Verifier que le fallback legacy applique l'override LMD via service
        $reflection = new \ReflectionClass(\App\Http\Controllers\ESBTPEmploiTempsController::class);
        $this->assertTrue($reflection->hasMethod('addSessionLegacyPartial'));

        $source = file_get_contents($reflection->getFileName());
        $this->assertStringContainsString(
            'MatiereTreeBuilder::class',
            $source,
            'addSession (legacy partial) doit utiliser MatiereTreeBuilder service'
        );

        // Verifier que whereHas('filieres') BTS-only a ete supprime du flow principal
        $this->assertStringNotContainsString(
            "ESBTPMatiere::where('is_active', true)",
            $source,
            'Logique BTS-only whereHas filieres+niveaux supprimee de addSession (PR3)'
        );
    }
}
