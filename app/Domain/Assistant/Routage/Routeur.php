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

        if ($modeleDemande) {
            return new Decision($this->registre->candidats($modeleDemande), null, 'choix_manuel');
        }

        $paliers = $this->paliers();
        if ($paliers === []) {
            return new Decision($this->registre->candidats(), null, 'sans_paliers');
        }

        if ($etat === BudgetAssistant::ECONOMIQUE) {
            $premier = array_key_first($paliers);
            $candidats = $this->modelesDu($paliers, [$premier]);

            return new Decision($candidats ?: $this->registre->candidats(), $premier, 'budget_atteint');
        }

        [$palier, $raison] = $this->palierDeDepart($question, $conversation, $relance, array_keys($paliers));
        $suivants = array_slice(array_keys($paliers), array_search($palier, array_keys($paliers), true));
        $candidats = $this->modelesDu($paliers, $suivants);

        return new Decision($candidats ?: $this->registre->candidats(), $palier, $raison);
    }

    /**
     * Après l'échange : le palier que la conversation doit garder, ou null si
     * rien ne change. Monte d'un cran quand le résultat trahit un modèle trop faible.
     */
    public function palierApres(Decision $decision, ResultatBoucle $resultat): ?string
    {
        if ($decision->palier === null || $decision->raison === 'budget_atteint') {
            return null;
        }

        $echec = $resultat->estErreur()
            || $resultat->statut === 'limite'
            || ($resultat->echecsOutils > 0 && trim($resultat->texteDernierTour) === '');

        return $echec ? $this->palierAuDessus($decision->palier) : null;
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

    /** @return array<string, string[]> paliers déclarés, dans l'ordre, du moins cher au plus fort */
    public function paliers(): array
    {
        return array_filter(array_map(
            fn ($cles) => array_values(array_filter((array) $cles)),
            (array) config('assistant.paliers', [])
        ));
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
