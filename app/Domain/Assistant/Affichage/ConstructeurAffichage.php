<?php

namespace App\Domain\Assistant\Affichage;

use App\Models\ChatbotConversation;
use App\Services\Chatbot\ConversationContextProvider;

/**
 * Transforme les résultats d'outils d'UN échange en données d'affichage
 * (display_type / display_data / deep_link), les mêmes formes que l'historique
 * et que les parties `data-*` du flux. Indépendant du fournisseur.
 *
 * Une instance par échange : elle accumule l'état au fil des appels d'outil.
 */
class ConstructeurAffichage
{
    private ?array $dernierResultat = null;
    private string $displayType = 'text';
    private ?array $displayData = null;
    private ?string $deepLink = null;
    private ?string $dernierOutil = null;

    public function __construct(private ConversationContextProvider $contextProvider)
    {
    }

    public function enregistrer(ChatbotConversation $conversation, string $nom, array $arguments, array $resultat): void
    {
        $this->dernierOutil = $nom;
        $this->contextProvider->updateFromToolResult($conversation, $nom, $arguments, $resultat);

        $precedent = $this->dernierResultat;
        $type = $resultat['display_type'] ?? 'text';

        // Fusionner les groupes si le même affichage groupé revient plusieurs fois.
        if (in_array($type, ['fee_groups', 'payment_groups'], true) && $precedent && ($precedent['display_type'] ?? '') === $type) {
            $precedent['results'] = array_merge($precedent['results'] ?? [], $resultat['results'] ?? []);
            $precedent['count'] = ($precedent['count'] ?? 0) + ($resultat['count'] ?? 0);
            $this->dernierResultat = $precedent;
        } else {
            // Sinon : garder le résultat qui porte des données.
            $nombre = $resultat['count'] ?? count($resultat['results'] ?? []);
            $nombrePrecedent = $precedent ? ($precedent['count'] ?? 0) : 0;
            if (!$precedent || $nombre > 0 || $nombrePrecedent === 0) {
                $this->dernierResultat = $resultat;
            }
        }

        if (isset($resultat['display_type'])) {
            $this->displayType = $resultat['display_type'];
        }
        if (isset($resultat['deep_link'])) {
            $this->deepLink = $resultat['deep_link'];
        }
        if (isset($resultat['guide'])) {
            $this->displayData = $resultat['guide'];
            $this->displayType = 'checklist';
        }
    }

    /**
     * @return array{text: string, display_type: string, display_data: ?array, deep_link: ?string}
     */
    public function finaliser(string $texte): array
    {
        $dernier = $this->dernierResultat;
        $displayType = $this->displayType;
        $displayData = $this->displayData;

        // navigate_to_page renvoie un page_guide que le modèle reprend mal : on l'injecte.
        if ($dernier && !empty($dernier['page_guide'])) {
            $guide = $dernier['page_guide'];
            $generique = $texte === '' || mb_strlen($texte) < 50 || str_contains($texte, 'Je suis là pour vous aider');
            if ($generique) {
                $texte = $guide;
            } elseif (!str_contains($texte, '1.') && !str_contains($texte, '- ')) {
                $texte .= "\n\n" . $guide;
            }
        }

        if ($dernier && in_array($displayType, ['fee_groups', 'payment_groups'], true)) {
            $displayData = [
                'groups' => $dernier['results'] ?? [],
                'deep_link' => $dernier['deep_link'] ?? null,
                'total_count' => $dernier['count'] ?? 0,
            ];
        } elseif ($dernier && $displayType === 'stat_cards') {
            $displayData = [
                'stats' => $dernier['results'] ?? [],
                'deep_link' => $dernier['deep_link'] ?? null,
                'total_count' => $dernier['count'] ?? 0,
            ];
        } elseif ($dernier && $displayType === 'timetable') {
            $displayData = [
                'days' => $dernier['results'] ?? [],
                'classe' => $dernier['classe'] ?? 'N/A',
                'filiere' => $dernier['filiere'] ?? '',
                'semestre' => $dernier['semestre'] ?? '',
                'annee' => $dernier['annee'] ?? '',
                'periode' => $dernier['periode'] ?? '',
                'total_count' => $dernier['count'] ?? 0,
                'deep_link' => $dernier['deep_link'] ?? null,
            ];
        } elseif ($dernier && $displayType !== 'checklist') {
            $displayData = $this->buildDisplayData($dernier, $displayType);
        }

        if ($displayData && $this->dernierOutil) {
            $displayData['follow_up'] = $this->generateFollowUpSuggestions($this->dernierOutil);
        }

        return [
            'text' => $texte,
            'display_type' => $displayType,
            'display_data' => $displayData,
            'deep_link' => $this->deepLink,
        ];
    }


