<?php

declare(strict_types=1);

namespace App\Domain\BtsTroncCommun;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Str;

/**
 * Retrouver une filiere, un niveau, une matiere depuis ce qu'a ecrit l'appelant.
 *
 * ON NE DEVINE JAMAIS. Le catalogue d'ESBTP Abidjan compte quatre « Anglais »
 * et quatre « Hydraulique appliquee » : resoudre au plus proche poserait un
 * bulletin faux que personne ne saurait relire. Un libelle ambigu est rendu
 * comme tel, avec ses candidats, et c'est l'appelant qui tranche.
 *
 * BTS uniquement POUR CE QUI ENTRE : une ECUE LMD (`unite_enseignement_id` non
 * nul) est refusee au chargement, qu'elle soit designee par son libelle ou par
 * son identifiant. La garde n'existait que sur le libelle, et l'identifiant
 * passait a cote.
 *
 * MAIS PAS POUR CE QUI SORT. Refuser une ECUE au RETRAIT aussi rendait la
 * ligne fautive inextirpable : le CLI la listait (`lignesDeLaMaquette` ne
 * filtre pas — c'est voulu, c'est le seul endroit ou on peut la VOIR), l'ecran
 * de classification l'ecartait de ses lignes donc n'offrait aucune croix, et
 * `POST /retirer` repondait « ne designe pas une matiere unique » pour un
 * identifiant que l'endpoint voisin venait de rendre. Le retrait est le geste
 * CORRECTEUR : il ne peut pas contaminer une maquette, il ne peut que la
 * nettoyer. D'ou `$pourRetrait`.
 *
 * @see .claude/rules/lmd-ecue-leak-bts-picker.md
 * @see .claude/rules/lmd-bts-matieres-single-source.md
 */
final class ResolutionDeMatiere
{
    public function filiere(mixed $cle): ?ESBTPFiliere
    {
        if (is_numeric($cle)) {
            return ESBTPFiliere::find((int) $cle);
        }

        return ESBTPFiliere::where('code', $cle)->first()
            ?? ESBTPFiliere::where('name', $cle)->first();
    }

    public function niveau(mixed $cle): ?ESBTPNiveauEtude
    {
        if (is_numeric($cle)) {
            return ESBTPNiveauEtude::find((int) $cle);
        }

        return ESBTPNiveauEtude::where('name', $cle)->first();
    }

    /**
     * @param  bool  $pourRetrait  true : accepte aussi une ECUE LMD, parce que
     *                             le retrait est le seul geste qui peut enlever
     *                             une ligne posee par erreur.
     * @return array{statut: 'ok'|'ambigu'|'introuvable', libelle: string, matiere?: ESBTPMatiere, candidats?: array<int, array{id: int, name: string, code: ?string}>}
     */
    public function matiere(mixed $entree, int $filiereId, int $niveauId, bool $pourRetrait = false): array
    {
        $libelle = is_array($entree) ? (string) ($entree['nom'] ?? $entree['id'] ?? '') : (string) $entree;

        $id = is_array($entree) ? ($entree['id'] ?? null) : (is_numeric($entree) ? $entree : null);
        if ($id !== null) {
            $matiere = ESBTPMatiere::query()
                ->unless($pourRetrait, fn ($q) => $q->whereNull('unite_enseignement_id'))
                ->find((int) $id);

            return $matiere
                ? ['statut' => 'ok', 'libelle' => $matiere->name, 'matiere' => $matiere]
                : ['statut' => 'introuvable', 'libelle' => (string) $id];
        }

        $cible = $this->normaliser($libelle);
        $candidats = ESBTPMatiere::query()
            ->unless($pourRetrait, fn ($q) => $q->whereNull('unite_enseignement_id'))
            ->get(['id', 'name', 'code'])
            ->filter(fn ($m) => $this->normaliser($m->name) === $cible)
            ->values();

        if ($candidats->isEmpty()) {
            return ['statut' => 'introuvable', 'libelle' => $libelle];
        }
        if ($candidats->count() === 1) {
            return ['statut' => 'ok', 'libelle' => $libelle, 'matiere' => $candidats->first()];
        }

        // Un doublon deja rattache a CE couple filiere x niveau n'en est plus
        // un : l'ecole a deja tranche, on suit sa decision.
        $dejaLiees = ESBTPMatiereFilierNiveau::query()
            ->where('filiere_id', $filiereId)
            ->where('niveau_etude_id', $niveauId)
            ->whereIn('matiere_id', $candidats->pluck('id'))
            ->pluck('matiere_id');

        if ($dejaLiees->count() === 1) {
            return [
                'statut' => 'ok',
                'libelle' => $libelle,
                'matiere' => $candidats->firstWhere('id', $dejaLiees->first()),
            ];
        }

        return [
            'statut' => 'ambigu',
            'libelle' => $libelle,
            'candidats' => $candidats->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->code])->all(),
        ];
    }

    /**
     * Rapproche deux libelles qui designent la meme matiere.
     *
     * Sans accent, sans casse, sans espace ni ponctuation : « Hydraulique
     * appliquee », « HYDRAULIQUE APPLIQUÉE » et « Hydraulique-appliquee » sont
     * le meme mot pour une ecole qui recopie son bulletin papier.
     */
    private function normaliser(?string $valeur): string
    {
        $sansAccent = Str::ascii((string) $valeur);

        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($sansAccent, 'UTF-8')) ?? '';
    }
}
