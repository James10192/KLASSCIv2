# Rule: Workflow d'inscription configurable par tenant

## Quand s'active

Cette rule s'active automatiquement quand tu :
- touches aux candidatures, rendez-vous, préinscriptions ou inscriptions ;
- modifies l'ordre caisse / contrôle physique des pièces ;
- crées ou actives un compte étudiant pendant l'admission ;
- modifies le choix ou l'affectation de classe ;
- ajoutes un envoi d'identifiants ou de liens d'activation par e-mail / WhatsApp ;
- travailles sur `ESBTPCandidatureController`, `ESBTPInscriptionController`, `DossierPiecesEtudiant` ou les parcours publics d'inscription.

## Pourquoi cette rule existe

KLASSCI est développé et validé sur `presentation`, puis le même code est propagé aux établissements. Le parcours demandé par une école ne doit donc jamais devenir implicitement le parcours de toutes les autres.

Demande métier ESBTP Yamoussoukro (30/09/2026) :

```text
Candidature en ligne
→ prise de rendez-vous
→ passage à la caisse / paiement de préinscription
→ contrôle physique des pièces par l'administration
→ activation / finalisation du dossier étudiant
→ choix de la classe par l'étudiant (une seule fois)
→ inscription académique définitive
```

D'autres tenants peuvent conserver le flux historique ou demander l'ordre inverse (pièces avant caisse). La solution doit donc être une politique de tenant, jamais une suite de `if (school == yakro)`.

## Règles non négociables

1. **Désactivé par défaut.** Le déploiement d'une nouvelle version ne change pas le comportement d'un tenant existant.
2. **Aucun nom d'établissement dans la logique métier.** Pas de `if Yakro`, pas de sous-domaine codé en dur.
3. **Ordre configurable.** Au minimum : historique, caisse avant pièces, pièces avant caisse.
4. **Étapes indépendantes.** Rendez-vous obligatoire ou non ; activation du compte après paiement ou après contrôle des pièces ; choix de classe par administration ou étudiant.
5. **Choix étudiant verrouillable.** Si l'étudiant choisit sa classe, le tenant peut imposer un choix unique ; toute correction ultérieure passe par une permission administrative et doit être auditée.
6. **Canaux configurables.** E-mail et WhatsApp sont activables indépendamment. Un canal indisponible ne doit pas annuler la transition métier.
7. **Permissions minimales.** La caisse valide le financier ; l'administration contrôle les pièces ; aucun rôle ne reçoit des droits académiques globaux pour satisfaire une étape locale.
8. **Pas de conversion prématurée.** Une candidature publique reste inerte jusqu'à l'étape prévue par la politique. Ne pas créer une inscription académique complète uniquement pour permettre un paiement ou le contrôle documentaire.
9. **Réutiliser le module pièces existant.** Étendre son support aux dossiers provisoires si nécessaire, ne pas créer un second catalogue de pièces.
10. **Tests obligatoires.** Toujours couvrir au minimum : workflow désactivé = comportement historique ; workflow caisse→pièces ; workflow pièces→caisse ; options indépendantes.

## Réglages canoniques

La définition des clés vit dans `App\Services\Admissions\InscriptionWorkflowSettings` :

- `inscriptions.workflow.enabled`
- `inscriptions.workflow.mode`
- `inscriptions.workflow.require_rdv`
- `inscriptions.workflow.account_activation_step`
- `inscriptions.workflow.class_choice_actor`
- `inscriptions.workflow.class_choice_once`
- `inscriptions.workflow.notify_email`
- `inscriptions.workflow.notify_whatsapp`

Ne recopier aucune de ces chaînes dans plusieurs services : utiliser les constantes.

## Politique ESBTP Yamoussoukro

Configuration attendue :

```text
workflow.enabled = true
workflow.mode = caisse_avant_pieces
workflow.require_rdv = true
workflow.account_activation_step = after_payment
workflow.class_choice_actor = student
workflow.class_choice_once = true
workflow.notify_email = true
workflow.notify_whatsapp = true
```

Cette configuration décrit Yamoussoukro ; elle ne constitue pas le défaut global de KLASSCI.

## Déploiement

1. développer sur une branche issue de `presentation` ;
2. tests unitaires / feature ;
3. fusion sur `presentation` ;
4. activer le workflow uniquement sur l'instance de démonstration pour recette ;
5. valider le parcours complet ;
6. propager le code ;
7. activer tenant par tenant selon décision de l'établissement.
