<?php

namespace App\Domain\Assistant\Routage;

use App\Domain\Assistant\Consommation\BudgetAssistant;
use App\Domain\Assistant\Harnais\ResultatBoucle;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Modeles\RegistreDesModeles;
use App\Models\ChatbotConversation;

/**
 * Choisit le modèle de chaque échange, sans rien demander à l'utilisateur.
 *
 * Principe : le modèle le moins cher qui fait bien le travail. Les modèles sont
 * rangés en paliers (config assistant.paliers, du moins cher au plus fort) :
 *
 *  1. Palier de départ selon la question : économique pour une recherche ou une
 *     question courte, standard pour une analyse, une comparaison, un schéma.
 *  2. Montée d'un palier quand l'échange précédent de la conversation a échoué,
 *     quand l'utilisateur demande de réessayer, ou quand le modèle a buté sur
 *     ses outils sans conclure. La conversation garde ensuite ce palier.
 *  3. Dans un échange, un modèle qui tombe avant d'avoir rien montré passe la
 *     main au suivant : les paliers au-dessus servent de repli.
 *  4. Budget du mois atteint : palier économique seulement, sans montée. Seuil
 *     de pause atteint : aucun appel.
 *
 * Un modèle choisi à la main (permission assistant.model.choose) court-circuite
 * le routage : c'est un outil de test, pas le fonctionnement normal.
 */
class Routeur
{
    private const REUSSITES_AVANT_DESCENTE = 3;

    public function __construct(
        private RegistreDesModeles $registre,
        private BudgetAssistant $budget,
    ) {
    }

    public function decider(string $question, ?ChatbotConversation $conversation, bool $relance = false, ?string $modeleDemande = null): Decision
    {
        $etat = $this->budget->etat();
        if ($etat === BudgetAssistant::PAUSE) {
            return new Decision([], null, 'budget_pause', true);
        }

        $paliers = $this->paliers();

        // Budget atteint : palier économique seulement, même pour qui choisit son
        // modèle — sinon un choix manuel consommerait sans frein jusqu'à la pause.
        if ($etat === BudgetAssistant::ECONOMIQUE) {
            $premier = $paliers === [] ? null : array_key_first($paliers);
            $candidats = $premier ? $this->modelesDu($paliers, [$premier]) : [];

            return new Decision($candidats ?: $this->leMoinsCher(), $premier, 'budget_atteint');
        }

        if ($modeleDemande) {
            return new Decision($this->registre->candidats($modeleDemande), null, 'choix_manuel');
        }

        if ($paliers === []) {
            return new Decision($this->registre->candidats(), null, 'sans_paliers');
        }

        [$palier, $raison] = $this->palierDeDepart($question, $conversation, $relance, array_keys($paliers));
        $suivants = array_slice(array_keys($paliers), array_search($palier, array_keys($paliers), true));
        $candidats = $this->modelesDu($paliers, $suivants);

        return new Decision($candidats ?: $this->registre->candidats(), $palier, $raison);
    }

    /**
     * Après l'échange : ce que la conversation doit retenir de son palier, ou null
     * si rien ne change.
     *
     *  - échec (erreur, ou outils en échec sans conclusion) : un palier au-dessus ;
     *  - trois réussites de suite sur un palier monté : on redescend d'un cran.
     *    Un palier monté pour une question difficile ne doit pas rester acquis :
     *    la suite de la conversation paierait le prix fort pour des questions simples.
     *
     * Une limite de tours ou de jetons ne fait pas monter : un modèle plus cher
     * atteindrait le même plafond, en coûtant plus.
     *
     * @return array{palier:?string,succes_au_palier:int}|null
     */
    public function palierApres(Decision $decision, ResultatBoucle $resultat, ?ChatbotConversation $conversation = null): ?array
    {
        if ($decision->palier === null || $decision->raison === 'budget_atteint') {
            return null;
        }

        $echec = $resultat->estErreur()
            || ($resultat->echecsOutils > 0 && trim($resultat->texteDernierTour) === '');
        if ($echec) {
            $au = $this->palierAuDessus($decision->palier);

            return $au ? ['palier' => $au, 'succes_au_palier' => 0] : null;
        }

        $garde = $conversation?->context['palier'] ?? null;
        if (! $garde || $resultat->statut !== 'ok') {
            return null;
        }

        $succes = (int) ($conversation->context['succes_au_palier'] ?? 0) + 1;
        if ($succes < self::REUSSITES_AVANT_DESCENTE) {
            return ['palier' => $garde, 'succes_au_palier' => $succes];
        }

        $ordre = array_keys($this->paliers());
        $i = array_search($garde, $ordre, true);

        // Redescendu au premier palier, la conversation n'a plus rien à retenir.
        return ['palier' => ($i !== false && $i > 1) ? $ordre[$i - 1] : null, 'succes_au_palier' => 0];
    }