    // ── Constructeurs des données d'affichage (tableaux, cartes) ──

    protected function buildDisplayData(array $toolResult, string $displayType): ?array
    {
        $results = $toolResult['results'] ?? [];
        if (empty($results)) {
            return null;
        }

        if ($displayType === 'table') {
            return $this->buildTableDisplayData($results, $toolResult);
        }

        if ($displayType === 'cards') {
            return $this->buildCardsDisplayData($results, $toolResult);
        }

        return null;
    }

    protected function buildTableDisplayData(array $results, array $toolResult): array
    {
        if (empty($results)) {
            return ['columns' => [], 'rows' => [], 'column_count' => 0];
        }

        $first = $results[0];
        $skipKeys = ['id', 'lien', 'lien_label', 'lien_icon', 'lien_inscription', 'montant_brut', 'deep_link', 'reste_brut', 'absences_brut'];
        $columns = [];
        foreach (array_keys($first) as $key) {
            if (in_array($key, $skipKeys, true)) {
                continue;
            }
            $columns[] = ['label' => $this->humanizeColumnName($key)];
        }

        $rows = [];
        foreach ($results as $result) {
            $cells = [];
            foreach (array_keys($first) as $key) {
                if (in_array($key, $skipKeys, true)) {
                    continue;
                }
                $value = $result[$key] ?? 'N/A';
                $cell = ['value' => (string) $value];

                if (in_array($key, ['statut', 'active', 'actif'], true)) {
                    $cell['badge'] = $this->getBadgeClass((string) $value);
                }

                $cells[] = $cell;
            }

            $row = ['cells' => $cells, 'column_count' => count($columns)];

            $actions = [];
            if (!empty($result['lien'])) {
                $actions[] = ['label' => 'Voir', 'url' => $result['lien'], 'icon' => 'fas fa-eye'];
            }
            if (!empty($result['lien_inscription'])) {
                $actions[] = ['label' => 'Inscription', 'url' => $result['lien_inscription'], 'icon' => 'fas fa-file-invoice'];
            }
            if (!empty($actions)) {
                $row['actions'] = $actions;
            }

            $rows[] = $row;
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'column_count' => count($columns),
            'total_count' => count($results),
            'total_available' => $toolResult['total'] ?? count($results),
            'deep_link' => $toolResult['deep_link'] ?? null,
        ];
    }

    protected function buildCardsDisplayData(array $results, array $toolResult): array
    {
        $cards = [];
        foreach ($results as $result) {
            $card = [
                'title' => $result['etudiant'] ?? $result['nom'] ?? 'N/A',
                'subtitle' => $result['classe'] ?? $result['filiere'] ?? '',
                'initials' => $result['initials'] ?? null,
                'meta' => [],
                'badges' => [],
            ];

            // Champs standards
            $metaFields = [
                'matricule' => 'Matricule',
                'periode' => 'Période',
                'annee' => 'Année',
                'moyenne' => 'Moyenne',
                'rang' => 'Rang',
                'decision' => 'Décision',
                'absences' => 'Absences',
                'taux_presence' => 'Présence',
                'seances' => 'Séances',
                'retards' => 'Retards',
                'reste' => 'Reste dû',
                'detail' => 'Paiement',
                'taux' => 'Progression',
                'date' => 'Date',
                'montant' => 'Montant',
            ];

            foreach ($metaFields as $key => $label) {
                if (isset($result[$key]) && $result[$key] !== 'N/A' && $result[$key] !== null && $result[$key] !== '') {
                    $card['meta'][] = ['label' => $label, 'value' => (string) $result[$key]];
                }
            }

            if (isset($result['filiere']) && !isset($result['etudiant']) && !isset($result['nom'])) {
                $card['meta'][] = ['label' => 'Filière', 'value' => $result['filiere']];
            }
            if (isset($result['type'])) {
                $card['meta'][] = ['label' => 'Type', 'value' => $result['type']];
            }
            if (isset($result['statut'])) {
                $card['badges'][] = ['label' => $result['statut'], 'style' => $this->getBadgeClass($result['statut'])];
            }
            if (isset($result['publie'])) {
                $card['badges'][] = ['label' => $result['publie'] === 'Oui' ? 'Publié' : 'Non publié', 'style' => $result['publie'] === 'Oui' ? 'success' : 'info'];
            }

            if (!empty($result['lien'])) {
                $actionLabel = $result['lien_label'] ?? 'Voir';
                $actionIcon = $result['lien_icon'] ?? 'fas fa-eye';
                $card['actions'] = [['label' => $actionLabel, 'url' => $result['lien'], 'icon' => $actionIcon]];
            } elseif (!empty($result['lien_inscription'])) {
                $card['actions'] = [['label' => 'Voir', 'url' => $result['lien_inscription'], 'icon' => 'fas fa-eye']];
            }

            $cards[] = $card;
        }

        return [
            'cards' => $cards,
            'total_count' => count($cards),
            'total_available' => $toolResult['total'] ?? count($cards),
            'deep_link' => $toolResult['deep_link'] ?? null,
        ];
    }

