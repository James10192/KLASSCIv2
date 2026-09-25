<?php

namespace App\Domain\Assistant\Fournisseurs;

use App\Domain\Assistant\Modeles\ModeleIa;

/**
 * Adaptateur API Messages d'Anthropic ("stream": true).
 *
 *   content_block_delta text_delta       → texte
 *   content_block_start tool_use         → outil_debut
 *   input_json_delta … content_block_stop → outil (arguments recomposés)
 *   message_start / message_delta usage  → usage
 *   message_delta stop_reason            → fin (tool_use → outils, max_tokens → longueur)
 *   error                                → erreur
 *
 * Le prompt système porte `cache_control: ephemeral` (mise en cache du prompt),
 * comme l'ancien service Claude.
 */
class Anthropic extends AdaptateurHttp
{
    public function diffuser(RequeteModele $requete, ModeleIa $modele, callable $arreter): iterable
    {
        [$corps, $erreur] = $this->ouvrir($modele, $modele->url . 'messages', $this->entetes($modele), $this->corps($requete, $modele));
        if ($erreur) {
            yield EvenementModele::erreur($erreur);
            return;
        }

        yield from $this->traduire(LecteurSse::depuisCorps($corps, $arreter));
    }

    /**
     * @param iterable<array{event: ?string, data: array}> $sse
     * @return \Generator<EvenementModele>
     */
    public function traduire(iterable $sse): \Generator
    {
        $outils = [];
        $entree = 0;
        $sortie = 0;
        $raison = null;
        $termine = false;

        foreach ($sse as $evenement) {
            $d = $evenement['data'];
            switch ($d['type'] ?? $evenement['event']) {
                case 'message_start':
                    $entree = (int) ($d['message']['usage']['input_tokens'] ?? 0)
                        + (int) ($d['message']['usage']['cache_read_input_tokens'] ?? 0)
                        + (int) ($d['message']['usage']['cache_creation_input_tokens'] ?? 0);
                    break;

                case 'content_block_start':
                    $bloc = $d['content_block'] ?? [];
                    if (($bloc['type'] ?? null) === 'tool_use') {
                        $outils[(int) ($d['index'] ?? 0)] = ['id' => (string) $bloc['id'], 'nom' => (string) $bloc['name'], 'json' => ''];
                        yield EvenementModele::outilDebut((string) $bloc['id'], (string) $bloc['name']);
                    } elseif (($bloc['type'] ?? null) === 'text' && ($bloc['text'] ?? '') !== '') {
                        yield EvenementModele::texte((string) $bloc['text']);
                    }
                    break;

                case 'content_block_delta':
                    $delta = $d['delta'] ?? [];
                    $index = (int) ($d['index'] ?? 0);
                    if (($delta['type'] ?? null) === 'text_delta' && ($delta['text'] ?? '') !== '') {
                        yield EvenementModele::texte((string) $delta['text']);
                    } elseif (($delta['type'] ?? null) === 'input_json_delta' && isset($outils[$index])) {
                        $outils[$index]['json'] .= (string) ($delta['partial_json'] ?? '');
                    }
                    break;

                case 'content_block_stop':
                    $index = (int) ($d['index'] ?? 0);
                    if (isset($outils[$index])) {
                        $o = $outils[$index];
                        unset($outils[$index]);
                        $args = trim($o['json']) === '' ? [] : json_decode($o['json'], true);
                        yield EvenementModele::outil($o['id'], $o['nom'], is_array($args) ? $args : []);
                    }
                    break;

                case 'message_delta':
                    $raison = $d['delta']['stop_reason'] ?? $raison;
                    $sortie = (int) ($d['usage']['output_tokens'] ?? $sortie);
                    break;

                case 'message_stop':
                    $termine = true;
                    break;

                case 'error':
                    yield EvenementModele::erreur('flux_' . ($d['error']['type'] ?? 'erreur'));
                    return;
            }
        }

        yield EvenementModele::usage($entree, $sortie);

        if (!$termine) {
            yield EvenementModele::erreur('flux_interrompu');
            return;
        }

        yield EvenementModele::fin(match ($raison) {
            'tool_use' => 'outils',
            'max_tokens' => 'longueur',
            default => 'fin',
        });
    }

    protected function entetes(ModeleIa $modele): array
    {
        $cle = $modele->cleApi();
        $entetes = [
            'Content-Type' => 'application/json',
            'Accept' => 'text/event-stream',
            'anthropic-version' => '2023-06-01',
        ];

        if (str_starts_with($cle, 'sk-ant-oat')) {
            $entetes['Authorization'] = 'Bearer ' . $cle;
            $entetes['anthropic-beta'] = 'oauth-2025-04-20';
        } else {
            $entetes['x-api-key'] = $cle;
            $entetes['anthropic-beta'] = 'prompt-caching-2024-07-31';
        }

        return $entetes;
    }

    public function corps(RequeteModele $requete, ModeleIa $modele): array
    {
        $oauth = str_starts_with($modele->cleApi(), 'sk-ant-oat');

        $corps = [
            'model' => $modele->identifiant,
            'max_tokens' => $requete->maxTokens,
            'temperature' => $requete->temperature,
            'stream' => true,
            'system' => $oauth
                ? $requete->systeme
                : [['type' => 'text', 'text' => $requete->systeme, 'cache_control' => ['type' => 'ephemeral']]],
            'messages' => $this->messages($requete->messages),
        ];

        if ($requete->outils !== [] && $modele->outils) {
            $corps['tools'] = array_map(fn (array $o) => [
                'name' => $o['nom'],
                'description' => $o['description'],
                'input_schema' => $this->schemaPortable($o['parametres']) + ['type' => 'object'],
            ], $requete->outils);
            $corps['tool_choice'] = ['type' => $requete->conclure ? 'none' : 'auto'];
        }

        return $corps;
    }

    private function messages(array $messages): array
    {
        $sortie = [];

        foreach ($messages as $m) {
            if ($m['role'] === 'outil') {
                $resultat = ['type' => 'tool_result', 'tool_use_id' => $m['id'], 'content' => $m['resultat']];
                $dernier = end($sortie);
                if ($dernier && $dernier['role'] === 'user' && is_array($dernier['content'])
                    && ($dernier['content'][0]['type'] ?? null) === 'tool_result') {
                    $sortie[array_key_last($sortie)]['content'][] = $resultat;
                } else {
                    $sortie[] = ['role' => 'user', 'content' => [$resultat]];
                }
                continue;
            }

            if ($m['role'] === 'assistant') {
                $blocs = [];
                if (($m['texte'] ?? '') !== '') {
                    $blocs[] = ['type' => 'text', 'text' => $m['texte']];
                }
                foreach ($m['appels'] ?? [] as $appel) {
                    $blocs[] = ['type' => 'tool_use', 'id' => $appel['id'], 'name' => $appel['nom'], 'input' => (object) $appel['arguments']];
                }
                $sortie[] = ['role' => 'assistant', 'content' => $blocs !== [] && ($m['appels'] ?? []) !== [] ? $blocs : ($m['texte'] ?? '')];
                continue;
            }

            $sortie[] = ['role' => 'user', 'content' => (string) ($m['texte'] ?? '')];
        }

        return $sortie;
    }
}
