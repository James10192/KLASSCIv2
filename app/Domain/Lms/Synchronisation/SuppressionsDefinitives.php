<?php

namespace App\Domain\Lms\Synchronisation;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPTeacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Trace les suppressions DEFINITIVES pour la synchronisation LMS.
 *
 * Une suppression douce n'a pas besoin de trace : la ligne reste en base, sa
 * date de modification avance, et elle part dans son flux marquee `supprime`.
 * Une suppression definitive (purge de la corbeille) fait disparaitre la
 * ligne : seule cette table dit encore au LMS qu'elle a existe.
 *
 * Ne voit pas un `DB::table(...)->delete()` : aucun evenement n'est emis.
 */
final class SuppressionsDefinitives
{
    private const MODELES = [
        ESBTPClasse::class => 'classe',
        ESBTPMatiere::class => 'matiere',
        ESBTPEtudiant::class => 'etudiant',
        ESBTPTeacher::class => 'enseignant',
        ESBTPInscription::class => 'inscription',
        ESBTPSeanceCours::class => 'seance',
    ];

    public static function ecouter(): void
    {
        foreach (self::MODELES as $modele => $type) {
            $noter = fn (Model $m) => self::noter($type, (int) $m->getKey());

            // Sans suppression douce (ESBTPTeacher), toute suppression est definitive.
            in_array(SoftDeletes::class, class_uses_recursive($modele), true)
                ? $modele::forceDeleted($noter)
                : $modele::deleted($noter);
        }
    }

    private static function noter(string $type, int $id): void
    {
        try {
            DB::table('lms_suppressions')->insert([
                'type' => $type,
                'objet_id' => $id,
                // Ecrit par l'application, pas par MySQL : les deux horloges
                // peuvent differer (rule adminklassci-tenant-management).
                'supprime_le' => now(),
            ]);
        } catch (Throwable $e) {
            // Une trace manquee ne doit pas empecher la purge ; elle se dit.
            Log::warning('LMS : suppression definitive non tracee', [
                'type' => $type, 'id' => $id, 'erreur' => $e->getMessage(),
            ]);
        }
    }
}
