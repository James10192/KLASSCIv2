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
        // `credit` : le poids en credits que CETTE maquette donne a l unite pour
        // CE semestre. `null` = pas de credit propre, on prend celui de l unite.
        // Declare avant d etre lu, sinon il rendrait null sans rien signaler.
        )->withPivot('semestre', 'is_optional', 'ordre', 'credit')->withTimestamps();
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
     * Générer le libellé parcours destiné à un document officiel.
     *
     * Le code court de la filière (BU, TP, GCV...) est un identifiant interne :
     * il aide les écrans et les imports, mais n'a pas à apparaître sur le
     * bulletin. Exemple : "LICENCE 1 BÂTIMENT ET URBANISME", jamais
     * "LICENCE 1 BU BÂTIMENT ET URBANISME".
     *
     * @param ESBTPNiveauEtude|null $niveau Le niveau de la classe
     */
    public function genererLabelBulletin($niveau = null): string
    {
        $filiere = $this->filiere;

        if (! $filiere && ! $niveau) {
            return (string) $this->name;
        }

        $parts = [];

        if ($niveau) {
            $parts[] = mb_strtoupper(trim((string) ($niveau->name ?? '')), 'UTF-8');
        }

        if ($filiere) {
            $parts[] = mb_strtoupper(trim((string) ($filiere->name ?? '')), 'UTF-8');
        }

        return implode(' ', array_filter($parts)) ?: (string) $this->name;
    }

    /**
     * Corrige aussi les snapshots déjà générés sans réécrire leur contenu.
     *
     * Les anciens bulletins ont parfois figé "LICENCE 1 BU BÂTIMENT..." dans
     * parcours_label. On retire uniquement le token exact correspondant au code
     * de la filière, sans toucher au reste du libellé historique.
     */
    public function nettoyerLabelBulletin(?string $label): string
    {
        $label = trim((string) $label);
        $code = trim((string) ($this->filiere?->code ?? ''));

        if ($label === '' || $code === '') {
            return $label;
        }

        $nettoye = preg_replace(
            '/(?<![\\pL\\pN])'.preg_quote($code, '/').'(?![\\pL\\pN])/iu',
            ' ',
            $label
        );

        return trim((string) preg_replace('/\\s{2,}/u', ' ', (string) $nettoye));
    }
}
