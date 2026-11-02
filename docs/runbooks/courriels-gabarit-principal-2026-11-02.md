# Courriels KLASSCI — gabarit principal (2 novembre 2026)

## Modèle de référence retrouvé
- Contenu métier des alertes d'encaissement : `resources/views/esbtp/emails/equipe/alerte-encaissements.blade.php`.
- Habillage réel : `resources/views/esbtp/emails/parents/recu.blade.php`, partagé par les avis aux parents. Ce rendu clair à liseré et identité école est désormais la référence de présentation.
- Ancien habillage : `resources/views/esbtp/emails/layout-legacy.blade.php` (conservé sans migration destructive). Pour comparer / revenir provisoirement à l'ancien style sur un modèle, utiliser `@extends('esbtp.emails.layout-legacy')`.

## Périmètre migré
- Les avis aux parents et l'alerte « Encaissements en retard » héritaient **déjà** de `parents.recu` : aucune modification du contenu métier.
- Tous les modèles qui héritent de `esbtp.emails.layout` prennent le même habillage automatiquement : annonce, accès étudiant, code de connexion, confirmation du contact, activation de préinscription, réponse/vérification du support, convocation de rendez-vous.
- `subtitle`, `preheader`, `mention`, identité de l'école, couleurs du tenant, adresse, logo, liens d'action et contenu restent disponibles dans la nouvelle enveloppe.
- Les prévisualisations d'annonces appellent le même rendu que la diffusion MailPulse (via `CourrielAnnonce`).
- Le changement ne crée aucun nouveau canal, aucun nouvel envoi, aucune relance et ne change pas les destinataires.

## Autres chemins à contrôler avant d'affirmer « partout »
Les notifications Laravel basées sur `MailMessage` (par exemple `app/Notifications/ESBTPNotification.php`, `PaiementNotification.php`, `TpeDeclarationStatusChangedNotification.php`, etc.) empruntent le Markdown natif de Laravel et ne passent **pas** par l'enveloppe Blade `esbtp.emails.layout`. Les e-mails arbitraires définis directement par des prestataires externes ou des templates de leur interface ne peuvent pas être uniformisés seulement en changeant ce dépôt. Il faut réviser et tester ces voies individuellement, sans convertir globalement des e-mails de sécurité à l'aveugle.

## Recette requise
- Tests `GabaritRecuDesAvisParentsTest`, `CourrielAnnonceHtmlTest`, `GabaritPrincipalCourrielsTest` et `MailPulseTransportTest`.
- Rendu Gmail/Outlook/mobile du message étudiant et de l'alerte comptable, vérification logo local/URL, lien et préheader.
- Contrôle des événements MailPulse (ne jamais relancer des messages soumis / délivrés par un simple changement de template).
