# Carte visuelle KLASSCI LMD 360

Date : 21 juillet 2026  
Contexte : session `superadmin` authentifiée sur l'instance `presentation`.

## Couverture obtenue

| Écran | Route | HTTP | Viewports | Décision |
|---|---|---:|---|---|
| Classe BTS | `/esbtp/classes/1` | 200 | 1366 | Conserver la page et ses onglets |
| Classe LMD | `/esbtp/classes/2` | 200 | 1366, 390 | Intégrer les actions urgentes sans seconde bande de KPI |
| Étudiant BTS | `/esbtp/etudiants/7` | 200 | 1366 | Conserver |
| Étudiant LMD | `/esbtp/etudiants/6` | 200 | 1366, 390 | Ajouter le statut académique canonique dans le contexte |
| Résultats BTS | `/esbtp/resultats/classe/1` | 200 | 1366 | Protéger la matrice BTS |
| Résultats LMD classe | `/esbtp/lmd/resultats/classe/2` | 200 | 1366 | Distinguer données absentes, non calculées et échec |
| Résultats LMD étudiant | `/esbtp/lmd/resultats/etudiant/6` | 200 | 1366, 1440, zoom 200 % | Réduire la dépendance au tableau à fort zoom |
| Bulletins LMD | `/esbtp/lmd/bulletins` | 200 | 1366 | Brancher sur documents émis immuables |
| Planning LMD | `/esbtp/lmd/planning` | 200 | 1366, 1024 | Corriger les erreurs serveur avant extension |
| Liste jurys | `/esbtp/lmd/jurys` | 200 | 1366 | Conserver la liste compacte |
| JuryRoom | `/esbtp/lmd/jurys/1` | 200 | 1366, 390 | Ajouter prérequis, preuve de signature et review screen |
| Rattrapage | `/esbtp/lmd/rattrapage/1` | 200 | 1366 | Réconcilier avec décision canonique |
| Examen | `/esbtp/examens/1` | 200 | 1366 | Conserver l'architecture actuelle |
| Dashboard widgets | `/dashboard/widgets` | 500 | 1366 | Bloquant, corriger la requête legacy |
| Chatbot fermé | `/dashboard` | 200 | 1366 | Conserver le FAB, résoudre le conflit avec les actions |
| Chatbot ouvert | `/dashboard` | 200 | 1366 | Conserver le panneau existant |
| Chatbot, état erreur outil | `POST /chatbot/message` | Réponse applicative d'erreur | 1366 | Configurer le fournisseur puis tester une fixture autorisée |

## Captures

Les captures brutes sont conservées dans `docs/audits/assets/lmd360/phase0/`.

Captures pivots :

- `phase0-classe-bts-1-1366.png`
- `phase0-classe-lmd-2-1366.png`
- `phase0-classe-lmd-390.png`
- `phase0-etudiant-bts-7-1366.png`
- `phase0-etudiant-lmd-6-1366.png`
- `phase0-resultats-bts-classe-1-1366.png`
- `phase0-resultats-lmd-classe-2-1366.png`
- `phase0-resultat-lmd-etudiant-6-1366.png`
- `phase0-resultat-lmd-zoom-200.png`
- `phase0-planning-lmd-1024.png`
- `phase0-jurys-lmd-1366.png`
- `phase0-jury-1-390.png`
- `phase0-rattrapage-1-1366.png`
- `phase0-examen-1-1366.png`
- `phase0-widgets-1366.png`
- `phase0-chatbot-closed-1366.png`
- `phase0-chatbot-open-1366.png`
- `phase0-chatbot-tool-result-1366.png`

## Observations ergonomiques

1. La classe LMD mobile reste lisible, mais la page est très longue et cumule hero, volumes horaires, hiérarchie, enseignants et onglets.
2. JuryRoom mobile est exploitable, mais les actions critiques sont présentées avant une preuve complète de readiness.
3. À 200 %, le menu latéral réduit fortement la largeur utile du résultat étudiant et le tableau devient la contrainte principale.
4. Le FAB chatbot entre en concurrence avec les contenus et actions en bas à droite.
5. Les tirets et `NAQ` ne distinguent pas clairement absence de note, calcul non lancé ou échec de calcul.
6. Les boutons destructifs et de publication doivent utiliser une review screen, jamais `window.confirm()`.
7. Le dashboard en erreur expose une stack trace complète, ce qui est interdit en production.

## Couverture non obtenue honnêtement

- Dashboard enseignant et dashboard étudiant : aucun compte déterministe actif n'est fourni par les seeders réellement exécutés.
- Chatbot avec résultat d'outil : non déclenché pour éviter une exposition supplémentaire de données tant que le contrôle d'autorisation est cassé.
- Chatbot avec résultat d'outil réussi : la requête bénigne exécutée comme superAdmin retourne l'état erreur `ANTHROPIC_API_KEY` absente. Aucun succès n'est revendiqué.
- Offline, loading et réseau dégradé : pas de fixture reproductible ni de harness navigateur installé dans le worktree.
- Zoom natif 125 % : la preuve actuelle couvre 200 % par zoom CSS, pas un réglage natif du navigateur.

Ces lacunes deviennent des critères du kit de test et des fixtures du chantier, pas des validations supposées.
