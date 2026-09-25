<?php

namespace App\Domain\Assistant\Harnais;

use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\FournisseurDeModele;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use Illuminate\Support\Facades\Log;

/**
 * Boucle d'agent, indépendante du fournisseur.
 *
 * Un tour = un appel au modèle = un « step » du protocole UI message stream :
 * start-step, texte diffusé (text-start/delta/end), puces d'outil (data-outil,
 * « en cours » puis « terminé »), finish-step. Si le modèle demande des outils,
 * la boucle les exécute (CatalogueOutils, permissions revérifiées), renvoie
 * les résultats et relance un tour.
 *
 * Garde-fous, tous réglés dans config/assistant.php :
 *  - nombre de tours maximal ;
 *  - budget de jetons (entrée + sortie cumulées) ;
 *  - délai global de l'échange.
 *
 * Repli : si un fournisseur échoue AVANT d'avoir montré quoi que ce soit, le
 * modèle suivant de la chaîne reprend l'échange. Après un début de réponse, on
 * ne change plus de modèle en silence : une partie `error` explique l'arrêt.
 */
class BoucleAgent
{
    public function __construct(private CatalogueOutils $catalogue)
    {
    }

    /**
     * @param ModeleIa[] $candidats ordre d'essai (RegistreDesModeles::candidats)
     * @param callable(string $nom, array $arguments, array $resultat):void|null $surResultat
     */
    public function executer(array $candidats, RequeteModele $requete, $user, UiMessageStream $ui, ?callable $surResultat = null): ResultatBoucle
    {
        $maxTours = max(1, (int) config('assistant.limites.tours', 4));
        $budget = (int) config('assistant.limites.budget_tokens', 60000);
        $echeance = microtime(true) + (int) config('assistant.limites.delai_secondes', 90);
        $debut = microtime(true);

        $essais = [];
        $appelsFaits = [];
        $texte = '';
        $texteTour = '';
        $entree = 0;
        $sortie = 0;
        $tours = 0;
        $montre = false;
        $statut = 'ok';
        $modele = array_shift($candidats);
        $delaiDepasse = false;

        $arreter = function () use ($ui, $echeance, &$delaiDepasse): bool {
            if ($ui->aborted()) {
                return true;
            }
            if (microtime(true) > $echeance) {
                $delaiDepasse = true;
                return true;
            }
            return false;
        };

        if (!$modele) {
            $ui->error("Aucun modèle d'IA n'est configuré pour cette école.");
            return $this->resultat('erreur', '', '', [], null, [], 0, 0, 0, $debut);
        }

        while ($tours < $maxTours) {
            $tours++;
            $ui->startStep();

            $tour = $this->unTour($this->fournisseur($modele), $requete, $modele, $ui, $arreter);
            $entree += $tour['entree'];
            $sortie += $tour['sortie'];

            if ($tour['texte'] !== '' || $tour['appels'] !== []) {
                $montre = true;
            }

            if ($ui->aborted()) {
                $ui->finishStep();
                $statut = 'interrompu';
                $texte = $this->joindre($texte, $tour['texte']);
                break;
            }

            if ($tour['erreur'] !== null || $delaiDepasse) {
                $code = $delaiDepasse ? 'delai' : $tour['erreur'];
                $essais[] = ['modele' => $modele->cle, 'code' => $code];
                Log::warning('assistant.tour_en_echec', ['fournisseur' => $modele->fournisseur, 'modele' => $modele->cle, 'code' => $code, 'tour' => $tours]);
                $ui->finishStep();

                if (!$montre && !$delaiDepasse && $candidats !== []) {
                    // Les puces d'outil annoncées par le modèle abandonné ne décrivent
                    // plus rien : le suivant repart de zéro. On les retire.
                    foreach ($tour['puces'] as $puce) {
                        $ui->data('outil', ['nom' => $puce['nom'], 'etat' => 'retire'], $puce['id']);
                    }
                    $modele = array_shift($candidats);
                    $tours--;
                    continue;
                }

                $texte = $this->joindre($texte, $tour['texte']);
                $ui->error($delaiDepasse
                    ? "La réponse prend trop de temps. Réessayez avec une question plus précise."
                    : "L'assistant n'a pas pu terminer sa réponse. Réessayez dans un instant.");
                $statut = 'erreur';
                break;
            }

            $texteTour = $tour['texte'];
            $texte = $this->joindre($texte, $tour['texte']);

            if ($tour['raison'] !== 'outils' || $tour['appels'] === []) {
                $ui->finishStep();
                break;
            }

            // Le modèle demande des outils : on les exécute, puis on relance un tour.
            $messages = $requete->messages;
            $messages[] = ['role' => 'assistant', 'texte' => $tour['texte'], 'appels' => $tour['appels']];
            foreach ($tour['appels'] as $appel) {
                $resultat = $this->catalogue->executer($appel['nom'], $appel['arguments'], $user);
                $ok = !isset($resultat['error']);
                $ui->data('outil', [
                    'nom' => $appel['nom'],
                    'libelle' => $this->catalogue->libelle($appel['nom']),
                    'etat' => $ok ? 'termine' : 'echec',
                ], $appel['id']);

                if ($ok) {
                    $appelsFaits[] = ['tool' => $appel['nom'], 'args' => $appel['arguments'], 'result_count' => $resultat['count'] ?? null];
                    if ($surResultat) {
                        $surResultat($appel['nom'], $appel['arguments'], $resultat);
                    }
                }

                $messages[] = [
                    'role' => 'outil',
                    'id' => $appel['id'],
                    'nom' => $appel['nom'],
                    'resultat' => json_encode($resultat, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
                ];
            }
            $requete = $requete->avecMessages($messages);
            $texteTour = '';
            $ui->finishStep();

            if ($budget > 0 && ($entree + $sortie) >= $budget) {
                $statut = 'limite';
                break;
            }
            if ($tours >= $maxTours) {
                $statut = 'limite';
            }
        }

        return $this->resultat($statut, $texte, $texteTour, $appelsFaits, $modele, $essais, $entree, $sortie, $tours, $debut);
    }

    /**
     * Un appel au modèle : diffuse le texte au fil de l'eau et collecte les appels d'outil.
     */
    private function unTour(FournisseurDeModele $fournisseur, RequeteModele $requete, ModeleIa $modele, UiMessageStream $ui, callable $arreter): array
    {
        $tour = ['texte' => '', 'appels' => [], 'puces' => [], 'raison' => null, 'erreur' => null, 'entree' => 0, 'sortie' => 0];
        $idTexte = null;

        try {
            foreach ($fournisseur->diffuser($requete, $modele, $arreter) as $evenement) {
                if ($arreter()) {
                    break;
                }

                switch ($evenement->type) {
                    case EvenementModele::TEXTE:
                        if ($idTexte === null) {
                            $idTexte = $ui->nextTextId();
                            $ui->textStart($idTexte);
                        }
                        $ui->textDelta($idTexte, $evenement->donnees['delta']);
                        $tour['texte'] .= $evenement->donnees['delta'];
                        break;

                    case EvenementModele::OUTIL_DEBUT:
                        $this->fermerTexte($ui, $idTexte);
                        $ui->data('outil', [
                            'nom' => $evenement->donnees['nom'],
                            'libelle' => $this->catalogue->libelle($evenement->donnees['nom']),
                            'etat' => 'en_cours',
                        ], $evenement->donnees['id']);
                        $tour['puces'][] = ['id' => $evenement->donnees['id'], 'nom' => $evenement->donnees['nom']];
                        break;

                    case EvenementModele::OUTIL:
                        $tour['appels'][] = $evenement->donnees;
                        break;

                    case EvenementModele::USAGE:
                        $tour['entree'] = $evenement->donnees['entree'];
                        $tour['sortie'] = $evenement->donnees['sortie'];
                        break;

                    case EvenementModele::FIN:
                        $tour['raison'] = $evenement->donnees['raison'];
                        break;

                    case EvenementModele::ERREUR:
                        $tour['erreur'] = $evenement->donnees['code'];
                        break 2;
                }
            }
        } catch (\Throwable $e) {
            // Un adaptateur ne doit pas lever ; s'il le fait, c'est une panne comme une autre.
            Log::error('assistant.adaptateur_exception', ['fournisseur' => $modele->fournisseur, 'modele' => $modele->cle, 'exception' => get_class($e)]);
            $tour['erreur'] = 'exception';
        }

        $this->fermerTexte($ui, $idTexte);

        if ($tour['erreur'] === null && $tour['raison'] === null && !$ui->aborted()) {
            $tour['erreur'] = 'flux_incomplet';
        }

        return $tour;
    }

    private function fermerTexte(UiMessageStream $ui, ?string &$idTexte): void
    {
        if ($idTexte !== null) {
            $ui->textEnd($idTexte);
            $idTexte = null;
        }
    }

    private function fournisseur(ModeleIa $modele): FournisseurDeModele
    {
        $classe = config('assistant.adaptateurs.' . $modele->adaptateur);

        return app($classe);
    }

    private function joindre(string $texte, string $ajout): string
    {
        if ($ajout === '') {
            return $texte;
        }

        return $texte === '' ? $ajout : $texte . "\n\n" . $ajout;
    }

    private function resultat(string $statut, string $texte, string $texteTour, array $appels, ?ModeleIa $modele, array $essais, int $entree, int $sortie, int $tours, float $debut): ResultatBoucle
    {
        return new ResultatBoucle(
            statut: $statut,
            texte: $texte,
            texteDernierTour: $texteTour,
            appels: $appels,
            modele: $modele?->cle,
            fournisseur: $modele?->fournisseur,
            essais: $essais,
            tokensEntree: $entree,
            tokensSortie: $sortie,
            tours: $tours,
            latenceMs: (int) round((microtime(true) - $debut) * 1000),
        );
    }
}
