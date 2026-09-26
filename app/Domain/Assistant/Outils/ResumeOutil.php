<?php

namespace App\Domain\Assistant\Outils;

/**
 * Ce que le modèle reçoit d'un résultat d'outil, et la ligne qui résume l'étape
 * à l'écran.
 *
 * Le résultat complet part vers l'écran (widget). Le modèle, lui, n'en reçoit
 * qu'une version courte : quelques lignes avec leurs identifiants et liens, le
 * total, et l'indication que l'utilisateur voit déjà le détail. Deux raisons :
 *  - un modèle qui reçoit cinquante lignes les recopie dans sa réponse ;
 *  - l'historique rejoue ces résultats à chaque tour : ils doivent rester petits.
 */
class ResumeOutil
{
    /** Lignes transmises au modèle ; le widget, lui, montre tout. */
    private const LIGNES_MODELE = 12;

    /** Plafond du JSON transmis, pour qu'un outil bavard ne sature pas le contexte. */
    private const OCTETS_MAX = 6000;

    /** Champs de présentation sans intérêt pour le raisonnement. */
    private const CHAMPS_ECARTES = ['initials', 'lien_label', 'lien_icon', 'display_type', 'badge', 'icon', 'couleur', 'color'];

    /** [singulier, pluriel] de ce que compte chaque outil. */
    private const UNITES = [
        'search_students' => ['étudiant trouvé', 'étudiants trouvés'],
        'search_payments' => ['paiement trouvé', 'paiements trouvés'],
        'search_inscriptions' => ['inscription trouvée', 'inscriptions trouvées'],
        'search_fees' => ['frais configuré', 'frais configurés'],
        'search_classes' => ['classe trouvée', 'classes trouvées'],
        'search_evaluations' => ['évaluation trouvée', 'évaluations trouvées'],
        'search_notes' => ['note trouvée', 'notes trouvées'],
        'search_attendances' => ['présence relevée', 'présences relevées'],
        'search_teachers' => ['enseignant trouvé', 'enseignants trouvés'],
        'search_results' => ['résultat trouvé', 'résultats trouvés'],
        'search_timetable' => ['jour de cours', 'jours de cours'],
        'search_subjects' => ['matière trouvée', 'matières trouvées'],
        'search_debtors' => ['étudiant en retard', 'étudiants en retard'],
        'search_bulletins' => ['bulletin trouvé', 'bulletins trouvés'],
        'search_absences_summary' => ['étudiant concerné', 'étudiants concernés'],
        'evolution_encaissements' => ['mois analysé', 'mois analysés'],
        'repartition_effectifs' => ['groupe', 'groupes'],
    ];

    /** Résumés fixes des outils qui ne comptent rien. */
    private const RESUMES_FIXES = [
        'get_dashboard_kpis' => 'Indicateurs lus',
        'get_financial_summary' => 'Synthèse financière calculée',
        'navigate_to_page' => 'Page trouvée',
        'get_setup_guide' => 'Guide préparé',
        'afficher_graphique' => 'Graphique affiché',
        'afficher_tableau' => 'Tableau affiché',
        'afficher_diagramme' => 'Diagramme affiché',
    ];

    /**
     * Ligne courte, au passé, montrée sous l'étape terminée.
     */
    public static function resumeCourt(string $nom, array $resultat): string
    {
        if (isset($resultat['error'])) {
            return (string) $resultat['error'];
        }
        if (isset(self::RESUMES_FIXES[$nom])) {
            return self::RESUMES_FIXES[$nom];
        }

        $nombre = self::nombre($resultat);
        if ($nombre === 0) {
            return 'Aucun résultat';
        }

        [$un, $plusieurs] = self::UNITES[$nom] ?? ['résultat', 'résultats'];
        $total = (int) ($resultat['total'] ?? 0);
        $texte = $nombre . ' ' . ($nombre > 1 ? $plusieurs : $un);
        if ($total > $nombre) {
            $texte .= ' sur ' . number_format($total, 0, ',', ' ');
        }

        return $texte;
    }

