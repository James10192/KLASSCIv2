# Configuration pédagogique par classe, année et semestre

## Accès

Page : `esbtp.bulletins.configuration-groupe` (`GET /esbtp-special/bulletins/configuration-groupe`).

Paramètres : `classe_id`, `annee_universitaire_id`, `periode` (`semestre1` / `semestre2` en BTS ; semestres réellement autorisés par la classe LMD). Les sélecteurs de classe, d'année et de semestre actualisent le groupe par requête AJAX avec historique du navigateur. Le lien « Configuration par groupe » est aussi présent dans l'ancien écran BTS de configuration des matières.

## BTS

- Un tableau unique affiche les matières BTS autorisées pour cette classe. Quand la maquette semestrielle est **complète**, les matières officielles sont filtrées par S1/S2 ; les évaluations historiques de la période restent visibles même hors maquette. Sans maquette validée, le repli historique conserve la liste de classe.
- L'enregistrement passe par le service `BulletinInlineConfigurationService`, auquel le serveur impose l'appartenance des matières à la classe/période ; une matière forgée est refusée.
- **Isolation :** le coefficient est désormais sauvegardé dans le JSON `esbtp_config_matieres.config.coefficient` (clé classe + année + période), jamais dans la table globale `esbtp_matiere_coefficients` qui est partagée par toutes les classes de filière/niveau.
- `BulletinService::getCoefficientForCombination` utilise en priorité la surcharge locale si elle existe ; en S1, la surcharge de classe tronc commun résolue pour l'étudiant est aussi respectée. Sinon, les règles historiques continuent de s'appliquer (filière/niveau, autre période, tronc commun).
- Le coefficient hérité est présenté comme **placeholder** et n'est pas copié en coefficient local lors d'un enregistrement ordinaire. Effacer une surcharge locale revient à l'héritage.
- L'ancien écran de classification et l'action « copier vers l'autre semestre » préservent/copie désormais correctement ce coefficient local sans casser le JSON typé.
- Les professeurs demeurent dans le modèle de configuration déjà utilisé par les bulletins, par classe/année/période.

## LMD

La page affiche les UE du semestre **résolues par `LMDBulletinService::getUEsForSemestre`**, donc selon le parcours et ses pivots officiels, et leurs ECUE effectifs. La modification des crédits/coefficient ECUE et de la composition reste dans l'éditeur UE/ECUE existant ; les affectations d'enseignants restent dans la planification LMD existante.

**Attention :** une maquette UE/ECUE peut être partagée entre plusieurs classes du même parcours et semestre. La page le signale explicitement ; cette PR ne crée pas d'écriture locale clandestine sur une maquette partagée.

## Autorisations et vérifications

- Page protégée par identité académique, paywall et `bulletins.configure` pour BTS ou `module.lmd.access` + `lmd.structure.view` pour LMD.
- Enregistrement BTS protégé par `bulletins.configure` et paywall. L'édition effective LMD demeure protégée par `lmd.structure.manage` / `lmd.planning.edit` selon la ressource.
- Les écritures de coefficients globaux depuis les évaluations exigent désormais `bulletins.configure`. Leur rôle reste distinct des surcharges propres au bulletin de classe.
- Tests : deux classes de même filière/niveau et de même année conservent des coefficients différents ; rejet d'une matière étrangère ; héritage sans surcharge ; indépendance S1/S2 ; compatibilité JSON historique ; restauration d'une ligne archivée.

Vérification manuelle attendue : passer de S1 à S2, changer de classe ou d'année, modifier un coefficient BTS puis comparer les moyennes de deux classes de même filière ; contrôler sur LMD que les UE et ECUE d'un parcours partagé ne fuient pas dans le parcours voisin.
