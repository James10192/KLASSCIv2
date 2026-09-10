<?php

declare(strict_types=1);

namespace App\Domain\Dispenses\Models;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Une matiere dont un etudiant est dispense, pour un semestre ou pour l'annee.
 *
 * Une dispense ne s'efface pas, elle se revoque : la ligne reste, et l'on peut
 * dire des mois plus tard qui a decide quoi, quand, et pourquoi. Un bulletin
 * distribue s'appuie dessus.
 *
 * BTS uniquement : les dispenses LMD attendent les regles de jury.
 */
class ESBTPDispense extends Model implements Auditable
{
    use HasFactory, SoftDeletes, \OwenIt\Auditing\Auditable;

    public const PERIODE_ANNEE = null;

    protected $table = 'esbtp_dispenses';

    protected $fillable = [
        'etudiant_id',
        'matiere_id',
        'annee_universitaire_id',
        'periode',
        'motif',
        'accordee_par',
        'accordee_le',
        'revoquee_par',
        'revoquee_le',
        'motif_revocation',
    ];

    protected $casts = [
        'accordee_le' => 'datetime',
        'revoquee_le' => 'datetime',
    ];

    /**
     * Ce que l'audit retient.
     *
     * Liste explicite : sans elle, la table d'audit enfle et l'on n'y retrouve
     * plus ce qui compte. Ce qui compte ici, c'est la decision — sur qui, quelle
     * matiere, quelle periode, pour quel motif — et sa revocation.
     */
    protected $auditInclude = [
        'etudiant_id',
        'matiere_id',
        'annee_universitaire_id',
        'periode',
        'motif',
        'revoquee_le',
        'motif_revocation',
    ];

    protected $auditEvents = ['created', 'updated', 'deleted', 'restored'];

    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function matiere()
    {
        return $this->belongsTo(ESBTPMatiere::class, 'matiere_id');
    }

    public function annee()
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function accordeePar()
    {
        return $this->belongsTo(User::class, 'accordee_par');
    }

    public function revoqueePar()
    {
        return $this->belongsTo(User::class, 'revoquee_par');
    }

    /** Une dispense revoquee reste en base, mais ne produit plus d'effet. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoquee_le');
    }

    public function estActive(): bool
    {
        return $this->revoquee_le === null;
    }

    /**
     * Cette dispense couvre-t-elle ce semestre ?
     *
     * Une dispense annuelle (`periode` nulle) couvre les deux.
     */
    public function couvreLeSemestre(?int $semestre): bool
    {
        if ($this->periode === null) {
            return true;
        }

        if ($semestre === null) {
            // L'ANNEE est demandee, et cette dispense ne porte que sur un
            // semestre : elle ne suffit pas a retirer la matiere de l'annee.
            //
            // Croire l'inverse coutait cher. Un eleve dispense de
            // mathematiques au premier semestre, puis note 8 au second, voyait
            // la matiere disparaitre entierement de son bulletin annuel : sa
            // moyenne et son rang montaient sur un travail qu'il avait
            // reellement rendu. Deux dispenses de semestre qui couvrent les
            // deux moities sont traitees par DispenseLookup, qui voit
            // l'ensemble ; une seule ligne ne peut pas en decider.
            return false;
        }

        return $this->periode === 'semestre'.$semestre;
    }

    /** Libelle lisible de la portee, pour l'ecran et le bulletin. */
    public function porteeLisible(): string
    {
        return match ($this->periode) {
            'semestre1' => 'Semestre 1',
            'semestre2' => 'Semestre 2',
            default => 'Année complète',
        };
    }
}
