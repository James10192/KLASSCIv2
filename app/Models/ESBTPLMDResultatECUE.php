<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use App\Services\LMD\EnseignantDeClasseLmd;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ESBTPLMDResultatECUE extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_resultats_ecues';

    protected $fillable = [
        'bulletin_id', 'resultat_ue_id', 'matiere_id', 'etudiant_id',
        'moyenne', 'credit', 'rang', 'enseignant_id', 'enseignant_snapshot_nom',
        'stat_min', 'stat_moy', 'stat_max',
        'note_session_normale', 'note_rattrapage', 'note_finale',
        'rattrapage_eligible', 'rattrapage_inscrit',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'moyenne' => 'decimal:2', 'credit' => 'integer', 'rang' => 'integer',
        'stat_min' => 'decimal:2', 'stat_moy' => 'decimal:2', 'stat_max' => 'decimal:2',
        'note_session_normale' => 'decimal:2', 'note_rattrapage' => 'decimal:2', 'note_finale' => 'decimal:2',
        'rattrapage_eligible' => 'boolean', 'rattrapage_inscrit' => 'boolean',
    ];

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

            if (! $bulletin || ! $resultat->matiere_id) return;

            $snapshot = static::resoudreEnseignantDuSemestre($bulletin, (int) $resultat->matiere_id);
            $resultat->enseignant_id = $snapshot['enseignant_id'];
            $resultat->enseignant_snapshot_nom = $snapshot['nom'] !== '' ? $snapshot['nom'] : null;
        });
    }

    /** @return array{enseignant_id: int|null, nom: string} */
    private static function resoudreEnseignantDuSemestre(ESBTPLMDBulletin $bulletin, int $matiereId): array
    {
        $classe = ESBTPClasse::find($bulletin->classe_id);
        if (! $classe) return ['enseignant_id' => null, 'nom' => ''];

        $resolution = app(EnseignantDeClasseLmd::class)->resoudre(
            $classe,
            $matiereId,
            (int) $bulletin->annee_universitaire_id,
            (int) $bulletin->semestre,
        );

        return [
            'enseignant_id' => $resolution['enseignant_id'],
            'nom' => static::nomEnseignantAvecTitre($resolution['enseignant']),
        ];
    }

    public function bulletin() { return $this->belongsTo(ESBTPLMDBulletin::class, 'bulletin_id'); }
    public function resultatUE() { return $this->belongsTo(ESBTPLMDResultatUE::class, 'resultat_ue_id'); }
    public function matiere() { return $this->belongsTo(ESBTPMatiere::class, 'matiere_id'); }
    public function etudiant() { return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id'); }
    public function enseignant() { return $this->belongsTo(User::class, 'enseignant_id'); }

    public function getEnseignantAfficheAttribute(): string
    {
        $bulletin = $this->relationLoaded('bulletin') ? $this->bulletin : $this->bulletin()->first();

        // Brouillon : toujours la résolution actuelle de CETTE classe.
        if ($bulletin && ! $bulletin->is_published && $this->matiere_id) {
            $courant = static::resoudreEnseignantDuSemestre($bulletin, (int) $this->matiere_id)['nom'];
            if ($courant !== '') return $courant;
        }

        $snapshot = trim((string) $this->enseignant_snapshot_nom);
        if ($snapshot !== '') return $snapshot;

        // Legacy : tenter la résolution par classe avant l'ancien enseignant_id.
        if ($bulletin && $this->matiere_id) {
            $resolu = static::resoudreEnseignantDuSemestre($bulletin, (int) $this->matiere_id)['nom'];
            if ($resolu !== '') return $resolu;
        }

        return static::nomEnseignantAvecTitre($this->enseignant);
    }

    /**
     * Le titre fait partie du nom figé sur le bulletin, sans modifier User::name.
     */
    private static function nomEnseignantAvecTitre(?User $enseignant): string
    {
        $nom = trim((string) ($enseignant?->name ?? ''));
        if ($nom === '') {
            return '';
        }

        $enseignant->loadMissing('teacherProfile');
        $titre = trim((string) ($enseignant->teacherProfile?->title ?? ''));
        if ($titre === '') {
            return $nom;
        }

        // Les noms legacy peuvent déjà porter le même titre, avec ou sans point.
        $prefixe = preg_quote(rtrim($titre, '.'), '/');
        if (preg_match('/^'.$prefixe.'\\.?(?:\\s|$)/iu', $nom) === 1) {
            return $nom;
        }

        return $titre.' '.$nom;
    }
}
