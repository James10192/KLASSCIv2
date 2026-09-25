<?php

namespace App\Domain\Assistant\Harnais;

use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\EvenementModele;
use App\Domain\Assistant\Fournisseurs\FournisseurDeModele;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Assistant\Outils\ResumeOutil;
use Illuminate\Support\Facades\Log;

/**
 * Boucle d'agent, indépendante du fournisseur.
 *
 * Un tour = un appel au modèle = un « step » du protocole UI message stream :
 * start-step, texte diffusé (text-start/delta/end), étapes (data-etape,
 * « en cours » puis « terminée » avec un résumé), finish-step. Si le modèle
 * demande des outils, la boucle les exécute (CatalogueOutils, permissions
 * revérifiées), montre le widget de chaque résultat juste sous son étape
 * (data-widget), renvoie au modèle une version COMPACTE du résultat
 * (ResumeOutil) et relance un tour.
 *
 * Un appel identique à un appel déjà fait dans l'échange (même outil, mêmes
 * arguments) n'est pas rejoué : le modèle reçoit le résultat déjà obtenu, et
 * l'écran ne montre ni seconde étape ni second widget.
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
    /** Numéro du dernier identifiant d'appel attribué dans l'échange en cours. */
    private int $numeroAppel = 0;

    public function __construct(private CatalogueOutils $catalogue)
    {
    }

    /**
     * @param ModeleIa[] $candidats ordre d'essai (RegistreDesModeles::candidats)
     * @param callable(string $nom, array $arguments, array $resultat):?array|null $surResultat
     *        rend le widget du résultat (ou null)
     */
    public function executer(array $candidats, RequeteModele $requete, $user, UiMessageStream $ui, ?callable $surResultat = null, ?FilDeReponse $fil = null): ResultatBoucle
    {
        $fil ??= new FilDeReponse();
        $this->numeroAppel = 0;
        $deja = [];
        $trace = [];
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

            // Dernier tour permis, après des outils : le modèle doit conclure avec ce
            // qu'il a. Ses outils restent déclarés mais il ne peut plus les appeler,
            // sinon l'utilisateur ne recevrait que des étapes sans réponse.
            $requeteTour = ($tours === $maxTours && $tours > 1) ? $requete->pourConclure() : $requete;
            $tour = $this->unTour($this->fournisseur($modele), $requeteTour, $modele, $ui, $arreter, $fil);
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
                Log::error('assistant.tour_en_echec', ['fournisseur' => $modele->fournisseur, 'modele' => $modele->cle, 'code' => $code, 'tour' => $tours]);
                $ui->finishStep();

                if (!$montre && !$delaiDepasse && $candidats !== []) {
                    // Les puces d'outil annoncées par le modèle abandonné ne décrivent
                    // plus rien : le suivant repart de zéro. On les retire.
                    foreach ($tour['puces'] as $puce) {
                        $ui->data('etape', ['nom' => $puce['nom'], 'etat' => 'retire'], $puce['id']);
                        $fil->retirerEtape($puce['id']);
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
            $messageAssistant = ['role' => 'assistant', 'texte' => $tour['texte'], 'appels' => $tour['appels']];
            $messages[] = $messageAssistant;
            $trace[] = $messageAssistant;
            foreach ($tour['appels'] as $appel) {
                $messageOutil = $this->executerAppel($appel, $user, $ui, $fil, $surResultat, $deja, $appelsFaits);
                $messages[] = $messageOutil;
                $trace[] = $messageOutil;
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

        return $this->resultat($statut, $texte, $texteTour, $appelsFaits, $modele, $essais, $entree, $sortie, $tours, $debut, $trace);
    }

    /**
     * Exécute UN appel d'outil : étape terminée à l'écran, widget sous l'étape,
     * et le message `outil` compact que le modèle recevra.
     *
     * @param array<string, string> $deja résultats déjà obtenus dans l'échange, par appel
     */
    private function executerAppel(array $appel, $user, UiMessageStream $ui, FilDeReponse $fil, ?callable $surResultat, array &$deja, array &$appelsFaits): array
    {
        $cle = $appel['nom'] . ':' . json_encode($this->trier($appel['arguments']));
        $message = ['role' => 'outil', 'id' => $appel['id'], 'nom' => $appel['nom']];

        if (isset($deja[$cle])) {
            // Déjà obtenu dans cet échange : on le redonne, sans rien réafficher.
            $ui->data('etape', ['nom' => $appel['nom'], 'etat' => 'retire'], $appel['id']);
            $fil->retirerEtape($appel['id']);

            return $message + ['resultat' => $deja[$cle] . "\n(Appel identique déjà fait dans cet échange : utilise ce résultat.)"];
        }

        $debut = microtime(true);
        $resultat = $this->catalogue->executer($appel['nom'], $appel['arguments'], $user);
        $ok = !isset($resultat['error']);
        $widget = ($ok && $surResultat) ? $surResultat($appel['nom'], $appel['arguments'], $resultat) : null;

        $etape = [
            'nom' => $appel['nom'],
            'libelle' => $this->catalogue->libelle($appel['nom']),
            'etat' => $ok ? 'termine' : 'echec',
            'resume' => ResumeOutil::resumeCourt($appel['nom'], $resultat),
            'detail' => $ok ? $this->detail($appel['arguments']) : null,
            'duree_ms' => (int) round((microtime(true) - $debut) * 1000),
        ];
        $ui->data('etape', $etape, $appel['id']);
        $fil->etape($appel['id'], $etape);

        if ($widget) {
            $ui->data('widget', $widget, $appel['id']);
            $fil->widget($appel['id'], $widget);
        }

        $pourModele = ResumeOutil::pourModele($appel['nom'], $resultat, $widget !== null);
        if ($ok) {
            // Seul un succès est mémorisé : un échec passager peut être retenté.
            $deja[$cle] = $pourModele;
            $appelsFaits[] = ['tool' => $appel['nom'], 'args' => $appel['arguments'], 'result_count' => $resultat['count'] ?? null];
        }

        return $message + ['resultat' => $pourModele];
    }

    /** Identifiant d'appel propre à l'échange : 9 caractères alphanumériques, accepté par toutes les API. */
    private function nouvelIdentifiant(): string
    {
        return 'a' . str_pad((string) ++$this->numeroAppel, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Un appel au modèle : diffuse le texte au fil de l'eau et collecte les appels d'outil.
     */
    private function unTour(FournisseurDeModele $fournisseur, RequeteModele $requete, ModeleIa $modele, UiMessageStream $ui, callable $arreter, FilDeReponse $fil): array
    {
        $tour = ['texte' => '', 'appels' => [], 'puces' => [], 'raison' => null, 'erreur' => null, 'entree' => 0, 'sortie' => 0];
        $idTexte = null;
        $bloc = '';
        // Identifiant du fournisseur → le nôtre. Les fournisseurs ne garantissent ni
        // l'unicité d'un tour à l'autre (Gemini recompte depuis 1) ni un format
        // commun (Mistral exige 9 caractères) : l'écran, le fil, la trace et les
        // messages suivants n'emploient que l'identifiant attribué ici.
        $ids = [];

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
                        $bloc .= $evenement->donnees['delta'];
                        break;

                    case EvenementModele::OUTIL_DEBUT:
                        $this->fermerTexte($ui, $idTexte, $fil, $bloc);
                        $id = $ids[$evenement->donnees['id']] = $this->nouvelIdentifiant();
                        $enCours = [
                            'nom' => $evenement->donnees['nom'],
                            'libelle' => $this->catalogue->libelle($evenement->donnees['nom']),
                            'etat' => 'en_cours',
                        ];
                        $ui->data('etape', $enCours, $id);
                        $fil->etape($id, $enCours);
                        $tour['puces'][] = ['id' => $id, 'nom' => $evenement->donnees['nom']];
                        break;

                    case EvenementModele::OUTIL:
                        $appel = $evenement->donnees;
                        $appel['id'] = $ids[$appel['id']] ?? $this->nouvelIdentifiant();
                        $tour['appels'][] = $appel;
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

        $this->fermerTexte($ui, $idTexte, $fil, $bloc);

        if ($tour['erreur'] === null && $tour['raison'] === null && !$ui->aborted()) {
            $tour['erreur'] = 'flux_incomplet';
        }

        return $tour;
    }

    private function fermerTexte(UiMessageStream $ui, ?string &$idTexte, FilDeReponse $fil, string &$bloc): void
    {
        if ($idTexte !== null) {
            $ui->textEnd($idTexte);
            $idTexte = null;
        }
        if ($bloc !== '') {
            $fil->texte($bloc);
            $bloc = '';
        }
    }

    /** Arguments lisibles sous l'étape : « classe : 2A BTS · période : S1 ». */
    private function detail(array $arguments): ?string
    {
        $morceaux = [];
        foreach ($arguments as $cle => $valeur) {
            if (is_scalar($valeur) && $valeur !== '' && $valeur !== null) {
                $valeur = is_bool($valeur) ? ($valeur ? 'oui' : 'non') : (string) $valeur;
                $morceaux[] = str_replace('_', ' ', (string) $cle) . ' : ' . mb_strimwidth($valeur, 0, 40, '…', 'UTF-8');
            }
            if (count($morceaux) === 3) {
                break;
            }
        }

        return $morceaux === [] ? null : implode(' · ', $morceaux);
    }

    /** Arguments dans un ordre stable, pour reconnaître deux appels identiques. */
    private function trier(array $arguments): array
    {
        ksort($arguments);

        return $arguments;
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

    private function resultat(string $statut, string $texte, string $texteTour, array $appels, ?ModeleIa $modele, array $essais, int $entree, int $sortie, int $tours, float $debut, array $trace = []): ResultatBoucle
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
            trace: $trace,
        );
    }
}
