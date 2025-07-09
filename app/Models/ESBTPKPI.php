<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ESBTPKPI extends Model
{
    use HasFactory;

    protected $table = 'esbtp_kpis';

    protected $fillable = [
        'nom',
        'valeur',
        'unite',
        'periode',
        'date_calcul',
        'type',
        'metadata'
    ];

    protected $casts = [
        'date_calcul' => 'date',
        'metadata' => 'json',
        'valeur' => 'decimal:2'
    ];

    /**
     * Scopes
     */
    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeParPeriode($query, $periode)
    {
        return $query->where('periode', $periode);
    }

    public function scopeParDate($query, $date)
    {
        return $query->where('date_calcul', $date);
    }

    public function scopeRecent($query, $jours = 30)
    {
        return $query->where('date_calcul', '>=', now()->subDays($jours));
    }

    public function scopeParNom($query, $nom)
    {
        return $query->where('nom', $nom);
    }

    public function scopeOrdreDateDesc($query)
    {
        return $query->orderBy('date_calcul', 'desc');
    }

    /**
     * Accessors
     */
    public function getValeurFormatteeAttribute()
    {
        if ($this->unite === 'FCFA') {
            return number_format($this->valeur, 0, ',', ' ') . ' FCFA';
        }

        if ($this->unite === '%') {
            return number_format($this->valeur, 2, ',', ' ') . '%';
        }

        return number_format($this->valeur, 2, ',', ' ') . ' ' . $this->unite;
    }

    public function getTypeFormatteAttribute()
    {
        $types = [
            'recette' => 'Recette',
            'depense' => 'Dépense',
            'performance' => 'Performance',
            'ratio' => 'Ratio'
        ];

        return $types[$this->type] ?? $this->type;
    }

    public function getPeriodeFormateeAttribute()
    {
        $periodes = [
            'jour' => 'Journalier',
            'semaine' => 'Hebdomadaire',
            'mois' => 'Mensuel',
            'trimestre' => 'Trimestriel',
            'annee' => 'Annuel'
        ];

        return $periodes[$this->periode] ?? $this->periode;
    }

    public function getDateCalculFormatteeAttribute()
    {
        return $this->date_calcul->format('d/m/Y');
    }

    public function getNomCompletAttribute()
    {
        return $this->nom . ' (' . $this->periode_formattee . ')';
    }

    /**
     * Méthodes statiques pour récupération des KPIs
     */
    public static function getKPIParNomEtPeriode($nom, $periode, $date = null)
    {
        $query = self::where('nom', $nom)->where('periode', $periode);
        
        if ($date) {
            $query->where('date_calcul', $date);
        } else {
            $query->latest('date_calcul');
        }

        return $query->first();
    }

    public static function getEvolutionKPI($nom, $periode, $nombrePeriodes = 12)
    {
        return self::where('nom', $nom)
                  ->where('periode', $periode)
                  ->orderBy('date_calcul', 'desc')
                  ->limit($nombrePeriodes)
                  ->get()
                  ->reverse()
                  ->values();
    }

    public static function getKPIsParType($type, $date = null)
    {
        $query = self::where('type', $type);
        
        if ($date) {
            $query->where('date_calcul', $date);
        } else {
            // Récupère les KPIs les plus récents pour chaque nom
            $query->whereIn('id', function($subQuery) use ($type) {
                $subQuery->select(DB::raw('MAX(id)'))
                         ->from('esbtp_kpis')
                         ->where('type', $type)
                         ->groupBy('nom', 'periode');
            });
        }

        return $query->get();
    }

    public static function getTendance($nom, $periode, $nombrePeriodes = 3)
    {
        $kpis = self::where('nom', $nom)
                   ->where('periode', $periode)
                   ->orderBy('date_calcul', 'desc')
                   ->limit($nombrePeriodes)
                   ->get();

        if ($kpis->count() < 2) {
            return ['tendance' => 'stable', 'variation' => 0];
        }

        $recent = $kpis->first()->valeur;
        $ancien = $kpis->last()->valeur;

        if ($ancien == 0) {
            return ['tendance' => 'stable', 'variation' => 0];
        }

        $variation = (($recent - $ancien) / $ancien) * 100;

        $tendance = 'stable';
        if ($variation > 5) {
            $tendance = 'hausse';
        } elseif ($variation < -5) {
            $tendance = 'baisse';
        }

        return [
            'tendance' => $tendance,
            'variation' => round($variation, 2)
        ];
    }

    /**
     * Méthodes métier
     */
    public function comparerAvecPrecedent()
    {
        $precedent = self::where('nom', $this->nom)
                        ->where('periode', $this->periode)
                        ->where('date_calcul', '<', $this->date_calcul)
                        ->orderBy('date_calcul', 'desc')
                        ->first();

        if (!$precedent) {
            return null;
        }

        $difference = $this->valeur - $precedent->valeur;
        $pourcentage = $precedent->valeur != 0 ? 
            ($difference / $precedent->valeur) * 100 : 0;

        return [
            'precedent' => $precedent,
            'difference' => $difference,
            'pourcentage' => round($pourcentage, 2),
            'evolution' => $difference > 0 ? 'positive' : ($difference < 0 ? 'negative' : 'stable')
        ];
    }

    public function estAuDessusSeuil($seuil)
    {
        return $this->valeur >= $seuil;
    }

    public function estEnDessousSeuil($seuil)
    {
        return $this->valeur <= $seuil;
    }

    public function getClasseCouleur()
    {
        $metadata = $this->metadata ?? [];
        
        if (isset($metadata['seuils'])) {
            $seuils = $metadata['seuils'];
            
            if (isset($seuils['critique']) && $this->valeur <= $seuils['critique']) {
                return 'danger';
            }
            
            if (isset($seuils['alerte']) && $this->valeur <= $seuils['alerte']) {
                return 'warning';
            }
            
            if (isset($seuils['objectif']) && $this->valeur >= $seuils['objectif']) {
                return 'success';
            }
        }

        return 'primary';
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kpi) {
            // Auto-génération de la date si non définie
            if (!$kpi->date_calcul) {
                $kpi->date_calcul = now()->format('Y-m-d');
            }
        });
    }
}
