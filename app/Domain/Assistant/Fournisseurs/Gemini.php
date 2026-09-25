<?php

namespace App\Domain\Assistant\Fournisseurs;

use App\Domain\Assistant\Modeles\ModeleIa;

/**
 * Adaptateur Google Gemini (models/{modele}:streamGenerateContent?alt=sse).
 *
 *   candidates[0].content.parts[].text          → texte
 *   candidates[0].content.parts[].functionCall  → outil_debut + outil (Gemini livre l'appel entier)
 *   usageMetadata (cumulatif)                   → usage
 *   finishReason                                → fin (MAX_TOKENS → longueur)
 *   { "error": … }                              → erreur
 *
 * Gemini ne numérote pas ses appels d'outil : on leur donne un identifiant
 * local, et le nom de l'outil accompagne sa réponse (functionResponse).
 */
class Gemini extends AdaptateurHttp
{
    public function diffuser(RequeteModele $requete, ModeleIa $modele, callable $arreter): iterable
    {
        $url = $modele->url . 'models/' . rawurlencode($modele->identifiant) . ':streamGenerateContent?alt=sse';
        [$corps, $erreur] = $this->ouvrir($modele, $url, [
            'Content-Type' => 'application/json',
            'Accept' => 'text/event-stream',
            'x-goog-api-key' => $modele->cleApi(),
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
        $raison = null;
        $entree = 0;
        $sortie = 0;
        $nbAppels = 0;

        foreach ($sse as $evenement) {
            $d = $evenement['data'];

            if (isset($d['error'])) {
                $statut = is_array($d['error']) ? ($d['error']['status'] ?? 'erreur') : 'erreur';
                yield EvenementModele::erreur('flux_' . (is_scalar($statut) ? strtolower((string) $statut) : 'erreur'));
                return;
            }

            if (isset($d['usageMetadata'])) {
                $entree = (int) ($d['usageMetadata']['promptTokenCount'] ?? $entree);
                $sortie = (int) ($d['usageMetadata']['candidatesTokenCount'] ?? $sortie);
            }

            $candidat = $d['candidates'][0] ?? null;
            if (!is_array($candidat)) {
                continue;
            }

            foreach ($candidat['content']['parts'] ?? [] as $part) {
                if (isset($part['text']) && is_string($part['text']) && $part['text'] !== '' && empty($part['thought'])) {
                    yield EvenementModele::texte($part['text']);
                } elseif (isset($part['functionCall']['name'])) {
                    $nbAppels++;
                    $id = 'gemini_' . $nbAppels;
                    $nom = (string) $part['functionCall']['name'];
                    $args = $part['functionCall']['args'] ?? [];
                    yield EvenementModele::outilDebut($id, $nom);
                    yield EvenementModele::outil($id, $nom, is_array($args) ? $args : []);
                }
            }

            if (!empty($candidat['finishReason'])) {
                $raison = $candidat['finishReason'];
            }
        }

        yield EvenementModele::usage($entree, $sortie);

        if ($raison === null) {
            yield EvenementModele::erreur('flux_interrompu');
            return;
        }

        if (in_array($raison, ['SAFETY', 'RECITATION', 'BLOCKLIST', 'PROHIBITED_CONTENT'], true) && $nbAppels === 0) {
            yield EvenementModele::erreur('refus_' . strtolower($raison));
            return;
        }

        yield EvenementModele::fin(match (true) {
            $nbAppels > 0 => 'outils',
            $raison === 'MAX_TOKENS' => 'longueur',
            default => 'fin',
        });
    }

    public function corps(RequeteModele $requete, ModeleIa $modele): array
    {
        $corps = [
            'systemInstruction' => ['parts' => [['text' => $requete->systeme]]],
            'contents' => $this->contenus($requete->messages),
            'generationConfig' => [
                'maxOutputTokens' => $requete->maxTokens,
                'temperature' => $requete->temperature,
            ],
        ];

        if ($requete->outils !== [] && $modele->outils) {
            $corps['tools'] = [[
                'functionDeclarations' => array_map(fn (array $o) => [
                    'name' => $o['nom'],
                    'description' => $o['description'],
                    'parameters' => $this->schemaPortable($o['parametres']) + ['type' => 'object'],
                ], $requete->outils),
            ]];
            if ($requete->conclure) {
                $corps['toolConfig'] = ['functionCallingConfig' => ['mode' => 'NONE']];
            }
        }

        return $corps;
    }

    private function contenus(array $messages): array
    {
        $sortie = [];

        foreach ($messages as $m) {
            if ($m['role'] === 'outil') {
                $decode = json_decode((string) $m['resultat'], true);
                $part = ['functionResponse' => [
                    'name' => $m['nom'],
                    'response' => is_array($decode) && !array_is_list($decode) ? $decode : ['resultat' => $decode ?? $m['resultat']],
                ]];
                $dernier = end($sortie);
                if ($dernier && $dernier['role'] === 'user' && isset($dernier['parts'][0]['functionResponse'])) {
                    $sortie[array_key_last($sortie)]['parts'][] = $part;
                } else {
                    $sortie[] = ['role' => 'user', 'parts' => [$part]];
                }
                continue;
            }

            if ($m['role'] === 'assistant') {
                $parts = [];
                if (($m['texte'] ?? '') !== '') {
                    $parts[] = ['text' => $m['texte']];
                }
                foreach ($m['appels'] ?? [] as $a) {
                    $parts[] = ['functionCall' => ['name' => $a['nom'], 'args' => (object) $a['arguments']]];
                }
                $sortie[] = ['role' => 'model', 'parts' => $parts !== [] ? $parts : [['text' => '']]];
                continue;
            }

            $sortie[] = ['role' => 'user', 'parts' => [['text' => (string) ($m['texte'] ?? '')]]];
        }

        return $sortie;
    }
}
