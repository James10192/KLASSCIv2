<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPRegleAcademique extends Model
{
    use HasFactory;

    protected $table = 'esbtp_regles_academiques';

    protected $fillable = [
        'niveau',
        'filiere',
        'moyenne_passage',
        'moyenne_rattrapage',
        'max_matieres_rattrapage',
        'autoriser_redoublement',
        'max_redoublements',
        'conditions_speciales',
        'actif'
    ];

    protected $casts = [
        'moyenne_passage' => 'decimal:2',
        'moyenne_rattrapage' => 'decimal:2',
        'autoriser_redoublement' => 'boolean',
        'actif' => 'boolean',
        'max_matieres_rattrapage' => 'integer',
        'max_redoublements' => 'integer',
    ];

    public static function getRegleForNiveauFiliere($niveau, $filiere)
    {
        return self::where('niveau', $niveau)
                   ->where('filiere', $filiere)
                   ->where('actif', true)
                   ->first();
    }

    public function peutPasser($moyenne)
    {
        return $moyenne >= $this->moyenne_passage;
    }

    public function peutRattraper($moyenne)
    {
        return $moyenne >= $this->moyenne_rattrapage && $moyenne < $this->moyenne_passage;
    }

    public function doitRedoubler($moyenne)
    {
        return $moyenne < $this->moyenne_rattrapage;
    }
}