    /**
     * Version du résultat envoyée au modèle, en JSON.
     *
     * @param bool $widgetAffiche l'utilisateur voit déjà ces données sous forme de widget
     */
    public static function pourModele(string $nom, array $resultat, bool $widgetAffiche): string
    {
        if (isset($resultat['error'])) {
            return self::json(['error' => $resultat['error']]);
        }

        // Proposition d'action : ni le jeton ni le tableau, seulement ce qu'il faut dire.
        if (isset($resultat['proposition'])) {
            return self::json(array_intersect_key($resultat, array_flip(['statut', 'message', 'resume', 'avertissements'])));
        }

        // Outils de présentation : il n'y a rien à relire, seulement à ne pas recopier.
        if (!empty($resultat['affiche'])) {
            return self::json([
                'affiche' => true,
                'note' => "L'utilisateur voit maintenant ce contenu. Ne le recopie pas : commente-le en une ou deux phrases si c'est utile.",
            ]);
        }

        $compact = [];
        $lignes = $resultat['results'] ?? null;
        $nombre = self::nombre($resultat);

        if (is_array($lignes) && array_is_list($lignes)) {
            $compact['nombre'] = $nombre;
            if (isset($resultat['total']) && (int) $resultat['total'] > $nombre) {
                $compact['total_disponible'] = (int) $resultat['total'];
            }
            $compact['elements'] = array_map([self::class, 'ligne'], array_slice($lignes, 0, self::LIGNES_MODELE));
            if (count($lignes) > self::LIGNES_MODELE) {
                $compact['elements_non_transmis'] = count($lignes) - self::LIGNES_MODELE;
            }
        }

        foreach (['kpis', 'resume', 'totaux', 'periode', 'annee', 'classe', 'page_guide', 'url', 'deep_link', 'message', 'suggestion'] as $cle) {
            if (isset($resultat[$cle]) && $resultat[$cle] !== '' && $resultat[$cle] !== []) {
                $compact[$cle === 'deep_link' ? 'lien_liste' : $cle] = $resultat[$cle];
            }
        }

        if ($widgetAffiche) {
            $compact['affichage'] = "L'utilisateur voit déjà ces données dans un widget sous ton étape. Ne les recopie pas en liste : réponds à sa question, relève ce qui compte (tendance, extrême, anomalie) et cite au plus deux ou trois éléments avec leur lien.";
        }

        return self::json($compact);
    }

    private static function nombre(array $resultat): int
    {
        if (isset($resultat['count'])) {
            return (int) $resultat['count'];
        }

        return is_array($resultat['results'] ?? null) ? count($resultat['results']) : 0;
    }

    /** Une ligne allégée : valeurs simples, liens renommés, textes coupés. */
    private static function ligne($ligne)
    {
        if (!is_array($ligne)) {
            return $ligne;
        }

        $sortie = [];
        foreach ($ligne as $cle => $valeur) {
            if (in_array($cle, self::CHAMPS_ECARTES, true)) {
                continue;
            }
            if ($cle === 'lien') {
                $sortie['url'] = $valeur;
                continue;
            }
            if (is_string($valeur)) {
                $sortie[$cle] = mb_strimwidth($valeur, 0, 160, '…', 'UTF-8');
            } elseif (is_scalar($valeur) || $valeur === null) {
                $sortie[$cle] = $valeur;
            } elseif (is_array($valeur) && count($valeur) <= 6) {
                $sortie[$cle] = array_map(fn ($v) => is_array($v) ? self::ligne($v) : $v, $valeur);
            }
        }

        return $sortie;
    }

    private static function json(array $donnees): string
    {
        $json = json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (strlen($json) <= self::OCTETS_MAX) {
            return $json;
        }

        // Trop long : on retire des éléments jusqu'à tenir, en le disant au modèle.
        while (strlen($json) > self::OCTETS_MAX && !empty($donnees['elements'])) {
            array_pop($donnees['elements']);
            $donnees['elements_non_transmis'] = ($donnees['elements_non_transmis'] ?? 0) + 1;
            $json = json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        return strlen($json) <= self::OCTETS_MAX ? $json : mb_strcut($json, 0, self::OCTETS_MAX, 'UTF-8');
    }
}
