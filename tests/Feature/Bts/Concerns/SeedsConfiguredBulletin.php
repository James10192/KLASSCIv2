<?php

namespace Tests\Feature\Bts\Concerns;

use App\Models\ESBTPBulletin;

/**
 * Helper de setup pour les tests Plan C qui appellent
 * BulletinService::genererDonneesBulletin(...).
 *
 * Le service exige (BulletinService.php:252) un ESBTPBulletin pré-existant
 * pour le triplet (etudiant, classe, periode, annee) portant :
 *   - config_matieres : JSON {generales:[...], techniques:[...]} non vide
 *   - professeurs     : JSON non vide (peut être {} mais doit être truthy)
 * sinon il lève \Exception('Configuration bulletin manquante ...').
 *
 * Ce helper crée ce bulletin minimal et valide. Il ne couple aucune matière
 * réelle car le early-check ne valide qu'un tableau d'ids non vide.
 *
 * BTS uniquement (aucun chemin LMD touché).
 */
trait SeedsConfiguredBulletin
{
    /**
     * Crée un ESBTPBulletin configuré minimal requis par genererDonneesBulletin.
     *
     * @param array<int> $generales  ids de matières générales (par défaut [1])
     * @param array<int> $techniques ids de matières techniques (par défaut [])
     * @param array<int|string, string> $professeurs map matiereId => nom (par défaut {})
     */
    protected function seedConfiguredBulletin(
        int $etudiantId,
        int $classeId,
        int $anneeUniversitaireId,
        string $periode = 'semestre1',
        array $generales = [1],
        array $techniques = [],
        array $professeurs = []
    ): ESBTPBulletin {
        $bulletin = ESBTPBulletin::create([
            'etudiant_id' => $etudiantId,
            'classe_id' => $classeId,
            'annee_universitaire_id' => $anneeUniversitaireId,
            'periode' => $periode,
            'config_matieres' => [
                'generales' => $generales,
                'techniques' => $techniques,
            ],
        ]);

        // `professeurs` n'est pas dans $fillable du modèle : set en attribut direct.
        // En prod c'est une chaîne JSON ('{}' par défaut). On stocke un JSON non vide.
        $bulletin->professeurs = empty($professeurs) ? '{}' : json_encode($professeurs);
        $bulletin->save();

        return $bulletin;
    }
}
