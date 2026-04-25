## Vue d'ensemble du workflow

Une inscription dans KLASSCI suit un cycle en 4 étapes, modélisé par le champ `workflow_step` :

1. **`pre_inscrit`** — l'étudiant a fourni ses informations mais le paiement initial n'a pas encore été validé. Aucune classe n'est encore attribuée.
2. **`pre_inscrit_paye`** — le paiement initial est validé. L'étudiant attend l'attribution d'une classe par l'administration.
3. **`affecte`** — la classe est attribuée. L'étudiant est en attente de validation finale.
4. **`etudiant_cree`** — l'inscription est validée définitivement. L'étudiant est compté dans les effectifs et peut accéder à son espace.

Cette séparation existe pour éviter qu'un étudiant qui a juste rempli un formulaire (sans payer) ne soit considéré comme inscrit dans les statistiques. Toutes les pages KPI filtrent par défaut sur `workflow_step = etudiant_cree`.

:::callout
**Règle d'or.** Tant que `workflow_step != etudiant_cree`, l'étudiant n'est PAS officiellement inscrit, même si son `status` vaut `active`. Les rapports d'effectifs, le calcul des places restantes et les tableaux de bord respectent cette règle.
:::

## Créer une nouvelle inscription

### Cas général : étudiant qui paie immédiatement

Allez dans `Étudiants → Inscriptions → Nouvelle inscription`. Le formulaire est divisé en sections logiques :

1. **Informations étudiant** — nom, prénoms, date de naissance, sexe, nationalité, téléphone, email
2. **Adresse et contact d'urgence**
3. **Cursus antérieur** — diplôme obtenu, établissement d'origine, année
4. **Affectation académique** — filière, niveau, classe (Select2 avec recherche)
5. **Frais et paiement initial** — sélection des frais à acquitter, mode de paiement, montant

Lorsque vous sélectionnez une classe, KLASSCI vérifie en temps réel les places disponibles via un appel AJAX vers `GET /esbtp/classes/{id}/available-places`. L'affichage utilise des seuils colorés :

| Couleur | Seuil | Message |
| --- | --- | --- |
| Vert | > 30 % | « Places disponibles : X / Y » |
| Orange | 10 % à 30 % | « Places limitées : X / Y » |
| Orange foncé | < 10 % | « Presque complet : X / Y » |
| Rouge | 0 | « Classe pleine » + bouton de soumission désactivé |

Si la classe est pleine, le bouton « Valider l'inscription » est automatiquement grisé et un message d'erreur explicite apparaît au-dessus du formulaire. L'étudiant doit choisir une autre classe ou attendre une place.

### Cas particulier : pré-inscription sans paiement immédiat

Si l'étudiant n'a pas encore les fonds pour payer son inscription, vous pouvez l'enregistrer en **pré-inscription** depuis `Étudiants → Pré-inscriptions → Nouvelle pré-inscription`. Le formulaire est plus court (informations essentielles seulement, pas d'affectation de classe).

Plus tard, quand l'étudiant paie, retrouvez sa pré-inscription dans la liste, cliquez sur « Compléter l'inscription » et finalisez le dossier. KLASSCI propose alors une **modale d'affectation** pour choisir la classe avec vérification des places.

## Validation d'une inscription

### Validation individuelle

Sur la fiche d'inscription, cliquez sur le bouton « Valider l'inscription ». KLASSCI vérifie automatiquement :

- qu'au moins un paiement existe sur cette inscription
- que le paiement a un statut `valide` (pas `en_attente`)
- qu'une classe est bien attribuée
- que la classe a encore une place

Si toutes les conditions sont réunies, l'inscription passe à `etudiant_cree`. L'étudiant reçoit son matricule (auto-généré selon la convention de l'établissement) et un email avec ses identifiants étudiant.

:::callout warning
**Pas de validation sans paiement.** KLASSCI bloque la validation si aucun paiement valide n'est associé. Cette règle s'applique aussi en validation en lot. Pour les cas exceptionnels, le super-administrateur peut forcer la validation depuis la console artisan.
:::

### Validation en lot

Pour valider plusieurs inscriptions d'un coup (par exemple en début d'année scolaire), allez dans `Étudiants → Inscriptions`, cochez les inscriptions à valider, puis utilisez le bouton « Actions groupées → Valider la sélection ». KLASSCI traite chaque inscription dans une transaction séparée :

