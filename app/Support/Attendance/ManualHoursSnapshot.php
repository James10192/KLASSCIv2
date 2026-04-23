<?php

namespace App\Support\Attendance;

use App\Models\ESBTPAttendanceManualHours;
use Illuminate\Support\Collection;

/**
 * Vue immuable des heures manuelles d'un étudiant pour (année, période).
 *
 * Sépare proprement les heures saisies par matière de l'éventuelle ligne
 * "globale" (sans matière). Les consommateurs (BulletinService,
 * ESBTPAbsenceService, vues) ne manipulent plus le modèle Eloquent
 * directement et raisonnent sur la sémantique : "ai-je du manuel pour
 * cette matière ? sinon ai-je du manuel global ?".
 *
 * Règle de priorité bulletin appliquée côté consommateurs :
 *
 *   per-matière  >  global  >  séances
 *
 * Le global ne ventile jamais artificiellement ses heures sur les
 * matières : il est affiché en valeur absolue en en-tête bulletin quand
 * aucune per-matière n'existe pour l'étudiant.
 */
final class ManualHoursSnapshot
{
    /**
     * @param  Collection<int, ESBTPAttendanceManualHours>  $perMatiere  indexée par matiere_id
     */
    public function __construct(
        public readonly Collection $perMatiere,
        public readonly ?ESBTPAttendanceManualHours $global,
    ) {
    }

    public static function empty(): self
    {
        return new self(collect(), null);
    }

    public function hasAnything(): bool
    {
        return $this->perMatiere->isNotEmpty() || $this->global !== null;
    }

    public function hasMatiere(int $matiereId): bool
    {
        return $this->perMatiere->has($matiereId);
    }

    public function forMatiere(int $matiereId): ?ESBTPAttendanceManualHours
    {
        return $this->perMatiere->get($matiereId);
    }

    public function matiereIdsWithManual(): array
    {
        return $this->perMatiere->keys()->all();
    }

    /**
     * Source effective pour une matière donnée selon la règle de priorité.
     * Ne retourne JAMAIS la ligne globale ici — la ventilation par matière
     * depuis une ligne globale ne serait qu'une moyenne artificielle. La
     * ligne globale se consomme explicitement via `$snapshot->global`.
     */
    public function effectiveForMatiere(int $matiereId): ?ESBTPAttendanceManualHours
    {
        return $this->forMatiere($matiereId);
    }
}
