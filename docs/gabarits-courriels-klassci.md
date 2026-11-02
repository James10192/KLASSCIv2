# Gabarits des courriels KLASSCI — novembre 2026

## Référence visuelle choisie

Le courrier de comptabilité **« Encaissements en retard »** est
`resources/views/esbtp/emails/equipe/alerte-encaissements.blade.php`.
Il étend `esbtp.emails.parents.recu` : **c'est ce dernier fichier** qui définit
le design (fond blanc, liseré 4 px couleur d'établissement, petit logo, pastille
de statut, titre, contenu, coordonnées et pied « Envoyé avec KLASSCI »).

La variante historique avec grand en-tête dégradé est conservée **sans
modification** sous `resources/views/esbtp/emails/layout-ancien.blade.php`.
Une régression visuelle peut donc être diagnostiquée sans reconstituer un
template ancien depuis l'historique Git.

## Chemins pris en charge

| Famille | Vue | Habillage |
| --- | --- | --- |
| Avis aux parents (11 types), alerte d'encaissements | `esbtp.emails.parents.*`, `esbtp.emails.equipe.alerte-encaissements` | `parents.recu` (déjà existant) |
| Annonces massives, invitation, vérification et accès étudiants, support, convocation | `esbtp.emails.layout` | Adaptateur vers `parents.recu` |
| Fin des tâches de bulletins | `esbtp.emails.tache-bulletins-terminee` | `parents.recu` |
| Rapports Analytics attachés | `esbtp.emails.exportable-report` | `parents.recu` |
| Demande de démo KLASSCI (prospection) | `emails.demo-request` | `parents.recu` avec identité KLASSCI |
| Débogage d'intégration de logo, non transactionnel | `emails.test-logo` | Diagnostic brut conservé, non destiné aux utilisateurs |

La vue historique `emails.exportable-report` est conservée comme référence
mais le Mailable pointe vers la nouvelle vue `esbtp.emails.exportable-report`.

Les contenus d'événements propres à chaque métier restent **indépendants** :
l'enveloppe graphique ne doit jamais fusionner les textes, les destinataires,
les consentements, les quotas, les identifiants MailPulse ou les déclencheurs.

## Convention pour un nouveau courriel

- Courriel standard : `@extends('esbtp.emails.layout')` avec
  `emailTitle` et une section `content`.
- Courriel avec indicateur de statut/montant ou vedette : 
  `@extends('esbtp.emails.parents.recu')` et les variables de statut du
  design system + sections `titre`, `preheader`, `content`.
- La couleur et le logo sont résolus par le composeur
  `IdentiteDesCourriels`, avec une valeur de repli KLASSCI. Pas de couleur
  d'établissement codée directement dans le template.
- Les boutons utilisent `esbtp.emails.partials.bouton` ; les lignes
  descriptives, progressions et citations réutilisent les partials parents.
- Ne pas réintroduire le grand bandeau `layout-ancien` dans les envois
  actifs sans modification explicitement demandée.

## Contrôles de recette

Exécuter :
```bash
php artisan view:cache
php artisan test tests/Unit/Mail/GabaritPrincipalCommunTest.php
php artisan test tests/Unit/Mail/CourrielAnnonceHtmlTest.php
php artisan test tests/Unit/Mail/GabaritRecuDesAvisParentsTest.php
```
Comparer l'aperçu MailPulse et le rendu réel sur Gmail/Outlook et écran
étroit, y compris liens à usage unique, images d'établissement, HTML
échappé, limites d'octets et pied de page.
