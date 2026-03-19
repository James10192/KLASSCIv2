<?php

namespace App\Models;

use App\Models\Traits\HasAuditTrail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPLMDParcours extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail;

    protected $table = 'esbtp_lmd_parcours';

    protected $fillable = [
        'name', 'code', 'description', 'mention_id', 'filiere_id',
        'responsable_id', 'credits_licence', 'credits_master', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'credits_licence' => 'integer',
        'credits_master' => 'integer',
        'is_active' => 'boolean',
    ];

    public function mention()
    {
        return $this->belongsTo(ESBTPLMDMention::class, 'mention_id');
    }

    /**
     * Raccourci pour acceder au domaine via la mention.
     * Note: pas une vraie relation Eloquent, utiliser via $parcours->domaine_instance.
     */
    public function getDomaineAttribute()
    {
        return $this->mention?->domaine;
    }

    /**
     * Filiere ESBTP liee a ce parcours.
     * Le parcours LMD correspond a une filiere existante (ex: Batiment, Travaux Publics).
     */
    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function unitesEnseignement()
    {
        return $this->belongsToMany(
            ESBTPUniteEnseignement::class,
            'esbtp_lmd_parcours_ue',
            'parcours_id',
            'unite_enseignement_id'
        )->withPivot('semestre', 'is_optional')->withTimestamps();
    }

    public function classes()
    {
        return $this->hasMany(ESBTPClasse::class, 'parcours_id');
    }

    public function bulletins()
    {
        return $this->hasMany(ESBTPLMDBulletin::class, 'parcours_id');
    }

    /**
     * Label complet: Domaine > Mention > Parcours
     */
    public function getLabelCompletAttribute(): string
    {
        $mention = $this->mention;
        $domaine = $mention?->domaine;
        return implode(' > ', array_filter([
            $domaine?->name,
            $mention?->name,
            $this->name,
        ]));
    }

    /**
     * Generer le label parcours tel qu'il apparait sur le bulletin ESBTP.
     * Ex: "LICENCE 3 GCV BATIMENT & URBANISME"
     *
     * @param ESBTPNiveauEtude|null $niveau Le niveau de la classe
     * @return string
     */
    public function genererLabelBulletin($niveau = null): string
    {
        $filiere = $this->filiere;

        if (!$filiere && !$niveau) {
            return $this->name; // Fallback au nom du parcours
        }

        $parts = [];

        // Niveau : "LICENCE 3" ou "MASTER 1"
        if ($niveau) {
            $parts[] = strtoupper($niveau->name ?? '');
        }

        // Filiere : "GCV BATIMENT & URBANISME"
        if ($filiere) {
            $filiereLabel = trim(($filiere->code ? $filiere->code . ' ' : '') . $filiere->name);
            $parts[] = strtoupper($filiereLabel);
        }

        return implode(' ', array_filter($parts)) ?: $this->name;
    }
}
