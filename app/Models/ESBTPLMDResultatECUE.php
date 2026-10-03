<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ESBTPLMDResultatECUE extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_resultats_ecues';

    protected $fillable = [
        'bulletin_id', 'resultat_ue_id', 'matiere_id', 'etudiant_id',
        'moyenne', 'credit', 'rang', 'enseignant_id', 'enseignant_snapshot_nom',
        'stat_min', 'stat_moy', 'stat_max',
        // PR10 rattrapage
        'note_session_normale', 'note_rattrapage', 'note_finale',
        'rattrapage_eligible', 'rattrapage_inscrit',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'moyenne' => 'decimal:2',
        'credit' => 'integer',
        'rang' => 'integer',
        'stat_min' => 'decimal:2',
        'stat_moy' => 'decimal:2',
        'stat_max' => 'decimal:2',
        // PR10 rattrapage
        'note_session_normale' => 'decimal:2',
        'note_rattrapage' => 'decimal:2',
        'note_finale' => 'decimal:2',
        'rattrapage_eligible' => 'boolean',
        'rattrapage_inscrit' => 'boolean',
    ];

    /** @var array<string, array{enseignant_id: int|null, nom: string}> */
    private static array $enseignantsSnapshot = [];

    protected static function booted(): void
    {
        static::saving(function (ESBTPLMDResultatECUE $resultat): void {
            $bulletin = $resultat->relationLoaded('bulletin')
                ? $resultat->bulletin
                : ($resultat->bulletin_id ? ESBTPLMDBulletin::find($resultat->bulletin_id) : null);

            if ($bulletin?->is_published) {
                $dirty = array_diff(array_keys($resultat->getDirty()), ['updated_at']);
                if (! $resultat->exists || $dirty !== []) {
                    throw ValidationException::withMessages([
                        'bulletin' => 'Ce bulletin LMD est publié : ses résultats ECUE et ses enseignants sont figés. Dépubliez-le avant toute correction.',
                    ]);
                }

                return;
            }

            if (! $bulletin || ! $resultat->matiere_id) {
                return;
            }

            $snapshot = static::resoudreEnseignantDuSemestre($bulletin, (int) $resultat->matiere_id);
            $resultat->enseignant_id = $snapshot['enseignant_id'];
            $resultat->enseignant_snapshot_nom = $snapshot['nom'] !== '' ? $snapshot['nom'] : null;
        });
    }

    private static function resoudreEnseignantDuSemestre(ESBTPLMDBulletin $bulletin, int $matiereId): array
    {
        $cacheKey = implode(':', [
            $bulletin->classe_id,
            $bulletin->annee_universitaire_id,
            $bulletin->semestre,
            $matiereId,
        ]);

        if (! app()->runningInConsole() && array_key_exists($cacheKey, static::$enseignantsSnapshot)) {
            return static::$enseignantsSnapshot[$cacheKey];
        }

        $semestre = (int) $bulletin->semestre;
        $periodes = [
            (string) $semestre,
            'semestre'.$semestre,
            'S'.$semestre,
            'Semestre '.$semestre,
            'semestre '.$semestre,
        ];

        $evaluations = ESBTPEvaluation::query()
            ->where('matiere_id', $matiereId)
            ->where('classe_id', $bulletin->classe_id)
            ->where('annee_universitaire_id', $bulletin->annee_universitaire_id)
            ->whereIn('periode', $periodes)
            ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED)
            ->where(function ($query) {
                $query->whereNotNull('enseignant_id')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('enseignant_externe_nom')
                            ->where('enseignant_externe_nom', '<>', '');
                    });
            })
            ->with('enseignant:id,name')
            ->orderBy('date_evaluation')
            ->orderBy('id')
            ->get();

        $noms = $evaluations
            ->map(fn (ESBTPEvaluation $evaluation): string => trim((string) (
                $evaluation->enseignant?->name
                ?: $evaluation->enseignant_externe_nom
                ?: ''
            )))
            ->filter()
            ->unique(fn (string $nom): string => mb_strtolower($nom, 'UTF-8'))
            ->values();

        $snapshot = [
            'enseignant_id' => $evaluations->first(fn (ESBTPEvaluation $evaluation) => $evaluation->enseignant_id !== null)?->enseignant_id,
            'nom' => $noms->implode(' / '),
        ];

        if (! app()->runningInConsole()) {
            static::$enseignantsSnapshot[$cacheKey] = $snapshot;
        }

        return $snapshot;
    }

    public function bulletin()
    {
        return $this->belongsTo(ESBTPLMDBulletin::class, 'bulletin_id');
    }

    public function resultatUE()
    {
        return $this->belongsTo(ESBTPLMDResultatUE::class, 'resultat_ue_id');
    }

    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function enseignant()
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    public function getEnseignantAfficheAttribute(): string
    {
        $snapshot = trim((string) $this->enseignant_snapshot_nom);
        if ($snapshot !== '') {
            return $snapshot;
        }

        if ($this->enseignant?->name) {
            return trim((string) $this->enseignant->name);
        }

        $bulletin = $this->relationLoaded('bulletin') ? $this->bulletin : $this->bulletin()->first();
        if (! $bulletin || ! $this->matiere_id) {
            return '';
        }

        return static::resoudreEnseignantDuSemestre($bulletin, (int) $this->matiere_id)['nom'];
    }
}
