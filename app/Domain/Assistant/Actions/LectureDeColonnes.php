<?php

namespace App\Domain\Assistant\Actions;

use App\Domain\Assistant\Pieces\LectureDePiece;
use App\Domain\Assistant\Pieces\PiecesJointes;

/**
 * Le serveur relit un fichier joint : le modèle ne désigne que la pièce et le
 * nom de ses colonnes, jamais les valeurs. Une colonne introuvable ou une
 * feuille inconnue est un manque, avec la liste de ce qui existe.
 */
trait LectureDeColonnes
{
    /**
     * @param  array<string, ?string>  $colonnes  rôle → nom de colonne (null = non fourni)
     * @return array{0: list<array<string, string>>, 1: string[], 2: string[]}  lignes (rôle → valeur), manques, avertissements
     */
    protected function lireColonnes(array $source, $user, array $colonnes): array
    {
        $piece = app(PiecesJointes::class)->pour((int) $user->id, (string) ($source['piece_id'] ?? ''));
        if (! $piece || ($piece['type'] ?? 'tableau') !== 'tableau') {
            return [[], ['Le fichier joint n\'est plus disponible ou n\'est pas un tableau (deux heures au plus) : demande à la personne de le joindre de nouveau.'], []];
        }

        $index = array_flip(array_map(fn ($c) => mb_strtolower(trim((string) $c)), $piece['colonnes']));
        $positions = [];
        $inconnues = [];
        foreach ($colonnes as $role => $nom) {
            if ($nom === null || trim((string) $nom) === '') {
                continue;
            }
            $i = $index[mb_strtolower(trim((string) $nom))] ?? null;
            $i === null ? $inconnues[] = (string) $nom : $positions[$role] = $i;
        }
        if ($inconnues !== []) {
            return [[], ['Colonne(s) introuvable(s) dans « ' . $piece['nom'] . ' » : ' . implode(', ', $inconnues) . '. Colonnes disponibles : ' . implode(', ', $piece['colonnes']) . '.'], []];
        }

        $feuilles = $piece['feuilles'] ?? [];
        $colFeuille = $feuilles !== [] ? ($index['feuille'] ?? $index['onglet'] ?? null) : null;
        $voulue = trim((string) ($source['feuille'] ?? ''));
        if ($voulue !== '' && $colFeuille !== null && ! in_array($voulue, $feuilles, true)) {
            return [[], ['Feuille inconnue « ' . $voulue . ' ». Feuilles lues : ' . implode(', ', $feuilles) . '.'], []];
        }

        $lignes = [];
        foreach ($piece['lignes'] as $l) {
            if ($voulue !== '' && $colFeuille !== null && (string) ($l[$colFeuille] ?? '') !== $voulue) {
                continue;
            }
            $ligne = [];
            foreach ($positions as $role => $i) {
                $ligne[$role] = trim((string) ($l[$i] ?? ''));
            }
            if (implode('', $ligne) !== '') {
                $lignes[] = $ligne;
            }
        }

        $avertissements = [];
        if ($colFeuille !== null && $voulue === '' && count($feuilles) > 1) {
            $avertissements[] = 'Feuilles lues ensemble : ' . implode(', ', $feuilles) . '. Précise la feuille si une seule est concernée.';
        }
        if (! empty($piece['feuilles_illisibles'])) {
            $avertissements[] = 'Feuilles illisibles, non lues : ' . implode(', ', $piece['feuilles_illisibles']) . '.';
        }
        if (! empty($piece['tronque'])) {
            $avertissements[] = 'Le fichier dépasse ' . LectureDePiece::MAX_LIGNES . ' lignes ou ' . LectureDePiece::MAX_COLONNES . ' colonnes : la suite n\'a pas été lue.';
        }

        return [$lignes, [], $avertissements];
    }

    /** « 3 », « 3,0 », « 3.0 » → 3 ; tout le reste → null (jamais arrondi en silence). */
    protected function entier(string $valeur): ?int
    {
        $v = str_replace([',', ' ', "\u{00A0}"], ['.', '', ''], trim($valeur));
        if ($v === '' || ! is_numeric($v) || (float) $v !== floor((float) $v) || (float) $v < 0) {
            return null;
        }

        return (int) $v;
    }
}
