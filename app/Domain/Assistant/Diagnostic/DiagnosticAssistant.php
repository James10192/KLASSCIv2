<?php

namespace App\Domain\Assistant\Diagnostic;

use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Modeles\RegistreDesModeles;

/**
 * Essai réel des modèles configurés, sans conversation ni données d'école :
 * une réponse courte, puis un appel d'outil fictif. C'est ce que lancent le
 * bouton « Tester » des réglages et `klassci assistant:test`.
 *
 * Le second essai vaut le premier : un modèle qui répond mais ne sait pas
 * appeler d'outil rend un assistant qui invente au lieu de lire les données.
 */
class DiagnosticAssistant
{
    public function __construct(private RegistreDesModeles $registre)
    {
    }

    /**
     * @return array{modele_defaut: string, resultats: list<array<string, mixed>>}
     */
    public function tester(?string $cleModele = null, int $max = 3): array
    {
        $modeles = $cleModele !== null
            ? array_values(array_filter([$this->registre->tous()[$cleModele] ?? null]))
            : array_slice($this->registre->candidats(), 0, max(1, $max));

        $resultats = [];
        foreach ($modeles as $modele) {
            $resultats[] = $this->essayer($modele);
        }

        return ['modele_defaut' => $this->registre->defaut(), 'resultats' => $resultats];
    }

    /** @return array<string, mixed> */
    private function essayer(ModeleIa $modele): array
    {
        $base = [
            'modele' => $modele->cle,
            'libelle' => $modele->libelle,
            'fournisseur' => $modele->fournisseur,
            'identifiant' => $modele->identifiant,
        ];

        if (!$modele->estConfigure()) {
            return $base + ['configure' => false, 'texte' => null, 'outils' => null];
        }

        $texte = $this->lancer($modele, new RequeteModele(
            systeme: 'Test de connexion. Réponds uniquement par le mot OK.',
            messages: [['role' => 'user', 'texte' => 'Test']],
            maxTokens: 20,
            temperature: 0.0,
        ));

        $outils = $texte['erreur'] !== null ? null : $this->lancer($modele, new RequeteModele(
            systeme: "Test d'appel d'outil. Appelle l'outil donner_heure avec fuseau = UTC, sans rien écrire d'autre.",
            messages: [['role' => 'user', 'texte' => 'Quelle heure est-il en UTC ?']],
            outils: [[
                'nom' => 'donner_heure',
                'description' => "Donne l'heure courante dans un fuseau horaire.",
                'parametres' => [
                    'type' => 'object',
                    'properties' => ['fuseau' => ['type' => 'string', 'description' => 'Fuseau horaire, par exemple UTC']],
                    'required' => ['fuseau'],
                ],
            ]],
            maxTokens: 120,
            temperature: 0.0,
        ));

        return $base + [
            'configure' => true,
            'texte' => [
                'ok' => $texte['erreur'] === null && trim($texte['texte']) !== '',
                'reponse' => mb_substr(trim($texte['texte']), 0, 80),
                'ms' => $texte['ms'],
                'erreur' => $texte['erreur'],
                'jetons' => $texte['jetons'],
            ],
            'outils' => $outils === null ? null : [
                'ok' => $outils['erreur'] === null && in_array('donner_heure', $outils['outils'], true),
                'appels' => $outils['outils'],
                'ms' => $outils['ms'],
                'erreur' => $outils['erreur'],
            ],
        ];
    }

    /** @return array{texte: string, outils: string[], erreur: ?string, ms: int, jetons: int} */
    private function lancer(ModeleIa $modele, RequeteModele $requete): array
    {
        $debut = microtime(true);
        $texte = '';
        $outils = [];
        $erreur = null;
        $jetons = 0;

        $fournisseur = app(config('assistant.adaptateurs.' . $modele->adaptateur));
        foreach ($fournisseur->diffuser($requete, $modele, fn () => false) as $evenement) {
            match ($evenement->type) {
                EvenementModele::TEXTE => $texte .= $evenement->donnees['delta'],
                EvenementModele::OUTIL => $outils[] = (string) ($evenement->donnees['nom'] ?? ''),
                EvenementModele::USAGE => $jetons = (int) ($evenement->donnees['entree'] ?? 0) + (int) ($evenement->donnees['sortie'] ?? 0),
                EvenementModele::ERREUR => $erreur = (string) ($evenement->donnees['code'] ?? 'inconnu'),
                default => null,
            };
            if ($erreur !== null) {
                break;
            }
        }

        return ['texte' => $texte, 'outils' => $outils, 'erreur' => $erreur, 'ms' => (int) round((microtime(true) - $debut) * 1000), 'jetons' => $jetons];
    }
}