- Les inscriptions avec paiement validé passent à `etudiant_cree`.
- Les inscriptions avec paiement `en_attente` sont **ignorées** (pas validées) avec la raison « paiement_en_attente ».
- Les inscriptions sans paiement sont **ignorées** avec la raison « sans_paiement ».
- Les inscriptions sur une classe pleine sont **ignorées** avec la raison « classe_pleine ».

Un toast récapitulatif affiche le nombre d'inscriptions validées et la liste des ignorées avec leurs raisons.

## Ré-inscription d'un étudiant existant

Pour un étudiant qui passe d'une année à l'autre, utilisez `Étudiants → Réinscriptions → Nouvelle réinscription`. Le formulaire est pré-rempli avec :

- les informations personnelles de l'étudiant (modifiables)
- l'historique de ses inscriptions précédentes
- le **reliquat** éventuel de l'année précédente (somme due qui n'a pas été payée), automatiquement reportée sur la nouvelle année

Choisissez la nouvelle classe (généralement le niveau supérieur dans la même filière) et finalisez le paiement initial. La règle métier impose que le **solde calculé** se base uniquement sur les `frais subscriptions` actives, sans fallback sur la configuration globale.

Pour les filières en tronc commun, KLASSCI propose un workflow spécifique :

1. À l'entrée, l'étudiant s'inscrit dans la classe `Tronc commun [Filière]`.
2. À la fin du semestre commun, vous accédez à la fiche étudiant et utilisez « Spécialisation » pour le ré-inscrire dans une classe spécialisée. Une nouvelle inscription est créée, liée à la précédente par `inscription_origine_id`. La règle de contrainte unique a été assouplie pour permettre 2 inscriptions par année universitaire dans ce cas (`(etudiant_id, annee_universitaire_id, classe_id)`).
3. Le bulletin annuel agrège automatiquement les notes du tronc commun (S1) et de la spécialisation (S2) avec la pondération configurée.

## Annuler une inscription

Sur la liste des inscriptions, sélectionnez les lignes à annuler et utilisez « Actions groupées → Annuler la sélection ». KLASSCI vous demande une **raison** (4 choix : abandon, exclusion, transfert, autre) et un commentaire libre.

L'inscription passe en `status = annule` et `workflow_step = annule`. Les données sont conservées (pas de suppression physique) pour l'historique. Les notes et bulletins associés sont **soft-archivés** : ils restent en base mais n'apparaissent plus dans les listings courants. Vous pouvez les retrouver dans `Notes → Filtres avancés → Inclure les annulés`.

Si l'étudiant avait payé, son paiement n'est PAS automatiquement remboursé : c'est une décision administrative. Le module comptabilité affiche les paiements rattachés à des inscriptions annulées dans une rubrique dédiée pour faciliter le traitement des remboursements.

## Exporter et imprimer

Depuis la liste des inscriptions, le bouton « Export » propose :

- **CSV** — pour Excel ou Google Sheets, avec toutes les colonnes
- **PDF liste** — un document avec en-tête établissement et toutes les inscriptions filtrées
- **PDF détaillé** — un document par étudiant (utile pour archivage papier)

Vous pouvez aussi générer le **dossier individuel** d'un étudiant depuis sa fiche : `Étudiants → [étudiant] → Documents → Dossier complet`. Le PDF inclut l'inscription, l'historique des paiements, les notes, le bulletin courant et les présences.

## Erreurs fréquentes et résolution

| Symptôme | Cause probable | Solution |
| --- | --- | --- |
| Le bouton « Valider » ne réagit pas | Aucun paiement validé | Validez d'abord le paiement dans `Comptabilité → Paiements` |
| Toujours « En attente » après validation | `workflow_step != etudiant_cree` | Rafraîchir la page, ou vérifier que la validation a bien commit la transaction |
| « Classe pleine » alors qu'il reste des places | Cache navigateur | Cliquer dans le sélecteur de classe (re-fetch AJAX) |
| L'étudiant n'apparaît pas dans les effectifs | Filtre `workflow_step` actif | Vérifier que la validation est complète, ou enlever le filtre temporairement |
| L'export PDF est vide | Filtre actif sans résultat | Vérifier les filtres actifs (statut, année, classe) |

Pour toute autre question, contactez le service technique via [contact@klassci.com](mailto:contact@klassci.com).

## Prochaine étape

Une fois vos inscriptions sous contrôle, vous voudrez sans doute consulter le guide **Comptable** pour comprendre comment les paiements arrivent dans le module financier et comment générer les relances. Ce guide sera publié prochainement.
