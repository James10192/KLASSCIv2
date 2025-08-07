<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class ESBTPEmploiTemps extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_emploi_temps';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'titre',
        'classe_id',
        'annee_universitaire_id',
        'semestre',
        'date_debut',
        'date_fin',
        'is_active',
        'is_current',
        'created_by',
        'updated_by'
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'is_active' => 'boolean',
        'is_current' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Les événements de modèle.
     */
    protected static function boot()
    {
        parent::boot();

        // Avant la suppression (soft delete)
        static::deleting(function ($emploiTemps) {
            try {
                // Journaliser l'événement
                Log::info('Suppression de l\'emploi du temps ID: ' . $emploiTemps->id);

                // Récupérer toutes les séances de cours associées
                $seances = $emploiTemps->seances;

                if ($seances->count() > 0) {
                    Log::info('Nombre de séances associées: ' . $seances->count());

                    // Option 1: Supprimer toutes les séances associées
                    foreach ($seances as $seance) {
                        Log::info('Suppression de la séance ID: ' . $seance->id);
                        $seance->delete();
                    }

                    // Option 2 (alternative): Mettre à jour les séances pour qu'elles référencent un autre emploi du temps
                    // Cette option nécessiterait de trouver un emploi du temps alternatif
                    // et de mettre à jour les séances pour qu'elles le référencent
                }
            } catch (\Exception $e) {
                Log::error('Erreur lors de la suppression des séances associées: ' . $e->getMessage());
                Log::error('Trace: ' . $e->getTraceAsString());
                // Ne pas bloquer la suppression de l'emploi du temps
            }
        });
    }

    /**
     * Relation avec la classe associée à cet emploi du temps.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe()
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec l'année universitaire associée à cet emploi du temps.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function annee()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec les séances de cours de cet emploi du temps.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function seances()
    {
        return $this->hasMany(ESBTPSeanceCours::class, 'emploi_temps_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé l'emploi du temps.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour l'emploi du temps.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtenir les séances de cours groupées par jour de la semaine.
     *
     * @return array
     */
    public function getSeancesParJour()
    {
        $jours = [
            0 => 'Lundi',
            1 => 'Mardi',
            2 => 'Mercredi',
            3 => 'Jeudi',
            4 => 'Vendredi',
            5 => 'Samedi',
        ];

        $seancesParJour = [];

        foreach ($jours as $index => $jour) {
            $seancesParJour[$jour] = $this->seances()
                ->where('jour', $index)
                ->orderBy('heure_debut')
                ->get();
        }

        return $seancesParJour;
    }

    /**
     * Obtenir la période de validité de l'emploi du temps.
     *
     * @return string
     */
    public function getPeriodeValiditeAttribute()
    {
        $debut = $this->date_debut ? $this->date_debut->format('d/m/Y') : 'Non définie';
        $fin = $this->date_fin ? $this->date_fin->format('d/m/Y') : 'Non définie';

        return "Du {$debut} au {$fin}";
    }

    /**
     * Vérifie si l'emploi du temps est en cours de validité.
     *
     * @return bool
     */
    public function estEnCours()
    {
        $now = now();
        return $this->is_active
            && $this->date_debut <= $now
            && ($this->date_fin === null || $this->date_fin >= $now);
    }

    /**
     * Vérifie s'il y a des conflits d'horaire dans les séances de cours.
     *
     * @return array
     */
    public function verifierConflitsHoraire()
    {
        $conflicts = [];
        $seancesParJour = $this->getSeancesParJour();

        foreach ($seancesParJour as $jour => $seances) {
            for ($i = 0; $i < count($seances); $i++) {
                for ($j = $i + 1; $j < count($seances); $j++) {
                    $seance1 = $seances[$i];
                    $seance2 = $seances[$j];

                    // Vérifier si les horaires se chevauchent
                    if (($seance1->heure_debut < $seance2->heure_fin) &&
                        ($seance1->heure_fin > $seance2->heure_debut)) {
                        $conflicts[] = [
                            'jour' => $jour,
                            'seance1' => $seance1,
                            'seance2' => $seance2,
                        ];
                    }
                }
            }
        }

        return $conflicts;
    }

    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    public static function setAsCurrent($id)
    {
        // Récupérer la classe de l'emploi du temps
        $classeId = self::where('id', $id)->value('classe_id');

        if (!$classeId) {
            \Log::error('Impossible de définir l\'emploi du temps comme actuel: classe non trouvée', [
                'emploi_temps_id' => $id
            ]);
            return false;
        }

        // Désactiver tous les autres emplois du temps pour cette classe
        self::where('classe_id', $classeId)
            ->where('id', '!=', $id)
            ->update([
                'is_current' => false,
                'is_active' => false
            ]);

        // Activer cet emploi du temps
        return self::where('id', $id)->update([
            'is_current' => true,
            'is_active' => true
        ]);
    }

    /**
     * Vérifie si la période de l'emploi du temps correspond à une semaine (5 jours maximum).
     *
     * @return bool
     */
    public function estUneSemaine()
    {
        if (!$this->date_debut || !$this->date_fin) {
            return false;
        }

        // Calculer la différence en jours
        $diffJours = $this->date_debut->diffInDays($this->date_fin);

        // La différence doit être inférieure ou égale à 4 pour avoir 5 jours au maximum
        // (jour de début + 4 jours = 5 jours au total)
        return $diffJours <= 4;
    }

    /**
     * Génère les dates de la semaine courante (lundi au vendredi).
     *
     * @return array Tableau associatif avec les dates de début et de fin
     */
    public static function genererSemaineCourante()
    {
        $aujourdhui = now();

        // Trouver le lundi de la semaine courante
        $lundi = $aujourdhui->copy()->startOfWeek();

        // Trouver le vendredi de la semaine courante
        $vendredi = $lundi->copy()->addDays(4);

        return [
            'date_debut' => $lundi->format('Y-m-d'),
            'date_fin' => $vendredi->format('Y-m-d')
        ];
    }

    /**
     * Vérifie si la période de l'emploi du temps chevauche une autre période.
     *
     * @param int|null $excludeId ID de l'emploi du temps à exclure de la vérification
     * @return bool
     */
    public function chavauchePeriode($excludeId = null)
    {
        $query = self::where('classe_id', $this->classe_id)
                    ->where(function($q) {
                        $q->whereBetween('date_debut', [$this->date_debut, $this->date_fin])
                          ->orWhereBetween('date_fin', [$this->date_debut, $this->date_fin])
                          ->orWhere(function($q2) {
                              $q2->where('date_debut', '<=', $this->date_debut)
                                 ->where('date_fin', '>=', $this->date_fin);
                          });
                    });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
