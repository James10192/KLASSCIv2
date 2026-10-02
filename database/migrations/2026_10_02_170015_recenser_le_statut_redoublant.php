<?php

use App\Domain\Inscriptions\RecensementDesRedoublants;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

/**
 * Pose le statut redoublant déduit sur toutes les inscriptions existantes, au
 * déploiement, sans attendre qu'on y pense école par école.
 *
 * Jusqu'ici la colonne `is_redoublant` n'était jamais écrite (absente des
 * champs remplissables) : toute inscription vaut « non ». Laisser une
 * secrétaire confirmer ces valeurs avant ce recensement figerait un « non »
 * faux comme une décision humaine.
 *
 * Rejouable et prudent : rien de ce qu'une personne a confirmé ou corrigé
 * n'est réécrit. Si le recensement échoue, la migration ne bloque pas le
 * déploiement : il se relance par `inscriptions:recenser-redoublants --apply`
 * ou `POST /api/cli/inscriptions/redoublants/recenser`.
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            app(RecensementDesRedoublants::class)->executer(true);
        } catch (\Throwable $e) {
            Log::error('Recensement du statut redoublant non fait au déploiement : à relancer', [
                'erreur' => $e->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        // Rien à défaire : la colonne de la migration précédente disparaît avec elle.
    }
};
