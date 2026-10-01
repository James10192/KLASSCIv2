<?php

namespace App\Domain\Academique;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Créer une année universitaire, désigner l'année courante.
 *
 * LE seul endroit qui bascule l'année en cours : l'écran (via
 * ESBTPAnneeUniversitaire::setAsCurrent(), qui délègue ici), la CLI et Nanan.
 * La CLI faisait jusqu'ici sa propre bascule, sans vider le cache :
 * `getCurrent()` servait encore l'ancienne année pendant dix minutes.
 *
 * Une transaction, jamais deux imbriquées : le vidage du cache est posé en
 * DB::afterCommit, et Laravel 9 ne joue jamais un rappel posé deux niveaux
 * sous une transaction de test (DatabaseTransactions). Les appelants qui veulent revérifier un état sous verrou passent `$verifier`,
 * exécuté DANS la transaction, plutôt que d'en ouvrir une autour.
 */
class AnneesUniversitaires
{
    /**
     * @param array{name: string, start_date: string, end_date: string, description?: ?string} $donnees
     * @param (callable(): void)|null $verifier levée attendue si l'état a changé
     */
    public function creer(array $donnees, bool $courante, ?callable $verifier = null): ESBTPAnneeUniversitaire
    {
        return DB::transaction(function () use ($donnees, $courante, $verifier) {
            $this->verrouillerCourante();
            if ($verifier) {
                $verifier();
            }
            $annee = ESBTPAnneeUniversitaire::create([
                'name' => trim($donnees['name']),
                'start_date' => $donnees['start_date'],
                'end_date' => $donnees['end_date'],
                'description' => $donnees['description'] ?? null,
                'is_current' => false,
                'is_active' => true,
            ]);

            if ($courante) {
                $this->basculer($annee);
            }

            return $annee->refresh();
        });
    }

    /**
     * Bascule le drapeau puis vide TOUT le cache (Cache::flush, comme l'écran
     * l'a toujours fait) — mais seulement une fois la transaction validée :
     * vidé avant, une requête concurrente relisait l'ancienne année (encore en
     * base) et la remettait en cache pour dix minutes.
     *
     * @param (callable(): void)|null $verifier levée attendue si l'état a changé
     */
    public function definirCourante(ESBTPAnneeUniversitaire $annee, ?callable $verifier = null): void
    {
        DB::transaction(function () use ($annee, $verifier) {
            $this->verrouillerCourante();
            if ($verifier) {
                $verifier();
            }
            $this->basculer($annee);
        });
    }

    /** Le nom est unique, comme à l'écran (`unique:esbtp_annee_universitaires,name`). */
    public function nomPris(string $nom): bool
    {
        return ESBTPAnneeUniversitaire::where('name', trim($nom))->exists();
    }

    /** L'identifiant de l'année en cours, lu tel quel (pas de cache). */
    public function idCourante(): ?int
    {
        $id = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        return $id === null ? null : (int) $id;
    }

    /** Ce que la bascule touche : à montrer avant de la faire. */
    public function effectifs(ESBTPAnneeUniversitaire $annee): array
    {
        return [
            'inscriptions' => DB::table('esbtp_inscriptions')->where('annee_universitaire_id', $annee->id)->whereNull('deleted_at')->count(),
            'sous_reserve' => DB::table('esbtp_inscriptions')->where('annee_universitaire_id', $annee->id)->whereNull('deleted_at')->where('is_sous_reserve', true)->count(),
        ];
    }

    private function verrouillerCourante(): void
    {
        ESBTPAnneeUniversitaire::where('is_current', true)->lockForUpdate()->get(['id']);
    }

    private function basculer(ESBTPAnneeUniversitaire $annee): void
    {
        ESBTPAnneeUniversitaire::where('id', '!=', $annee->id)->update(['is_current' => false]);
        ESBTPAnneeUniversitaire::where('id', $annee->id)->update(['is_current' => true]);
        DB::afterCommit(fn () => Cache::flush());
    }
}
