<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESBTPMatriculeConfig extends Model
{
    use HasFactory;

    protected $table = 'esbtp_matricule_configs';

    protected $fillable = [
        'etablissement_id',
        'niveau_etude_code',
        'niveau_etude_name',
        'pattern',
        'prefixe',
        'annee_format',
        'numero_digits',
        'etablissement_code',
        'is_active',
        'description',
        'exemple'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'annee_format' => 'integer',
        'numero_digits' => 'integer',
        'exemple' => 'array'
    ];

    protected $appends = ['exemples_generes'];

    /**
     * Générer un matricule selon la configuration
     *
     * @param string $genre M ou F
     * @param int $annee Année courante (ex: 2025)
     * @return string
     */
    public function genererMatricule($genre, $annee = null)
    {
        if (!$annee) {
            $annee = date('Y');
        }

        // Formater l'année selon la config
        $anneeFormatee = $this->annee_format == 2 ?
            substr($annee, -2) : $annee;

        // Obtenir le prochain numéro séquentiel
        $numeroSuivant = $this->getProchainNumero($genre, $anneeFormatee);

        // Formater le numéro avec les zéros de remplissage
        $numeroFormate = str_pad($numeroSuivant, $this->numero_digits, '0', STR_PAD_LEFT);

        // Construire le matricule
        $matricule = $genre .
                    ($this->prefixe ? $this->prefixe : '') .
                    $this->etablissement_code .
                    $anneeFormatee . '-' .
                    $numeroFormate;

        return $matricule;
    }

    /**
     * Obtenir le prochain numéro séquentiel pour un genre et une année
     * Recherche les trous dans les 100 derniers numéros pour optimiser la réutilisation
     *
     * @param string $genre
     * @param string $anneeFormatee
     * @return int
     */
    private function getProchainNumero($genre, $anneeFormatee)
    {
        // Pattern de recherche basé sur la configuration
        $pattern = $genre .
                  ($this->prefixe ? $this->prefixe : '') .
                  $this->etablissement_code .
                  $anneeFormatee . '-%';

        // Chercher le dernier matricule avec ce pattern (inclut soft deleted — matricules jamais réutilisés)
        $dernierEtudiant = ESBTPEtudiant::withTrashed()
            ->where('matricule', 'LIKE', $pattern)
            ->orderByRaw("CAST(SUBSTRING_INDEX(matricule, '-', -1) AS UNSIGNED) DESC")
            ->first();

        if (!$dernierEtudiant) {
            return 1;
        }

        // Extraire le numéro du matricule
        $parts = explode('-', $dernierEtudiant->matricule);
        $maxNumero = intval(end($parts));

        // Recherche incrémentale dans les 100 DERNIERS numéros pour trouver un trou
        $searchStart = max(1, $maxNumero - 99);

        for ($i = $searchStart; $i <= $maxNumero; $i++) {
            // Construire le matricule complet à tester
            $numeroFormate = str_pad($i, $this->numero_digits, '0', STR_PAD_LEFT);
            $testMatricule = $genre .
                            ($this->prefixe ? $this->prefixe : '') .
                            $this->etablissement_code .
                            $anneeFormatee . '-' .
                            $numeroFormate;

            // Vérifier si ce matricule existe (inclut soft deleted — jamais réutilisés)
            $exists = ESBTPEtudiant::withTrashed()
                ->where('matricule', $testMatricule)
                ->exists();

            if (!$exists) {
                // Trou trouvé, retourner ce numéro
                return $i;
            }
        }

        // Aucun trou trouvé dans les 100 derniers, retourner max + 1
        return $maxNumero + 1;
    }

    /**
     * Générer des exemples de matricules pour cette configuration
     *
     * @return array
     */
    public function genererExemples()
    {
        $anneeActuelle = date('Y');
        $anneeFormatee = $this->annee_format == 2 ?
            substr($anneeActuelle, -2) : $anneeActuelle;

        $exemples = [
            'masculin' => 'M' .
                         ($this->prefixe ? $this->prefixe : '') .
                         $this->etablissement_code .
                         $anneeFormatee . '-' .
                         str_pad(1, $this->numero_digits, '0', STR_PAD_LEFT),

            'feminin' => 'F' .
                        ($this->prefixe ? $this->prefixe : '') .
                        $this->etablissement_code .
                        $anneeFormatee . '-' .
                        str_pad(1, $this->numero_digits, '0', STR_PAD_LEFT)
        ];

        return $exemples;
    }

    /**
     * Accesseur pour les exemples générés automatiquement
     *
     * @return array
     */
    public function getExemplesGeneresAttribute()
    {
        return $this->genererExemples();
    }

    /**
     * Relation avec l'établissement
     */
    public function etablissement()
    {
        return $this->belongsTo(ESBTPEtablissement::class, 'etablissement_id');
    }

    /**
     * Scope pour les configurations actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Vérifier si un matricule existe déjà
     *
     * @param string $matricule
     * @return bool
     */
    public static function matriculeExists($matricule)
    {
        return ESBTPEtudiant::withTrashed()->where('matricule', $matricule)->exists();
    }
}