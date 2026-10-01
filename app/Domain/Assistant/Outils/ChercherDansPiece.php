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
    private const LIGNES_PAR_VALEUR = 1;

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

        $parCle = [];
        foreach ($piece['lignes'] ?? [] as $ligne) {
            $parCle[$this->cle((string) ($ligne[$index] ?? ''))][] = array_combine($colonnes, array_pad($ligne, count($colonnes), ''));
        }

        $resultats = [];
        foreach ($valeurs as $valeur) {
            $trouvees = $parCle[$this->cle($valeur)] ?? [];
            $resultats[] = [
                'valeur' => $valeur,
                'trouvee' => $trouvees !== [],
                'occurrences' => count($trouvees),
                'lignes' => array_slice($trouvees, 0, self::LIGNES_PAR_VALEUR),
            ];
        }

        return [
            'display_type' => 'text',
            'message' => sprintf('%d trouvée(s), %d absente(s) du fichier « %s ».',
                count(array_filter($resultats, fn ($r) => $r['trouvee'])),
                count(array_filter($resultats, fn ($r) => ! $r['trouvee'])),
                $piece['nom'] ?? 'joint'),
            'diagnostic' => [
                'fichier' => $piece['nom'] ?? null,
                'lignes_lues' => count($piece['lignes'] ?? []),
                'fichier_tronque' => (bool) ($piece['tronque'] ?? false),
                'feuilles' => $piece['feuilles'] ?? null,
                'feuilles_non_lues' => $piece['autres_feuilles'] ?? null,
                'resultats' => $resultats,
            ],
        ];
    }

    private function cle(string $valeur): string
    {
        $sansAccent = \Normalizer::normalize($valeur, \Normalizer::FORM_D);
        $sansAccent = preg_replace('/\p{Mn}+/u', '', (string) $sansAccent);

        return mb_strtolower(preg_replace('/\s+/u', '', (string) $sansAccent));
    }
}
