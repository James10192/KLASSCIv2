# Rule: Workflow d'inscription configurable — JAMAIS de parcours hardcodé par école

## Quand s'active

Cette rule s'active automatiquement quand tu touches :
- au portail public d'inscription / candidature ;
- aux rendez-vous d'inscription ;
- à la pré-inscription en caisse ;
- au contrôle des pièces du dossier ;
- au choix de classe ;
- à l'activation du compte étudiant ;
- à la conversion prospect → étudiant.

## Règle absolue

KLASSCI est multi-instance. **Le code ne doit jamais contenir un test sur `ESBTP Yakro`, `ESBTP Abidjan`, un domaine, un tenant_code ou un nom d'établissement pour décider du parcours.**

Une seule base de code est déployée sur `presentation`, puis propagée aux instances. L'ordre des étapes est donc une **politique d'instance** portée par les Settings et les permissions.

Interdits :

```php
if (config('app.tenant_code') === 'esbtp-yakro') { ... } // ❌
if (str_contains($schoolName, 'Yamoussoukro')) { ... }   // ❌
if ($user->hasRole('caissier')) { ... }                  // ❌ pour une capacité métier
```

Attendu :

```php
$workflow = app(\App\Services\Inscription\AdmissionWorkflowPolicy::class);

if ($workflow->caisseAvantPieces() && $user->can('paiements.create')) {
    // ...
}
```

## Source de vérité

Le service `App\Services\Inscription\AdmissionWorkflowPolicy` porte la lecture des réglages :

- `inscriptions.workflow.mode`
  - `standard` : comportement historique ;
  - `caisse_puis_pieces` : caisse avant contrôle administratif ;
  - `pieces_puis_caisse` : contrôle administratif avant caisse.
- `inscriptions.workflow.student_class_choice`
  - `0` : la classe est choisie par un agent comme aujourd'hui ;
  - `1` : l'étudiant choisit sa classe dans son espace, parmi les classes autorisées.
- `inscriptions.workflow.class_choice_once`
  - `1` : le choix étudiant est verrouillé après confirmation ; correction ultérieure uniquement par permission et avec audit.
- `inscriptions.workflow.activation_stage`
  - `payment`, `documents`, `class` ou `validation`.

Les réglages existants restent sources de vérité pour leur sujet :
- `caisse.pre_inscription.enabled` : la caisse fait-elle la pré-inscription ?
- `inscriptions.rdv.enabled` / `inscriptions.rdv.obligatoire` : rendez-vous activé / obligatoire ;
- `inscriptions.portail.verification_contact` : vérification e-mail/WhatsApp ;
- catalogue et suivi `pieces_dossier.*` : pièces physiques à fournir.

## Configuration cible ESBTP Yamoussoukro

La configuration métier demandée est :

```text
Demande en ligne
→ prise de rendez-vous
→ caisse / paiement de préinscription
→ contrôle physique des pièces par l'administration
→ finalisation des informations dans l'espace étudiant
→ choix unique de la classe par l'étudiant
→ inscription académique définitive
```

Elle se traduit par des Settings, **pas par du code spécifique Yakro** :

```text
caisse.pre_inscription.enabled = 1
inscriptions.rdv.enabled = 1
inscriptions.rdv.obligatoire = 1
inscriptions.workflow.mode = caisse_puis_pieces
inscriptions.workflow.student_class_choice = 1
inscriptions.workflow.class_choice_once = 1
inscriptions.workflow.activation_stage = payment
```

Une autre école peut garder `standard`, inverser caisse/pièces, désactiver la pré-inscription caisse ou conserver le choix de classe par l'agent sans modifier une ligne de code.

## Réutiliser l'existant

### Demandes en ligne

`ESBTPCandidature` reste la demande publique inerte. Elle ne doit pas créer silencieusement une inscription définitive.

### Caisse

Le flux caisse existant crée déjà un étudiant minimal + une inscription `workflow_step = prospect` et peut rattacher un paiement. Pour les modes où l'étudiant choisit sa classe plus tard :
- `classe_id` n'est pas exigé à la caisse ;
- `filiere_id` et `niveau_id` provisoires viennent de la candidature ;
- le prospect reste non validé tant que les prérequis configurés ne sont pas satisfaits.

### Pièces du dossier

**Ne pas créer un deuxième module.** Le contrôle physique utilise le module existant `DossierPiecesEtudiant` / `pieces_dossier.*` : original papier faisant foi, pièces obligatoires, quantité, validation/refus motivé, scan facultatif, synthèse complet/incomplet.

Quand la synthèse devient complète, le workflow peut avancer vers `documents_complets` si la politique de l'instance le demande.

### Choix de classe étudiant

Si `student_class_choice = 1` :
- l'étudiant ne voit que les classes actives compatibles avec son année, sa filière/parcours et son niveau ;
- capacité et règles BTS/LMD sont vérifiées côté serveur ;
- le premier choix confirmé est verrouillé si `class_choice_once = 1` ;
- tout changement administratif ultérieur exige une permission dédiée ou une permission existante de réaffectation, un motif et une trace d'audit ;
- aucun `hasRole()`.

## Permissions, jamais organisation imposée

Le parcours doit fonctionner avec les permissions, pas avec les intitulés de poste.

Exemples :
- voir les demandes : `inscriptions.candidatures.view` ;
- traiter académiquement une candidature : `inscriptions.candidatures.process` ;
- créer un paiement : permission de paiement existante ;
- suivre les pièces : `pieces_dossier.suivre` ;
- modifier/réaffecter une classe : permission d'inscription correspondante.

Un caissier peut donc recevoir la permission de **voir** une demande et d'encaisser sans recevoir la permission de décision académique. Voir aussi `.claude/rules/customizable-roles.md`.

## Activation du compte et identifiants

Ne pas envoyer un mot de passe permanent en clair si le nouveau flux peut l'éviter. Préférer :
- identifiant + lien d'activation à usage limité ;
- choix du mot de passe par l'étudiant ;
- `must_change_password` / `first_login_at` comme garde-fou pour les comptes historiques.

Le canal d'envoi (e-mail, WhatsApp ou les deux) doit utiliser les mécanismes de notification existants et respecter la disponibilité/configuration de l'instance.

## Compatibilité au déploiement

1. Les nouvelles Settings ont des valeurs par défaut qui reproduisent le comportement historique (`standard`, choix de classe étudiant OFF, activation à la validation).
2. Une migration n'écrase jamais une valeur existante d'une instance.
3. Toute nouvelle étape doit être testée au moins dans :
   - le mode `standard` ;
   - `caisse_puis_pieces` ;
   - le mode avec choix de classe étudiant ;
   - les permissions insuffisantes.
4. Les vues masquent les actions non autorisées (`@can`) au lieu de conduire vers des 403.
5. Aucun nom d'école ni tenant n'apparaît dans la logique métier.

## Voir aussi

- `.claude/rules/customizable-roles.md`
- `app/Services/TenantScolariteSettings.php`
- `app/Services/Inscription/AdmissionWorkflowPolicy.php`
- `app/Services/InscriptionWorkflowService.php`
- `app/Domain/Admissions/DemandeDInscription.php`
- `app/Services/DossierPiecesEtudiant.php`