    /**
     * Modèle effectif d'une question simple : ce que l'état des réglages doit
     * annoncer, puisque c'est le routeur qui choisit.
     */
    public function modelePourQuestionSimple(): ?ModeleIa
    {
        return $this->decider('', null)->candidats[0] ?? null;
    }

    /** @return array{0:string,1:string} */
    private function palierDeDepart(string $question, ?ChatbotConversation $conversation, bool $relance, array $ordre): array
    {
        $garde = $conversation?->context['palier'] ?? null;
        $base = in_array($garde, $ordre, true) ? $garde : $ordre[0];
        $raison = $garde ? 'palier_conversation' : 'question_simple';

        if ($this->estExigeante($question) && $this->rang($base, $ordre) < $this->rang('standard', $ordre)) {
            $base = in_array('standard', $ordre, true) ? 'standard' : $base;
            $raison = 'question_exigeante';
        }

        if ($relance) {
            $base = $this->palierAuDessus($base) ?? $base;
            $raison = 'relance';
        }

        return [$base, $raison];
    }

    /**
     * Une question qui demande de raisonner plutôt que de retrouver : analyse,
     * comparaison, explication, schéma, synthèse, ou question longue à plusieurs volets.
     */
    public function estExigeante(string $question): bool
    {
        $q = mb_strtolower($question, 'UTF-8');
        $motifs = '/\b(compar\w*|analy\w*|[ée]volution|tendance|pourquoi|expliqu\w*|synth[èe]se|r[ée]sum\w*|bilan|fais le point|sch[ée]ma|diagramme|graphique|pr[ée]vision|pr[ée]voi\w*|risque\w*|strat[ée]gie|recommand\w*|conseil\w*|plan d\'action|anomalie\w*|incoh[ée]ren\w*)\b/u';

        return preg_match($motifs, $q) === 1
            || mb_strlen($question, 'UTF-8') > 240
            || substr_count($question, '?') >= 2;
    }

    public function palierAuDessus(string $palier): ?string
    {
        $ordre = array_keys($this->paliers());
        $i = array_search($palier, $ordre, true);

        return ($i !== false && isset($ordre[$i + 1])) ? $ordre[$i + 1] : null;
    }

    /**
     * Paliers déclarés, dans l'ordre, du moins cher au plus fort.
     *
     * Le modèle par défaut choisi par l'école (réglage `assistant.modele_defaut`,
     * klassci-cli `assistant:modele`) passe en tête de son palier : c'est ainsi
     * qu'il garde un sens avec le routage. Hors de tout palier, il prend la tête
     * du premier.
     *
     * @return array<string, string[]>
     */
    public function paliers(): array
    {
        $paliers = array_filter(array_map(
            fn ($cles) => array_values(array_filter((array) $cles)),
            (array) config('assistant.paliers', [])
        ));

        $prefere = $this->registre->defautChoisiParLEcole();
        if ($prefere && $paliers !== []) {
            $cible = array_key_first($paliers);
            foreach ($paliers as $nom => $cles) {
                if (in_array($prefere, $cles, true)) {
                    $cible = $nom;
                    break;
                }
            }
            $paliers[$cible] = array_values(array_unique(array_merge([$prefere], $paliers[$cible])));
        }

        return $paliers;
    }

    /** Budget atteint et palier économique vide : le seul modèle disponible au tarif le plus bas. */
    private function leMoinsCher(): array
    {
        $disponibles = array_values($this->registre->disponibles());
        usort($disponibles, fn (ModeleIa $a, ModeleIa $b) => $this->prix($a) <=> $this->prix($b));

        return array_slice($disponibles, 0, 1);
    }

    private function prix(ModeleIa $modele): float
    {
        $tarif = (array) config("assistant.modeles.{$modele->cle}.tarif", []);

        // Sans tarif déclaré, le modèle est tenu pour le plus cher : on ne le choisit pas à l'aveugle.
        return $tarif === [] ? PHP_FLOAT_MAX : (float) ($tarif['entree'] ?? 0) + (float) ($tarif['sortie'] ?? 0);
    }

    /** @return ModeleIa[] modèles disponibles des paliers donnés, dans l'ordre, sans doublon */
    private function modelesDu(array $paliers, array $noms): array
    {
        $disponibles = $this->registre->disponibles();
        $candidats = [];
        foreach ($noms as $nom) {
            foreach ($paliers[$nom] ?? [] as $cle) {
                if (isset($disponibles[$cle]) && !isset($candidats[$cle])) {
                    $candidats[$cle] = $disponibles[$cle];
                }
            }
        }

        return array_values($candidats);
    }

    private function rang(string $palier, array $ordre): int
    {
        $i = array_search($palier, $ordre, true);

        return $i === false ? PHP_INT_MAX : $i;
    }
}
