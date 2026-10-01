<?php

namespace App\Domain\Assistant\Outils;

use App\Domain\Assistant\Pieces\PiecesJointes;
use App\Services\Chatbot\Tools\ChatbotTool;

/**
 * Cherche des valeurs dans un tableau joint à la conversation.
 *
 * Le modèle ne voit d'une pièce que ses en-têtes et cinq lignes d'aperçu : il ne
 * pouvait donc pas répondre à « cet élève est-il dans l'état des arriérés ? »
 * autrement qu'en devinant. Ici, le serveur relit la pièce et répond valeur par
 * valeur : trouvée (avec sa ou ses lignes) ou absente.
 *
 * La comparaison ignore la casse, les espaces et les accents : un matricule saisi
 * « br001 309 060001 » retrouve « BR001309060001 ». Elle reste EXACTE sur le reste :
 * un préfixe ne suffit pas, pour ne jamais confondre deux élèves.
 */
class ChercherDansPiece extends ChatbotTool
{
    /** Le résumé envoyé au modèle est plafonné (ResumeOutil) : au-delà, appelle plusieurs fois. */
    private const MAX_VALEURS = 20;

    /**
     * Octets accordés aux lignes trouvées, sous le plafond de ResumeOutil (6 000).
     * Les verdicts (`trouvees`, `absentes`) passent toujours en entier, AVANT les
     * lignes : un résultat coupé ne doit jamais se lire comme une absence — c'est
     * sur « absent de l'état des arriérés » qu'un dû peut être remis à zéro.
     */
    private const OCTETS_LIGNES = 3500;
    private const CARACTERES_PAR_CELLULE = 60;

    public function name(): string
    {
        return 'chercher_dans_piece';
    }

    public function description(): string
    {
        return "Cherche une ou plusieurs valeurs (matricules, de préférence) dans une colonne d'un fichier joint (piece_id), et dit pour chacune si elle y figure, avec sa ligne. "
            . "À utiliser avant toute décision tirée d'un fichier : tu ne vois que cinq lignes d'aperçu, jamais le fichier entier. Une valeur « absente » l'est du fichier lu, pas forcément de la réalité.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'piece_id' => ['type' => 'string', 'description' => 'Identifiant du fichier joint (piece_id).'],
                'colonne' => ['type' => 'string', 'description' => 'Nom EXACT de la colonne où chercher (ex. MATRICULE).'],
                'colonnes_retour' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Colonnes à renvoyer pour les lignes trouvées (ex. NOM, RESTE). Par défaut : toutes, coupées.'],
                'valeurs' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Valeurs à chercher, '.self::MAX_VALEURS.' au plus.'],
            ],
            'required' => ['piece_id', 'colonne', 'valeurs'],
        ];
    }

    public function execute(array $args, $user): array
    {
        $piece = app(PiecesJointes::class)->pour((int) $user->id, (string) ($args['piece_id'] ?? ''));
        if (! $piece || ($piece['type'] ?? 'tableau') !== 'tableau') {
            return ['error' => 'Fichier introuvable ou expiré : demande de le joindre de nouveau.'];
        }

        $colonnes = $piece['colonnes'] ?? [];
        $index = array_search((string) ($args['colonne'] ?? ''), $colonnes, true);
        if ($index === false) {
            return ['error' => 'Colonne inconnue. Colonnes du fichier : '.implode(' | ', $colonnes)];
        }

        $valeurs = array_slice(array_values(array_filter(array_map('strval', (array) ($args['valeurs'] ?? [])), fn ($v) => trim($v) !== '')), 0, self::MAX_VALEURS);
        if ($valeurs === []) {
            return ['error' => 'Indique au moins une valeur à chercher.'];
        }

        $retour = array_values(array_intersect($colonnes, array_map('strval', (array) ($args['colonnes_retour'] ?? []))));
        $retour = $retour === [] ? $colonnes : $retour;

        $parCle = [];
        foreach ($piece['lignes'] ?? [] as $ligne) {
            $complete = array_combine($colonnes, array_pad(array_slice($ligne, 0, count($colonnes)), count($colonnes), ''));
            $parCle[$this->cle((string) ($ligne[$index] ?? ''))][] = array_map(
                fn ($v) => mb_strimwidth((string) $v, 0, self::CARACTERES_PAR_CELLULE, '…', 'UTF-8'),
                array_intersect_key($complete, array_flip($retour))
            );
        }

        $trouvees = $absentes = $occurrences = [];
        $lignes = [];
        $octets = 0;
        $nonTransmises = 0;
        foreach ($valeurs as $valeur) {
            $correspondances = $parCle[$this->cle($valeur)] ?? [];
            if ($correspondances === []) {
                $absentes[] = $valeur;
                continue;
            }
            $trouvees[] = $valeur;
            if (count($correspondances) > 1) {
                $occurrences[$valeur] = count($correspondances);
            }
            $taille = strlen((string) json_encode($correspondances[0], JSON_UNESCAPED_UNICODE));
            if ($octets + $taille > self::OCTETS_LIGNES) {
                $nonTransmises++;
                continue;
            }
            $octets += $taille;
            $lignes[$valeur] = $correspondances[0];
        }

        $diagnostic = [
            'fichier' => $piece['nom'] ?? null,
            'colonne' => $colonnes[$index],
            'lignes_lues' => count($piece['lignes'] ?? []),
            'fichier_tronque' => (bool) ($piece['tronque'] ?? false),
            'trouvees' => $trouvees,
            'absentes' => $absentes,
            'plusieurs_lignes' => $occurrences ?: null,
            'lignes' => $lignes,
            'lignes_non_transmises' => $nonTransmises ?: null,
            'feuilles' => $piece['feuilles'] ?? null,
            'feuilles_non_lues' => $piece['autres_feuilles'] ?? null,
            'feuilles_illisibles' => $piece['feuilles_illisibles'] ?? null,
        ];

        return [
            'display_type' => 'text',
            'message' => sprintf('%d trouvée(s), %d absente(s) du fichier « %s ».', count($trouvees), count($absentes), $piece['nom'] ?? 'joint'),
            'diagnostic' => array_filter($diagnostic, fn ($v) => $v !== null),
        ];
    }

    private function cle(string $valeur): string
    {
        $sansAccent = \Normalizer::normalize($valeur, \Normalizer::FORM_D);
        $sansAccent = preg_replace('/\p{Mn}+/u', '', (string) $sansAccent);

        return mb_strtolower(preg_replace('/\s+/u', '', (string) $sansAccent));
    }
}
