<?php

namespace App\Domain\Academique;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Facades\DB;

/**
 * Créer une année universitaire, désigner l'année courante.
 *
 * Un seul chemin pour la CLI et pour Nanan, et c'est celui de l'écran :
 * `ESBTPAnneeUniversitaire::setAsCurrent()`. La CLI faisait jusqu'ici sa propre
 * bascule, sans vider le cache : `getCurrent()` servait encore l'ancienne année
 * pendant dix minutes après le changement.
 */
class AnneesUniversitaires
{
    /**
     * @param array{name: string, start_date: string, end_date: string, description?: ?string} $donnees
     */
    public function creer(array $donnees, bool $courante): ESBTPAnneeUniversitaire
    {
        return DB::transaction(function () use ($donnees, $courante) {
            $annee = ESBTPAnneeUniversitaire::create([
                'name' => trim($donnees['name']),
                'start_date' => $donnees['start_date'],
                'end_date' => $donnees['end_date'],
                'description' => $donnees['description'] ?? null,
                'is_current' => false,
                'is_active' => true,
            ]);

            if ($courante) {
                $this->definirCourante($annee);
            }

            return $annee->refresh();
        });
    }

    public function definirCourante(ESBTPAnneeUniversitaire $annee): void
    {
        $annee->setAsCurrent();
        ESBTPAnneeUniversitaire::flushCurrentCache();
    }

    /** Le nom est unique, comme à l'écran (`unique:esbtp_annee_universitaires,name`). */
    public function nomPris(string $nom): bool
    {
        return ESBTPAnneeUniversitaire::where('name', trim($nom))->exists();
    }

    /** Ce que la bascule touche : à montrer avant de la faire. */
    public function effectifs(ESBTPAnneeUniversitaire $annee): array
    {
        return [
            'inscriptions' => DB::table('esbtp_inscriptions')->where('annee_universitaire_id', $annee->id)->whereNull('deleted_at')->count(),
            'sous_reserve' => DB::table('esbtp_inscriptions')->where('annee_universitaire_id', $annee->id)->whereNull('deleted_at')->where('is_sous_reserve', true)->count(),
        ];
    }
}
