<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ESBTPMatiereFilierNiveau extends Model
{
    protected $table = 'esbtp_matiere_filiere_niveau';

    public const TRONC_COMMUN = 'tronc_commun';
    public const SPECIALITE = 'specialite';

    protected $fillable = [
        'matiere_id',
        'filiere_id',
        'niveau_etude_id',
        'classification',
        'ordre_bulletin',
        'semestre',
        'semestre_renseigne',
    ];

    /**
     * `ordre_bulletin` et `semestre` sont nullables : le cast `integer` les
     * laisse a null, il ne les ramene pas a 0. La distinction compte, un rang
     * nul valant « non defini » et non « premier ».
     */
    protected $casts = [
        'ordre_bulletin' => 'integer',
        'semestre' => 'integer',
        'semestre_renseigne' => 'boolean',
    ];

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveauEtude()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_etude_id');
    }

    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function scopeForCombo($query, $filiereId, $niveauId)
    {
        return $query->where('filiere_id', $filiereId)->where('niveau_etude_id', $niveauId);
    }

    public static function matiereIdsForCombo($filiereId, $niveauId)
    {
        return static::forCombo($filiereId, $niveauId)->pluck('matiere_id');
    }

    /**
     * Matières de spécialité (classées `specialite`) : exclues des bulletins TC.
     */
    public function scopeSpecialite($query)
    {
        return $query->where('classification', self::SPECIALITE);
    }

    /**
     * Matières remontant au bulletin de tronc commun : classées `tronc_commun`
     * OU non classées (null = comportement historique, non-régressif).
     */
    public function scopeNotSpecialite($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('classification')->orWhere('classification', self::TRONC_COMMUN);
        });
    }

    /**
     * IDs des matières `specialite` d'un combo (filière, niveau).
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public static function specialiteMatiereIdsForCombo($filiereId, $niveauId)
    {
        return static::forCombo($filiereId, $niveauId)->specialite()->pluck('matiere_id');
    }

    /**
     * Carte matiere_id => classification (tronc_commun|specialite|null) pour un combo.
     *
     * @return array<int, string|null>
     */
    public static function classificationMapForCombo($filiereId, $niveauId): array
    {
        return static::forCombo($filiereId, $niveauId)
            ->pluck('classification', 'matiere_id')
            ->all();
    }

    /**
     * Combien de matieres BTS actives porte ce couple.
     *
     * Le nom dit « bts » parce que le compte l'est : une ECUE LMD ayant une
     * ligne dans ce pivot gonflait le « Total matieres » du planning et faussait
     * le denominateur du taux de configuration. Un compteur dont la portee ne
     * se lit pas dans son nom rend un chiffre faux sans que personne ne cherche.
     */
    public static function btsMatiereCountForCombo($filiereId, $niveauId)
    {
        return static::forCombo($filiereId, $niveauId)
            ->whereHas('matiere', fn($q) => $q->where('is_active', true)->btsOnly())
            ->count();
    }
}
