# BTS — Frontière entre bulletin officiel et résultats courants

Date : 10 novembre 2026. Portée : `ESBTPBulletin` (BTS), toutes les instances KLASSCI.
Statut : **audit et première sécurisation ; immutabilité documentaire NON garantie**.

## 1. Signalement et calcul contrôlé

Deux bulletins S2 2025-2026 ESBTP Yamoussoukro affichaient un premier
dont la moyenne était inférieure à la « plus forte moyenne » de sa classe.
La PR #1798 a supprimé le calcul parallèle des statistiques et réutilisé
`RankingService::calculerRangsClasse` pour les mêmes membres et les mêmes
moyennes. La recette CLI et les nouveaux PDF ont confirmé :
- classe Bâtiment G : 48 résultats, premier = maximum **14,01** ; moyenne
  brute **14,40**, assiduité **−0,39**, classe **11,07** ;
- classe Bâtiment E : 47 résultats, premier = maximum **14,65** ; moyenne
  brute **14,52**, assiduité **+0,13**, classe **12,44**.

La différence de population G provenait d'un élève rattaché à la cohorte
semestrielle mais absent de `/students?class_id=78` (note 4,46, rang 46).
Ce n'est pas une preuve de disparition administrative : la classe de phase S2
diffère de la classe d'inscription.

## 2. Composition de la moyenne BTS — trois réglages existants

La migration `2026_09_11_222511_seed_reglages_moyenne_par_blocs.php` sème,
sans écraser les choix de l'établissement :
- `bulletin_moyenne_mode=ponderee` par défaut : chaque **matière** est
  pondérée par son coefficient dans le total ;
- `bulletin_moyenne_mode=blocs` : on compose deux moyennes calculées au sein
  de chaque bloc, puis `bulletin_bloc_general_coef` et
  `bulletin_bloc_professionnel_coef` (défaut **1 et 1**).

Pour le bulletin G, avec général **15,43 (coefficients cumulés 5)** et
technique **14,21 (coefficients cumulés 27)** :
- pondération par matière : `(15,43 × 5 + 14,21 × 27) / 32 ≈ 14,40` ;
- composition par blocs 1:1 : `(15,43 + 14,21) / 2 = 14,82`.

Le bulletin imprimé comporte **14,40** : cohérent avec la pondération par
matière, **pas** avec le mode 1:1. Ce constat ne remplace pas une lecture
du réglage effectif du tenant en base. Les paramètres existaient, mais n'étaient
pas exposés dans l'onglet Bulletins > Calculs et décisions ; cette branche
ajoute l'interface, sans changer aucun réglage préexistant.

Un coefficient nul ou vide n'est pas une pondération exploitable ; la nouvelle
validation refuse ces valeurs. Les contrôles sont centralisés dans
`ModificationDeReglages` pour le formulaire, la CLI et Nanan.

## 3. Frontière « Courant / Officiel » : état de code établi

| Données | Source | Ce que cela garantit |
| --- | --- | --- |
| Courant | `BtsCurrentResultSnapshotService` (notes, moyennes manuelles, configuration et assiduité actuelles) | Recalcul disponible pour un instant donné. **Pas** un document historisé. |
| Officiel DB | `esbtp_bulletins.moyenne_generale`, `note_assiduite`, rang/mentions et `ESBTPResultatMatiere` | Une partie de l'état enregistré. **Pas** l'ensemble du PDF figé. |
| Différence | `BulletinConsistencyService::getSnapshot` | Compare les totaux brut, assiduité et moyenne effective ; **pas** l'empreinte de chaque détail de matière ni la version des réglages. |
| PDF | `ESBTPBulletinController::buildBulletinPdf` | La projection canonique de `BulletinService` relit encore les données actuelles. Un PDF téléchargé plus tard peut différer même sans modification du bulletin DB. |

