<?php

namespace App\Domain\Assistant\Fournisseurs;

use App\Domain\Assistant\Modeles\ModeleIa;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\StreamInterface;

/**
 * Socle HTTP des adaptateurs : requête POST diffusée (option Guzzle `stream`),
 * délais, et journalisation des pannes SANS le contenu des messages.
 */
abstract class AdaptateurHttp implements FournisseurDeModele
{
    /**
     * Ouvre le flux. Rend soit le corps à lire, soit un code d'erreur normalisé.
     *
     * @return array{0: ?StreamInterface, 1: ?string}
     */
    protected function ouvrir(ModeleIa $modele, string $url, array $entetes, array $corps): array
    {
        if (!$modele->estConfigure()) {
            return [null, 'non_configure'];
        }

        // Une surcharge passagère (429, 5xx, coupure réseau) se retente sur le MÊME
        // modèle avant de passer au suivant : sur une instance qui n'a qu'une clé,
        // il n'y a pas de suivant. Rien n'a encore été montré à ce stade, la
        // nouvelle tentative est donc invisible pour l'utilisateur.
        $tentatives = max(1, (int) config('assistant.limites.tentatives', 3));
        $pause = max(0, (int) config('assistant.limites.pause_ms', 600));

        for ($essai = 1; ; $essai++) {
            $code = null;
            $type = null;
            try {
                $reponse = Http::timeout((int) config('assistant.limites.delai_secondes', 90))
                    ->connectTimeout((int) config('assistant.limites.delai_connexion', 15))
                    ->withOptions(['stream' => true])
                    ->withHeaders($entetes)
                    ->post($url, $corps);
                if ($reponse->successful()) {
                    break;
                }
                $statut = $reponse->status();
                $code = $statut === 429 ? 'limite_debit' : 'http_' . $statut;
                $type = $this->typeErreur($reponse->body());
                $passager = $statut === 429 || $statut >= 500;
            } catch (\Throwable $e) {
                $code = 'reseau';
                $passager = true;
            }

            $this->journaliserPanne($modele, $code, $type);
            if (!$passager || $essai >= $tentatives) {
                return [null, $code];
            }
            usleep($pause * 1000 * $essai);
        }

        return [$reponse->toPsrResponse()->getBody(), null];
    }

    /** Type d'erreur annoncé par le fournisseur (« overloaded_error »…), jamais son message. */
    protected function typeErreur(string $corps): ?string
    {
        $json = json_decode(mb_substr($corps, 0, 4000), true);
        if (!is_array($json)) {
            return null;
        }
        $erreur = $json['error'] ?? $json[0]['error'] ?? null;
        $type = is_array($erreur) ? ($erreur['type'] ?? $erreur['status'] ?? $erreur['code'] ?? null) : null;

        return is_scalar($type) ? mb_substr((string) $type, 0, 60) : null;
    }

    protected function journaliserPanne(ModeleIa $modele, string $code, ?string $type): void
    {
        Log::warning('assistant.fournisseur_en_echec', [
            'fournisseur' => $modele->fournisseur,
            'modele' => $modele->cle,
            'code' => $code,
            'type' => $type,
        ]);
    }

    /**
     * Retire d'un schéma JSON ce que certaines API refusent. Liste blanche :
     * ce qui n'y est pas ne part pas.
     */
    protected function schemaPortable(array $schema): array
    {
        $garde = ['type', 'description', 'properties', 'required', 'items', 'enum', 'format', 'nullable', 'minimum', 'maximum'];
        $propre = array_intersect_key($schema, array_flip($garde));

        if (isset($propre['properties']) && is_array($propre['properties'])) {
            $propre['properties'] = array_map(
                fn ($sous) => is_array($sous) ? $this->schemaPortable($sous) : $sous,
                $propre['properties']
            );
            if ($propre['properties'] === []) {
                $propre['properties'] = new \stdClass();
            }
        }
        if (isset($propre['items']) && is_array($propre['items'])) {
            $propre['items'] = $this->schemaPortable($propre['items']);
        }
        if (isset($propre['required']) && $propre['required'] === []) {
            unset($propre['required']);
        }

        return $propre;
    }
}
