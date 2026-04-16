<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ESBTPRelance extends Model
{
    use HasFactory;

    protected $table = 'esbtp_relances';

    protected $fillable = [
        'etudiant_id',
        'facture_id',
        'type',
        'niveau',
        'template_utilise',
        'contenu_message',
        'date_envoi',
        'statut',
        'response_data'
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
        'response_data' => 'json'
    ];

    /**
     * Relations
     */
    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function facture()
    {
        return $this->belongsTo(ESBTPFacture::class, 'facture_id');
    }

    /**
     * Scopes
     */
    public function scopePlanifiee($query)
    {
        return $query->where('statut', 'planifiee');
    }

    public function scopeEnvoyee($query)
    {
        return $query->where('statut', 'envoyee');
    }

    public function scopeEchec($query)
    {
        return $query->where('statut', 'echec');
    }

    public function scopeParNiveau($query, $niveau)
    {
        return $query->where('niveau', $niveau);
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeAEnvoyer($query)
    {
        return $query->where('statut', 'planifiee')
                    ->where('date_envoi', '<=', now());
    }

    /**
     * Accessors
     */
    public function getTypeFormatteAttribute()
    {
        $types = [
            'email' => 'Email',
            'sms' => 'SMS',
            'courrier' => 'Courrier',
            'appel' => 'Appel téléphonique'
        ];

        return $types[$this->type] ?? $this->type;
    }

    public function getStatutFormatteAttribute()
    {
        $statuts = [
            'planifiee' => 'Planifiée',
            'envoyee' => 'Envoyée',
            'echec' => 'Échec'
        ];

        return $statuts[$this->statut] ?? $this->statut;
    }

    public function getStatutClassAttribute()
    {
        $classes = [
            'planifiee' => 'warning',
            'envoyee' => 'success',
            'echec' => 'danger'
        ];

        return $classes[$this->statut] ?? 'secondary';
    }

    public function getNiveauFormatteAttribute()
    {
        $niveaux = [
            1 => '1er rappel',
            2 => '2ème rappel', 
            3 => 'Dernière relance'
        ];

        return $niveaux[$this->niveau] ?? "Niveau {$this->niveau}";
    }

    public function getDateEnvoiFormatteeAttribute()
    {
        if (!$this->date_envoi) {
            return 'Non envoyée';
        }

        return $this->date_envoi->format('d/m/Y à H:i');
    }

    /**
     * Méthodes métier
     */
    public function marquerCommeEnvoyee($responseData = null)
    {
        $this->update([
            'statut' => 'envoyee',
            'date_envoi' => now(),
            'response_data' => $responseData
        ]);
    }

    public function marquerCommeEchec($errorData = null)
    {
        $this->update([
            'statut' => 'echec',
            'response_data' => $errorData
        ]);
    }

    public function peutEtreRenvoyee()
    {
        return $this->statut === 'echec' || 
               ($this->statut === 'planifiee' && $this->date_envoi < now()->subHours(2));
    }

    public function calculerProchaineDateEnvoi()
    {
        // Lire les délais depuis settings (source unique de vérité)
        $delaiKey = "relances.delai_niveau_{$this->niveau}";
        $delaiSettings = \DB::table('settings')->where('key', $delaiKey)->value('value');

        if ($delaiSettings !== null) {
            return now()->addDays((int) $delaiSettings);
        }

        // Fallback si settings non configurés
        $fallback = [1 => 0, 2 => 7, 3 => 14];
        return now()->addDays($fallback[$this->niveau] ?? 0);
    }

    public function estEnRetard()
    {
        return $this->statut === 'planifiee' && 
               $this->date_envoi && 
               $this->date_envoi < now();
    }

    public function getDureeDepuisEnvoi()
    {
        if (!$this->date_envoi || $this->statut !== 'envoyee') {
            return null;
        }

        return $this->date_envoi->diffForHumans();
    }

    /**
     * Boot method pour génération automatique
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($relance) {
            // Auto-génération de la date d'envoi si non définie
            if (!$relance->date_envoi) {
                $relance->date_envoi = $relance->calculerProchaineDateEnvoi();
            }

            // Auto-génération du template si non défini
            if (!$relance->template_utilise) {
                $relance->template_utilise = "relance_niveau_{$relance->niveau}_{$relance->type}";
            }
        });
    }
}