    protected function humanizeColumnName(string $key): string
    {
        $map = [
            'nom' => 'Nom',
            'matricule' => 'Matricule',
            'classe' => 'Classe',
            'filiere' => 'Filière',
            'niveau' => 'Niveau',
            'effectif' => 'Effectif',
            'active' => 'Active',
            'actif' => 'Actif',
            'statut' => 'Statut',
            'etudiant' => 'Étudiant',
            'montant' => 'Montant',
            'date' => 'Date',
            'mode' => 'Mode',
            'type' => 'Type',
            'code' => 'Code',
            'categorie' => 'Catégorie',
            'type_tarif' => 'Type tarif',
            'affectes' => 'Affectés',
            'reaffectes' => 'Réaffectés',
            'non_affectes' => 'Non affectés',
            'formule' => 'Formule',
        ];

        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    protected function getBadgeClass(string $value): string
    {
        $lower = mb_strtolower($value, 'UTF-8');
        return match (true) {
            str_contains($lower, 'valid'), str_contains($lower, 'activ'), $lower === 'oui' => 'success',
            str_contains($lower, 'attente') => 'warning',
            str_contains($lower, 'rejet'), str_contains($lower, 'annul'), $lower === 'non' => 'danger',
            default => 'info',
        };
    }


    protected function generateFollowUpSuggestions(?string $toolName): array
    {
        return match ($toolName) {
            'search_inscriptions' => [
                'Voir les paiements associés',
                'Lister les classes actives',
                'Chercher un étudiant précis',
            ],
            'search_students' => [
                'Voir ses paiements',
                'Voir ses résultats',
                'Lister toutes les inscriptions',
            ],
            'search_payments' => [
                'Paiements en attente',
                'Voir les inscriptions',
                'Accéder à la comptabilité',
            ],
            'search_classes' => [
                'Voir les étudiants d\'une classe',
                'Consulter les inscriptions',
                'Voir l\'emploi du temps',
            ],
            'search_fees' => [
                'Voir les paiements',
                'Configurer les frais',
                'Lister les inscriptions',
            ],
            'search_results' => [
                'Voir le bulletin',
                'Voir les notes détaillées',
                'Chercher un autre étudiant',
            ],
            'search_timetable' => [
                'Voir les matières de la classe',
                'Chercher un enseignant',
                'Voir les évaluations',
            ],
            'search_subjects' => [
                'Voir l\'emploi du temps',
                'Chercher un enseignant',
                'Voir les évaluations',
            ],
            'get_financial_summary' => [
                'Étudiants en retard de paiement',
                'Voir les paiements en attente',
                'Accéder à la comptabilité',
            ],
            'search_debtors' => [
                'Résumé financier global',
                'Paiements en attente',
                'Voir les inscriptions',
            ],
            'search_bulletins' => [
                'Voir les résultats',
                'Chercher un étudiant',
                'Voir les évaluations',
            ],
            'search_absences_summary' => [
                'Voir les présences détaillées',
                'Voir les résultats de la classe',
                'Voir l\'emploi du temps',
            ],
            'get_setup_guide' => [
                'Créer une filière',
                'Ajouter un étudiant',
                'Configurer les frais',
            ],
            'navigate_to_page' => [
                'Voir les inscriptions',
                'Chercher un étudiant',
                'Consulter les paiements',
            ],
            default => [
                'Voir les inscriptions',
                'Chercher un étudiant',
                'Consulter les paiements',
            ],
        };
    }
}
