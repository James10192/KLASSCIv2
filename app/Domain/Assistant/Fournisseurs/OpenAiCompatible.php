<?php

namespace App\Domain\Assistant\Fournisseurs;

use App\Domain\Assistant\Modeles\ModeleIa;

/**
 * Adaptateur Chat Completions « compatible OpenAI » : OpenAI, Mistral,
 * DeepSeek, OpenRouter… ne diffèrent que par l'URL de base et la clé.
 *
 *   choices[0].delta.content          → texte
 *   choices[0].delta.tool_calls[i]    → outil_debut (premier morceau), arguments accumulés
 *   finish_reason / fin du flux       → outil (arguments recomposés), puis fin
 *   usage (stream_options.include_usage) → usage
 *   { "error": … }                    → erreur
 */
class OpenAiCompatible extends AdaptateurHttp
{
    public function diffuser(RequeteModele $requete, ModeleIa $modele, callable $arreter): iterable
    {
        [$corps, $erreur] = $this->ouvrir($modele, $modele->url . 'chat/completions', [
            'Content-Type' => 'application/json',
            'Accept' => 'text/event-stream',
            'Authorization' => 'Bearer ' . $modele->cleApi(),
        ], $this->corps($requete, $modele));

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
        $appels = [];
        $raison = null;
        $entree = 0;
        $sortie = 0;
        $recu = false;

        foreach ($sse as $evenement) {
            $d = $evenement['data'];

            if (isset($d['error'])) {
                $type = is_array($d['error']) ? ($d['error']['type'] ?? $d['error']['code'] ?? 'erreur') : 'erreur';
                yield EvenementModele::erreur('flux_' . (is_scalar($type) ? $type : 'erreur'));
                return;
            }

            if (isset($d['usage']) && is_array($d['usage'])) {
                $entree = (int) ($d['usage']['prompt_tokens'] ?? $entree);
                $sortie = (int) ($d['usage']['completion_tokens'] ?? $sortie);
            }

            $choix = $d['choices'][0] ?? null;
            if (!is_array($choix)) {
                continue;
            }
            $recu = true;
            $delta = $choix['delta'] ?? [];

            if (isset($delta['content']) && is_string($delta['content']) && $delta['content'] !== '') {
                yield EvenementModele::texte($delta['content']);
            }

            foreach ($delta['tool_calls'] ?? [] as $morceau) {
                $i = (int) ($morceau['index'] ?? 0);
                if (!isset($appels[$i])) {
                    $appels[$i] = ['id' => (string) ($morceau['id'] ?? 'appel_' . $i), 'nom' => '', 'json' => '', 'annonce' => false];
                }
                if (!empty($morceau['id'])) {
                    $appels[$i]['id'] = (string) $morceau['id'];
                }
                $appels[$i]['nom'] .= (string) ($morceau['function']['name'] ?? '');
                $appels[$i]['json'] .= (string) ($morceau['function']['arguments'] ?? '');

                if (!$appels[$i]['annonce'] && $appels[$i]['nom'] !== '') {
                    $appels[$i]['annonce'] = true;
                    yield EvenementModele::outilDebut($appels[$i]['id'], $appels[$i]['nom']);
                }
            }

            if (!empty($choix['finish_reason'])) {
                $raison = $choix['finish_reason'];
            }
        }

        if (!$recu) {
            yield EvenementModele::erreur('flux_vide');
            return;
        }

        ksort($appels);
        foreach ($appels as $appel) {
            $args = trim($appel['json']) === '' ? [] : json_decode($appel['json'], true);
            yield EvenementModele::outil($appel['id'], $appel['nom'], is_array($args) ? $args : []);
        }

        yield EvenementModele::usage($entree, $sortie);

        if ($raison === null) {
            yield EvenementModele::erreur('flux_interrompu');
            return;
        }

        yield EvenementModele::fin(match (true) {
            $appels !== [] || $raison === 'tool_calls' => 'outils',
            $raison === 'length' => 'longueur',
            default => 'fin',
        });
    }

    public function corps(RequeteModele $requete, ModeleIa $modele): array
    {
        $corps = [
            'model' => $modele->identifiant,
            'stream' => true,
            'stream_options' => ['include_usage' => true],
            'max_tokens' => $requete->maxTokens,
            'temperature' => $requete->temperature,
            'messages' => $this->messages($requete),
        ];

        if ($requete->outils !== [] && $modele->outils) {
            $corps['tools'] = array_map(fn (array $o) => [
                'type' => 'function',
                'function' => [
                    'name' => $o['nom'],
                    'description' => $o['description'],
                    'parameters' => $this->schemaPortable($o['parametres']) + ['type' => 'object'],
                ],
            ], $requete->outils);
            $corps['tool_choice'] = 'auto';
        }

        return $corps;
    }

    private function messages(RequeteModele $requete): array
    {
        $sortie = [['role' => 'system', 'content' => $requete->systeme]];

        foreach ($requete->messages as $m) {
            if ($m['role'] === 'outil') {
                $sortie[] = ['role' => 'tool', 'tool_call_id' => $m['id'], 'content' => $m['resultat']];
            } elseif ($m['role'] === 'assistant') {
                $message = ['role' => 'assistant', 'content' => ($m['texte'] ?? '') !== '' ? $m['texte'] : null];
                if (($m['appels'] ?? []) !== []) {
                    $message['tool_calls'] = array_map(fn (array $a) => [
                        'id' => $a['id'],
                        'type' => 'function',
                        'function' => ['name' => $a['nom'], 'arguments' => json_encode((object) $a['arguments'], JSON_UNESCAPED_UNICODE)],
                    ], $m['appels']);
                }
                if ($message['content'] === null && !isset($message['tool_calls'])) {
                    $message['content'] = '';
                }
                $sortie[] = $message;
            } else {
                $sortie[] = ['role' => 'user', 'content' => (string) ($m['texte'] ?? '')];
            }
        }

        return $sortie;
    }
}