**Défaut réel de lecture** : `genererPDF()` appelait le renderer avec
`persist=true` par défaut. Le renderer pouvait enregistrer une moyenne
recalculée et recalculer les rangs depuis une requête de consultation PDF.
Le GET `genererPDFParParamsUnified` persistait lui aussi quand
`inline=false`. Cette branche rend les **deux GET non persistants** ;
la régénération explicite reste séparée et soumise à
`BulletinConsistencyService::regenerateOfficialBulletin`.

**Défaut toujours ouvert** : sans archive PDF immuable produite à la
publication, les valeurs imprimées peuvent suivre des notes modifiées après
publication. Un simple contrôle d'égalité des moyennes ne suffit pas :
deux notes peuvent changer en sens opposé sans modifier la moyenne.

## 4. Écriture, publication et garde-fous

- `BulletinConsistencyService::regenerateOfficialBulletin` utilise
  transaction et verrou `lockForUpdate` et refuse les bulletins publiés
  ou signés : garde-fou **présent**.
- `ESBTPBulletinController::update`, `edit` et `destroy`
  refusent désormais les bulletins publiés ou signés, selon la même règle
  que `BulletinConsistencyService` ; `bulkDelete` verrouille les lignes,
  puis refuse le lot entier dès qu'un membre est protégé.
- Ce sont des garde-fous des parcours Web examinés, **pas** une
  interdiction globale des écritures de tous les services ou jobs :
  les chemins restants doivent être cartographiés avant toute affirmation
  d'immutabilité.
- `bulkPublish` bascule le statut par requête `update` de masse : aucun
  PDF/version n'est capturé lors de la transition.
- `togglePublication` publie/dépublie le même enregistrement : aucune
  version immuable n'est conservée et une dépublication peut encore
  rendre la modification possible. À remplacer par une rectification
  officielle versionnée.
- `buildBulletinPdf` contient encore de la logique calculatoire doublonnée,
  ensuite remplacée par la projection canonique ; elle doit être simplifiée
  sans perdre les parcours de génération groupée.

## 5. Prochain lot requis pour une immutabilité complète (NON inclus ici)

1. **Versionner l'émission** : à chaque publication, conserver le PDF binaire,
   son SHA-256, la configuration et les versions des données/assiduité
   pertinentes ; stockage privé et contrôle d'accès par établissement.
2. Servir les téléchargements « **Officiel** » exclusivement depuis l'archive
   publiée, sans aucun recalcul. Si aucune archive n'existe pour un ancien
   bulletin, afficher explicitement « bulletin historique non archivé »
   et une procédure de régularisation, jamais un PDF live étiqueté officiel.
3. Différencier l'« **Aperçu courant** » (bandeau BROUILLON/Courant, aucune
   écriture) de l'« **Officiel publié** » (archive versionnée).
4. Toute rectification doit exiger motif, permission fine, contrôle
   maker-checker si requis, version n+1 et conservation consultable de n.
5. Assurer les interdictions **côté serveur** sur chaque chemin d'écriture,
   y compris modification, régénération, génération groupée et suppression.
6. Tests de non-régression : changement d'une matière avec moyenne identique,
   changement d'assiduité, configuration pondération 1:1 → 1:2, S1 tronc commun,
   alias de semestre, re-publication, droits individuels, tenant distinct,
   PDF historique et audit d'accès.

## 6. Acceptation / déploiement

Première phase (cette PR) : champs de composition visibles, validations,
tests de calcul, **GET PDF sans écriture**, refus d'édition/suppression
sur les bulletins publiés ou signés dans les parcours Web contrôlés,
diagnostic documenté.
Aucune migration de résultats, aucun recalcul automatique, aucun
déploiement tenant. Revue thermo-nucléaire et tests CI requis avant fusion.

Deuxième phase : architecture d'archives et politiques d'édition ci-dessus,
PR distincte avec migration additive et backfill **contrôlé** des anciens
bulletins ; ne jamais prétendre reconstituer le binaire authentique d'un
bulletin publié si celui-ci n'a jamais été conservé.
