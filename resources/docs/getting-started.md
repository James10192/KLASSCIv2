## Qu'est-ce que KLASSCI

KLASSCI est une plateforme de gestion scolaire conçue pour les **établissements d'enseignement supérieur en Afrique** : universités, grandes écoles, instituts BTS, licences professionnelles. Un seul outil pour remplacer les fichiers Excel, les cahiers de notes, les tableaux d'affichage et les bases Access vieillissantes.

La plateforme couvre l'ensemble du cycle de vie d'un étudiant, de la pré-inscription à la délivrance du diplôme :

- inscriptions et ré-inscriptions, avec workflow de validation et gestion des paiements
- emploi du temps, séances de cours et émargement enseignant
- présences étudiants, codes d'émargement, rapports d'assiduité
- saisie des notes, calcul automatique des moyennes, génération des bulletins
- comptabilité : frais, paiements, relances, dashboards financiers
- système LMD complet (UE, ECUE, crédits ECTS, parcours) en parallèle du BTS classique
- communication : annonces, messages, notifications

Chaque établissement dispose de **sa propre base de données isolée**. Vos données ne sont jamais partagées avec d'autres écoles.

## Pour qui

KLASSCI est pensé pour les profils suivants :

| Rôle | Ce qu'il fait dans KLASSCI |
| --- | --- |
| Super-administrateur | Paramétrage initial, gestion des comptes, configuration des frais, supervision globale |
| Coordinateur | Pilotage pédagogique, planning général, charge par classe |
| Secrétaire | Inscriptions, ré-inscriptions, dossiers étudiants, génération de documents |
| Enseignant | Saisie des notes, émargement, consultation des classes, rapports |
| Comptable | Suivi des paiements, relances, situations financières, exports comptables |
| Caissier | Pré-inscription rapide, encaissement, génération de reçus |
| Étudiant | Consultation des notes, bulletins, situation financière, emploi du temps |

Les rôles disposent de permissions granulaires gérées via Spatie Permission. Un super-administrateur peut activer ou désactiver des **modules complets** (LMD, comptabilité, communication...) selon le plan de l'établissement.

## Vocabulaire essentiel

Quelques termes que vous croiserez souvent dans la plateforme et dans le reste de cette documentation :

- **Filière** — la spécialité d'enseignement (ex. : Génie Civil, Bâtiment, Informatique de Gestion).
- **Niveau d'étude** — l'année dans le cursus (BTS1, BTS2, L1, L2, L3, M1, M2). Un niveau appartient à un cycle (BTS, Licence, Master).
- **Classe** — l'unité opérationnelle qui regroupe des étudiants pour une année universitaire donnée. Toujours liée à une filière + un niveau d'étude.
- **Année universitaire** — la période académique (ex. : 2025-2026). Une seule est marquée comme « courante » à la fois.
- **Inscription** — le lien entre un étudiant et une classe pour une année universitaire. Possède un `status` (active / annulée) et une `workflow_step` (pre_inscrit, etudiant_cree, etc.).
- **Matière** — l'unité d'enseignement à laquelle on attache des notes. En LMD, une matière est une **ECUE** liée à une **UE** (Unité d'Enseignement) avec des crédits ECTS.
- **Évaluation** — un type d'évaluation (devoir, partiel, examen final, projet) configuré pour une matière.
- **Note** — la note d'un étudiant à une évaluation donnée.
- **Bulletin** — la synthèse des résultats d'un étudiant pour une période (semestre ou année).
- **Tronc commun** — un mode d'inscription en deux temps : tronc commun en S1, puis spécialisation en S2 (ou inversement selon la filière).

## Premières étapes après création de votre compte

Une fois votre établissement provisionné par l'équipe KLASSCI, vous recevez vos identifiants super-administrateur. Voici l'ordre recommandé :

1. **Vérifiez l'année universitaire courante** dans `Paramètres → Configuration → Année universitaire`. Si elle n'est pas créée, créez-la et marquez-la `is_current = true`.
2. **Configurez les filières** (`Académique → Filières`) puis les **niveaux d'étude** (`Académique → Niveaux`).
3. **Créez les classes** pour l'année courante (`Académique → Classes`). Chaque classe est l'intersection d'une filière + un niveau pour une année donnée.
4. **Définissez les matières** (`Académique → Matières`). Pour le LMD, créez d'abord les UE puis les ECUE rattachées.
5. **Paramétrez les frais** (`Comptabilité → Frais → Configurer`). Définissez les catégories (inscription, scolarité, examens...) et les montants par filière + niveau.
6. **Importez ou créez les comptes du personnel** (`Personnel`). Chaque membre reçoit ses identifiants par email. Le mot de passe est valable 6 mois et expire avec une alerte 1 mois avant.
7. **Démarrez les inscriptions étudiants** (`Étudiants → Inscriptions → Nouvelle inscription`).

Le guide [Super-administrateur — installation initiale](/docs/superadmin/onboarding) détaille chaque étape avec les écrans correspondants.

## Connexion et environnements

KLASSCI est accessible depuis n'importe quel navigateur moderne (Chrome, Firefox, Safari, Edge). Aucune installation locale, aucune extension à télécharger.

Chaque établissement dispose d'une URL dédiée du type `https://votre-ecole.klassci.com`. Sur mobile, l'interface est entièrement responsive : les enseignants saisissent leurs notes depuis leur téléphone sans application native dédiée.

:::callout
**Bon à savoir.** Les sessions expirent au bout de 12 heures d'inactivité. Si vous perdez votre mot de passe, utilisez le lien « Mot de passe oublié » sur la page de connexion : un email de réinitialisation est envoyé sous 60 secondes.
:::

## Sécurité de vos données

KLASSCI applique plusieurs niveaux de protection :

- **Isolation par tenant** : chaque école a sa propre base MySQL, hébergée séparément.
- **HTTPS obligatoire** : toutes les communications sont chiffrées (TLS 1.3 sur les domaines klassci.com).
- **Permissions par rôle** : un comptable ne voit pas les notes, un enseignant ne voit pas la comptabilité.
- **Audit IDOR** : protection systématique contre les accès directs non autorisés (`Policy` Laravel sur les ressources sensibles).
- **Rate limiting** : les routes monétaires et d'authentification sont limitées pour bloquer les attaques en force brute.
- **Sauvegardes** : sauvegardes automatiques quotidiennes, conservées 30 jours.

## Prochaines étapes

Selon votre rôle, continuez par l'un des guides suivants :

- [Super-administrateur — installation initiale](/docs/superadmin/onboarding) si vous configurez votre établissement pour la première fois
- [Secrétaire — gérer les inscriptions](/docs/secretaire/inscriptions) si vous gérez les dossiers étudiants au quotidien

Les guides par module (LMD, emploi du temps, notes, comptabilité, présences) sont en cours de rédaction et seront publiés progressivement. Pour toute question ou demande de démo, écrivez à [contact@klassci.com](mailto:contact@klassci.com).
