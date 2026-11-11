# KLASSCI — Bulletin BTS/LMD, archivage et pondérations : recette avant mise en production

**10 novembre 2026 — PR #1801.** Ne pas confondre les deux systèmes pédagogiques.

## BTS : Général / Technique

Réglages → Bulletins → Calculs et décisions → **Composition de la moyenne semestrielle BTS**.

- `ponderee` (défaut historique) : la moyenne de chaque matière pèse
  selon **son coefficient** ; général et technique pèsent au prorata de la
  somme des coefficients de leurs matières.
- `blocs` : moyenne interne de chaque bloc calculée selon ses matières ;
  moyenne du semestre `(MG × poidsG + MT × poidsT)/(poidsG+poidsT)`.
  Les poids de blocs sont par défaut `1` et `1` (50/50), **uniquement**
  lorsque le mode `blocs` est sélectionné. Valeurs autorisées 0,1–10.

Exemple contrôlé (BTS1 Bâtiment G, S2 2025–2026) : Général 15,43
(total coeff 5), Technique 14,21 (total coeff 27) :
- `ponderee` → **14,40** brute ;
- `blocs` 1:1 → **14,82** brute ;
- `blocs` 1:2 → **14,62** brute.

Ces moyennes sont **avant** la note d'assiduité et les règles de rang.
Un changement de réglages affecte les nouveaux calculs et les aperçus
courants ; il ne rétro-modifie pas les archives PDF déjà publiées.

## LMD : CC / Examen, ECUE / UE / crédits

Le LMD n'utilise **pas** les blocs « Enseignement général / technique »
des bulletins BTS. Sa chaîne de calcul est :
1. **ECUE** : moyenne des évaluations selon leurs coefficients, ou, si
   l'école active `lmd_ponderation_cc_examen`, composition de la moyenne
   des contrôles continus et de celle des examens selon les poids
   `lmd_cc_weight` et `lmd_exam_weight` (défaut 40/60). Si une seule
   catégorie comporte des notes, seule cette catégorie compte.
2. **UE** : moyenne des ECUE selon leurs coefficients académiques ;
   compensation intra-UE et statut AQ/APC/NAQ selon les réglages.
3. **Semestre LMD** : moyenne des UE, **pondérée par les crédits UE** ;
   rang dans la cohorte semestre ; les crédits capitalisés dépendent des
   règles de validation et de compensation inter-UE.
4. **Publication** : la génération LMD actualise d'abord les résultats,
   puis la publication fige un PDF archivé et synchronise les crédits.

Quand l'école active CC/Examen, la somme des deux poids doit désormais
faire **100 %** (contrôle Web + CLI/Nanan). Une valeur négative, >100
ou une composition 0/0 est refusée. Le calcul existant est conservé :
la correction ajoute des gardes, sans forcer le mode 40/60 sur les écoles.

## Publication et lecture officielle

Sur les **nouvelles publications après déploiement** :
- Le code publie le bulletin et enregistre son PDF original dans
  `App\\Domain\\OfficialDocuments` (stockage privé `local`).
- Le document reçoit une référence/version, un snapshot des valeurs et
  réglages, une empreinte SHA-256 des octets, et une entrée d'audit.
- Chaque téléchargement unitaire, lecture du chatbot parents et export
  groupé réutilise **exactement les octets archivés** (après vérification
  d'intégrité), sans recalcul sur les nouvelles notes.
- L'édition, la suppression et la dépublication directes des bulletins
  publiés sont interdites. Les signatures BTS restent autorisées.
- Sur un **ancien bulletin publié non archivé**, KLASSCI n'invente
  pas d'original rétroactif. Le rendu encore disponible affiche
  un bandeau explicite **document historique non archivé**.
- Un aperçu recomposé depuis les résultats courants affiche
  **APERÇU DES RÉSULTATS COURANTS**, jamais « original officiel ».

**Important :** aucune interface de rectification maker-checker/versionnement automatique n'est livrée par cette PR ; les corrections de bulletins publiés
sont bloquées et nécessitent une procédure distincte validée. Ne jamais
modifier la base directement pour contourner le blocage.

## Préflight technique de déploiement

1. S'assurer que la migration historique `esbtp_official_documents`
   existe dans **chaque tenant**, et que le disque Laravel `local`
   est privé, persistant et sauvegardé. Vérifier les droits d'écriture de
   `storage/app/official-documents` et la politique de sauvegarde.
2. Sauvegarder **DB + stockage privé** de l'instance avant déploiement.
   Sauvegarder l'archive lors de toute restauration ; DB seule ne suffit
   plus à préserver les originaux.
3. Exécuter PHPUnit/CI, revoir les permissions et le flux d'export,
   vérifier l'absence de changement de valeurs de `settings` lors du
   déploiement. Les poids **ne sont jamais changés automatiquement**.
4. Sur `presentation` : réaliser des captures écran des réglages BTS,
   pondération CC/Examen LMD et impression de trois cas :
   brouillon, historique non archivé et publication archivée.
5. Vérifier les moyens d'imprimer côté parent, le téléchargement unitaire,
   l'export groupé (archive et historique), le refus d'altération d'un PDF
   stocké et l'absence d'écritures académiques lors d'un GET.
6. Publier un **bulletin de test**, vérifier son SHA et sa relecture après
   une modification de note/configuration en recette. Supprimer les données
   de recette selon la politique du tenant ; ne pas expérimenter sur un
   bulletin réel de production.
7. Ne propager vers les écoles qu'après capture sur `presentation`, revue
   thermo-nucléaire et suppression de la dette d'annonces
   (`docs/nouveautes-en-attente.md`).

## Recette pédagogique après déploiement

Pour BTS1 Bâtiment G, ouvrir **l'aperçu courant** en mode
`ponderee`, puis `blocs` 1:1 et 1:2, sans publier et sans toucher
aux bulletins historiques. Vérifier la moyenne brute 14,40 / 14,82 /
14,62 seulement si les notes et coefficients n'ont pas changé depuis
l'échantillon. Relever également l'assiduité et le rang, sans les
confondre avec la composition brute.

Pour LMD, sur une ECUE d'essai avec CC=20 et examen=11 (coefficient
identique), comparer : mode désactivé → 15,50 ; mode activé 40/60 →
14,60 ; mode activé 50/50 → 15,50. Vérifier ensuite une UE avec au
moins deux ECUE et la moyenne du semestre pondérée par crédits.
