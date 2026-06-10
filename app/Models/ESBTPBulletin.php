<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Contracts\Auditable;

class ESBTPBulletin extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    /**
     * Colonnes auditées (whitelist — résultats académiques officiels).
     *
     * @var array
     */
    protected $auditInclude = [
        'etudiant_id',
        'classe_id',
        'annee_universitaire_id',
        'periode',
        'moyenne_generale',
        'rang',
        'effectif_classe',
        'mention',
        'appreciation_generale',
        'decision_conseil',
        'is_published',
        'signature_responsable',
        'signature_directeur',
        'signature_parent',
        'date_signature_responsable',
        'date_signature_directeur',
        'date_signature_parent',
        'note_assiduite',
        'absences_justifiees',
        'absences_non_justifiees',
        'total_absences',
    ];

    /**
     * Événements à auditer.
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('not_archived', function (Builder $builder) {
            $builder->whereNull($builder->getModel()->getTable() . '.archived_at');
        });
    }

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'esbtp_bulletins';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'etudiant_id',
        'classe_id',
        'annee_universitaire_id',
        'periode',
        'moyenne_generale',
        'rang',
        'effectif_classe',
        'mention',
        'appreciation_generale',
        'config_matieres',
        'decision_conseil',
        'user_id',
        'signature_responsable',
        'signature_directeur',
        'signature_parent',
        'date_signature_responsable',
        'date_signature_directeur',
        'date_signature_parent',
        'is_published',
        'absences_justifiees',
        'absences_non_justifiees',
        'total_absences',
        'note_assiduite',
        'details_absences',
    ];

    /**
     * Les attributs qui doivent être convertis en types natifs.
     *
     * @var array
     */
    protected $casts = [
        'moyenne_generale' => 'float',
        'rang' => 'integer',
        'effectif_classe' => 'integer',
        'is_published' => 'boolean',
        'signature_responsable' => 'boolean',
        'signature_directeur' => 'boolean',
        'signature_parent' => 'boolean',
        'date_signature_responsable' => 'datetime',
        'date_signature_directeur' => 'datetime',
        'date_signature_parent' => 'datetime',
        'config_matieres' => 'json',
        'absences_justifiees' => 'float',
        'absences_non_justifiees' => 'float',
        'total_absences' => 'float',
        'note_assiduite' => 'float',
        'details_absences' => 'json',
    ];

    /**
     * Relation avec l'étudiant associé à ce bulletin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    /**
     * Relation avec la classe associée à ce bulletin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    /**
     * Relation avec l'année universitaire associée à ce bulletin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    /**
     * Relation avec les résultats par matière de ce bulletin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function resultatsMatiere(): HasMany
    {
        return $this->hasMany(ESBTPResultatMatiere::class, 'bulletin_id');
    }

    /**
     * Alias pour la relation resultatsMatiere.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function resultats(): HasMany
    {
        return $this->resultatsMatiere();
    }

    /**
     * Relation avec les détails des matières de ce bulletin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function details()
    {
        return $this->hasMany(ESBTPBulletinDetail::class, 'bulletin_id');
    }

    /**
     * Relation avec l'utilisateur qui a créé le bulletin.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Calcule et met à jour la moyenne générale du bulletin.
     *
     * @return void
     */
    public function calculerMoyenneGenerale(): void
    {
        $resultats = $this->resultats;

        if ($resultats->isEmpty()) {
            $this->moyenne_generale = null;
            $this->save();
            return;
        }

        $sommePoints = 0;
        $sommeCoefficients = 0;

        foreach ($resultats as $resultat) {
            if ($resultat->moyenne !== null) {
                $sommePoints += $resultat->moyenne * $resultat->coefficient;
                $sommeCoefficients += $resultat->coefficient;
            }
        }

        $this->moyenne_generale = $sommeCoefficients > 0 ? $sommePoints / $sommeCoefficients : null;
        $this->save();
    }

    /**
     * Calcule et définit la mention en fonction de la moyenne générale.
     *
     * @return void
     */
    public function calculerMention(): void
    {
        if ($this->moyenne_generale === null) {
            $this->mention = null;
        } elseif ($this->moyenne_generale >= 16) {
            $this->mention = 'Excellent';
        } elseif ($this->moyenne_generale >= 14) {
            $this->mention = 'Très Bien';
        } elseif ($this->moyenne_generale >= 12) {
            $this->mention = 'Bien';
        } elseif ($this->moyenne_generale >= 10) {
            $this->mention = 'Assez Bien';
        } elseif ($this->moyenne_generale >= 8) {
            $this->mention = 'Passable';
        } else {
            $this->mention = 'Insuffisant';
        }

        $this->save();
    }

    /**
     * Calcule et définit la décision en fonction de la moyenne générale.
     *
     * @return void
     */
    public function determinerDecision()
    {
        $moyenne = $this->moyenne_generale;

        if ($moyenne >= 10) {
            return 'Admis(e)';
        } else {
            return 'Ajourné(e)';
        }
    }

    /**
     * Met à jour le rang de l'étudiant dans sa classe.
     *
     * @return void
     */
    public function calculerRang(): void
    {
        // Tronc commun : pour un bulletin S1 d'un étudiant orienté, la cohorte de rang
        // est la classe TC qui portait les notes du S1 (pas la spécialité courante).
        $cohorteClasseId = app(\App\Domain\BtsTroncCommun\BtsBulletinCohortResolver::class)
            ->resolveRankCohortClasseId($this);

        // Get all bulletins from the cohort class, period and academic year
        $bulletins = self::where('classe_id', $cohorteClasseId)
            ->where('annee_universitaire_id', $this->annee_universitaire_id)
            ->where('periode', $this->periode)
            ->whereNotNull('moyenne_generale')
            ->orderByDesc('moyenne_generale')
            ->get();

        // Keep displayed class size aligned with validated enrollments, not only generated bulletins.
        $this->effectif_classe = ESBTPInscription::where('classe_id', $cohorteClasseId)
            ->where('annee_universitaire_id', $this->annee_universitaire_id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->distinct('etudiant_id')
            ->count('etudiant_id');

        // Find student's rank. Quand la cohorte diffère de la classe du bulletin
        // (étudiant orienté), le bulletin courant n'est pas dans la liste cohorte :
        // on classe alors par nombre de moyennes strictement supérieures + 1.
        $rang = null;
        foreach ($bulletins as $index => $bulletin) {
            if ($bulletin->id === $this->id) {
                $rang = $index + 1;
                break;
            }
        }

        if ($rang === null) {
            $rang = $bulletins->where('moyenne_generale', '>', $this->moyenne_generale ?? 0)->count() + 1;
        }

        $this->rang = $rang;

        $this->save();
    }

    /**
     * Vérifie si le bulletin est signé par tous les responsables.
     *
     * @return bool
     */
    public function isFullySigned()
    {
        return $this->signature_directeur &&
               $this->signature_responsable &&
               $this->signature_parent;
    }

    /**
     * Vérifie si le bulletin est signé par un responsable spécifique.
     *
     * @param string $role directeur|responsable|parent
     * @return bool
     */
    public function isSignedBy($role)
    {
        $field = 'signature_' . $role;
        return $this->$field;
    }

    /**
     * Signe le bulletin par un responsable.
     *
     * @param string $role directeur|responsable|parent
     * @return void
     */
    public function signer($role)
    {
        $signatureField = 'signature_' . $role;
        $dateField = 'date_signature_' . $role;

        $this->$signatureField = true;
        $this->$dateField = now();
        $this->save();
    }

    public function getAbsencesJustifieesAttribute()
    {
        if (!$this->etudiant || !$this->classe_id) {
            return 0;
        }
        return $this->etudiant->absences()
            ->where('justified', true)
            ->whereHas('matiere', function ($query) {
                $query->whereHas('classes', function ($q) {
                    $q->where('classe_id', $this->classe_id);
                });
            })
            ->sum('hours');
    }

    public function getAbsencesNonJustifieesAttribute()
    {
        if (!$this->etudiant || !$this->classe_id) {
            return 0;
        }
        return $this->etudiant->absences()
            ->where('justified', false)
            ->whereHas('matiere', function ($query) {
                $query->whereHas('classes', function ($q) {
                    $q->where('classe_id', $this->classe_id);
                });
            })
            ->sum('hours');
    }

    public function getMoyenneEnseignementGeneralAttribute()
    {
        $resultats = $this->resultats()
            ->whereHas('matiere', function ($query) {
                $query->where('type', 'general');
            })
            ->get();

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($resultats as $resultat) {
            $totalPoints += $resultat->moyenne * $resultat->coefficient;
            $totalCoefficients += $resultat->coefficient;
        }

        return $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : 0;
    }

    public function getMoyenneEnseignementTechniqueAttribute()
    {
        $resultats = $this->resultats()
            ->whereHas('matiere', function ($query) {
                $query->where('type', 'technique');
            })
            ->get();

        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($resultats as $resultat) {
            $totalPoints += $resultat->moyenne * $resultat->coefficient;
            $totalCoefficients += $resultat->coefficient;
        }

        return $totalCoefficients > 0 ? round($totalPoints / $totalCoefficients, 2) : 0;
    }

    /**
     * Get the total absences for this bulletin's period.
     */
    public function getTotalAbsences(): int
    {
        return ESBTPAbsence::where('etudiant_id', $this->etudiant_id)
            ->whereHas('matiere', function ($query) {
                $query->whereHas('classes', function ($q) {
                    $q->where('classe_id', $this->classe_id);
                });
            })
            ->where('date', '>=', $this->anneeUniversitaire->date_debut)
            ->where('date', '<=', $this->anneeUniversitaire->date_fin)
            ->sum('hours');
    }

    /**
     * Get the justified absences for this bulletin's period.
     */
    public function getAbsencesJustifiees(): int
    {
        return ESBTPAbsence::where('etudiant_id', $this->etudiant_id)
            ->whereHas('matiere', function ($query) {
                $query->whereHas('classes', function ($q) {
                    $q->where('classe_id', $this->classe_id);
                });
            })
            ->where('date', '>=', $this->anneeUniversitaire->date_debut)
            ->where('date', '<=', $this->anneeUniversitaire->date_fin)
            ->where('justified', true)
            ->sum('hours');
    }

    /**
     * Get the unjustified absences for this bulletin's period.
     */
    public function getAbsencesNonJustifiees(): int
    {
        return ESBTPAbsence::where('etudiant_id', $this->etudiant_id)
            ->whereHas('matiere', function ($query) {
                $query->whereHas('classes', function ($q) {
                    $q->where('classe_id', $this->classe_id);
                });
            })
            ->where('date', '>=', $this->anneeUniversitaire->date_debut)
            ->where('date', '<=', $this->anneeUniversitaire->date_fin)
            ->where('justified', false)
            ->sum('hours');
    }

    /**
     * Get results grouped by subject type (general or technical).
     */
    public function getResultatsGroupes(): Collection
    {
        return $this->resultatsMatiere->groupBy(function ($resultat) {
            return $resultat->matiere->type;
        });
    }

    /**
     * Calculate the average for a specific subject type.
     */
    public function calculerMoyenneParType(string $type): ?float
    {
        $resultats = $this->resultats->filter(function ($resultat) use ($type) {
            return $resultat->matiere->type === $type;
        });

        if ($resultats->isEmpty()) {
            return null;
        }

        $sommePoints = 0;
        $sommeCoefficients = 0;

        foreach ($resultats as $resultat) {
            if ($resultat->moyenne !== null) {
                $sommePoints += $resultat->moyenne * $resultat->coefficient;
                $sommeCoefficients += $resultat->coefficient;
            }
        }

        return $sommeCoefficients > 0 ? $sommePoints / $sommeCoefficients : null;
    }

    public function getResultatsParTypeAttribute()
    {
        return $this->resultats->groupBy(function ($resultat) {
            return $resultat->matiere->type;
        });
    }

}
