<?php

namespace App\Domain\Notes;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultat;
use App\Services\AppreciationScaleService;
use App\Services\BulletinService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enregistrer ou retirer des moyennes de matiere pour UN eleve, sur UNE
 * classe et UN semestre : ce que fait l'ecran « Modifier les moyennes », sans
 * l'ecran. Sert aux reclamations traitees a distance.
 *
 * Meme ecriture que `ESBTPResultatController::updateMoyennes()` — coefficient
 * de la ligne existante, a defaut celui de la combinaison, a defaut 1 ;
 * appreciation tiree du bareme — pour qu'une moyenne posee ici ne se
 * distingue en rien d'une moyenne posee a l'ecran.
 *
 * Deux ecarts, voulus :
 *  - le retrait est une suppression DOUCE (l'ecran supprime en dur). Une
 *    reclamation se conteste ; la ligne d'avant doit pouvoir revenir, et
 *    l'audit garder qui l'a retiree ;
 *  - tout ou rien, dans une transaction : le garde d'`ESBTPResultat` (une ECUE
 *    dans une classe BTS) peut lever au milieu de la liste.
 *
 * Une moyenne enregistree l'emporte sur les notes au bulletin. La retirer
 * rend la main aux notes de la matiere, s'il y en a.
 */
final class SaisieDeMoyennes
{
    public function __construct(
        private BulletinService $bulletins,
        private AppreciationScaleService $appreciations,
    ) {
    }

    /**
     * @param  array<int, array{matiere_id:int, moyenne:float|int|string|null}>  $lignes
     * @return array{lignes: array<int, array<string,mixed>>, bulletins_a_regenerer: array<int,int>}
     */
    public function appliquer(
        int $etudiantId,
        ESBTPClasse $classe,
        int $anneeId,
        string $periode,
        array $lignes,
        bool $simuler,
        ?int $auteurId,
    ): array {
        // Refus AVANT toute ecriture, et aussi en simulation : l'apercu doit
        // annoncer le refus que l'ecriture rencontrerait (garde d'ESBTPResultat,
        // moyennes en double).
        foreach ($lignes as $ligne) {
            $matiere = ESBTPMatiere::findOrFail($ligne['matiere_id']);
            $this->refuserUneMatiereEtrangere($classe, $matiere);
            $this->laLigneLue([
                'etudiant_id' => $etudiantId, 'classe_id' => $classe->id, 'matiere_id' => $matiere->id,
                'periode' => $periode, 'annee_universitaire_id' => $anneeId,
            ], $matiere);
        }

        $ecrire = fn () => array_map(
            fn (array $ligne) => $this->uneLigne($etudiantId, $classe, $anneeId, $periode, $ligne, $simuler, $auteurId),
            $lignes
        );
        $rapport = $simuler ? $ecrire() : DB::transaction($ecrire);

        return [
            'lignes' => $rapport,
            // Le bulletin fige sa moyenne generale a la generation : il ne
            // bougera qu'une fois regenere.
            'bulletins_a_regenerer' => ESBTPBulletin::where('etudiant_id', $etudiantId)
                ->where('classe_id', $classe->id)
                ->where('annee_universitaire_id', $anneeId)
                ->where('periode', $periode)
                ->pluck('id')->all(),
        ];
    }

    /** @param array{matiere_id:int, moyenne:float|int|string|null} $ligne */
    private function uneLigne(int $etudiantId, ESBTPClasse $classe, int $anneeId, string $periode, array $ligne, bool $simuler, ?int $auteurId): array
    {
        $matiere = ESBTPMatiere::findOrFail($ligne['matiere_id']);
        $cle = [
            'etudiant_id' => $etudiantId,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'periode' => $periode,
            'annee_universitaire_id' => $anneeId,
        ];
        $existante = $this->laLigneLue($cle, $matiere);
        $avant = $existante ? (float) $existante->moyenne : null;
        $cible = $ligne['moyenne'] === null ? null : round((float) $ligne['moyenne'], 2);

        $rapport = ['matiere_id' => $matiere->id, 'matiere' => $matiere->name, 'avant' => $avant, 'apres' => $cible];

        if ($cible === null) {
            if ($existante && ! $simuler) {
                $existante->delete();
            }

            return $rapport + ['action' => $existante ? 'retiree' : 'absente'];
        }

        if ($existante && $avant === $cible) {
            return $rapport + ['action' => 'inchangee', 'coefficient' => $existante->coefficient];
        }

        $coefficient = $existante?->coefficient ?: $this->coefficient($matiere->id, $classe->id, $anneeId, $periode, $etudiantId);
        $valeurs = [
            'moyenne' => $cible,
            'coefficient' => $coefficient,
            'appreciation' => $this->appreciations->labelFor($cible, 'bts'),
            'updated_by' => $auteurId,
        ];

        if (! $simuler) {
            $existante
                ? $existante->update($valeurs)
                : ESBTPResultat::create($cle + $valeurs + ['created_by' => $auteurId]);
        }

        return $rapport + ['action' => $existante ? 'modifiee' : 'creee', 'coefficient' => $coefficient];
    }

    /**
     * La ligne que le bulletin lira — et refus s'il y en a plusieurs.
     *
     * L'index unique d'esbtp_resultats inclut `deleted_at`, et MySQL tient les
     * NULL pour distincts : rien n'empeche DEUX lignes vivantes sur la meme cle
     * (une course de recalcul suffit). Modifier la premiere laisserait le
     * bulletin lire l'autre, et la reclamation paraitrait traitee sans l'etre.
     * On refuse plutot que de deviner laquelle compte.
     *
     * @param  array<string,int|string>  $cle
     */
    private function laLigneLue(array $cle, ESBTPMatiere $matiere): ?ESBTPResultat
    {
        $vivantes = ESBTPResultat::where($cle)->get();
        if ($vivantes->count() > 1) {
            throw ValidationException::withMessages([
                'moyennes' => "« {$matiere->name} » porte {$vivantes->count()} moyennes enregistrées pour ce semestre "
                    .'(lignes '.$vivantes->pluck('id')->implode(', ').') : à dédoublonner avant toute saisie.',
            ]);
        }

        return $vivantes->first();
    }

    private function refuserUneMatiereEtrangere(ESBTPClasse $classe, ESBTPMatiere $matiere): void
    {
        if (! CoherenceSystemeAcademique::estCoherente($classe->systeme_academique, $matiere->unite_enseignement_id)) {
            throw ValidationException::withMessages([
                'moyennes' => CoherenceSystemeAcademique::messageDeRefus($classe->systeme_academique, $classe->name, $matiere->name),
            ]);
        }
    }

    /** Meme repli que l'ecran : un coefficient introuvable ne bloque pas la saisie. */
    private function coefficient(int $matiereId, int $classeId, int $anneeId, string $periode, int $etudiantId): float
    {
        try {
            return $this->bulletins->getCoefficientForCombination($matiereId, $classeId, $anneeId, $periode, $etudiantId);
        } catch (\RuntimeException) {
            return 1.0;
        }
    }
}
