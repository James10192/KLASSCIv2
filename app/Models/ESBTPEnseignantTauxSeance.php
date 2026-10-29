<?php

namespace App\Models;

use App\Enums\TypeSeance;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Taux horaire d'un enseignant pour un type de séance donné (CM/TD/TP…).
 *
 * En LMD (Ephrata), chaque type de séance a un taux horaire distinct par
 * professeur. Le fallback sur esbtp_teachers.taux_horaire (taux défaut) reste
 * la source de vérité quand aucune ligne spécifique n'existe.
 *
 * @see App\Enums\TypeSeance — valeurs canoniques du champ type_seance
 */
class ESBTPEnseignantTauxSeance extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'esbtp_enseignant_taux_seance';

    protected $auditInclude = [
        'teacher_id',
        'type_seance',
        'taux_horaire',
    ];

    /**
     * Les evenements reellement audites : les MUTATIONS, pas les lectures.
     *
     * `config/audit.php` active aussi `retrieved`, ce qui fait ecrire une ligne
     * dans `audits` a chaque fois qu'un modele est LU. Vingt et un modeles s'en
     * protegent deja par cette meme propriete ; ceux-ci ne le faisaient pas.
     *
     * Le cout n'etait pas theorique : c'est par ce canal que la table `audits` a
     * enfle au point que la page qui la consulte ne repondait plus.
     *
     * Ce qui reste trace : creation, modification, suppression, restauration.
     * La conservation OHADA porte sur les mutations, pas sur les consultations.
     */
    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];
    protected $fillable = [
        'teacher_id',
        'type_seance',
        'taux_horaire',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'taux_horaire' => 'decimal:2',
        'type_seance'  => TypeSeance::class,
    ];

    public function teacher()
    {
        return $this->belongsTo(ESBTPTeacher::class, 'teacher_id');
    }
}
