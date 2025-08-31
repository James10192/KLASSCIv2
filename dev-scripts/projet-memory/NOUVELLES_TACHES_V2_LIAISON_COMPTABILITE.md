# 📋 NOUVELLES TÂCHES V2.0 - LIAISON COMPTABILITÉ INSCRIPTION DÉPENSES

## 🎯 **Vue d'ensemble du sprint V2.0**

Extension majeure du système ESBTP KLASSCI pour créer un workflow intégré et sécurisé :
- **Inscription → Validation → Étudiant** avec paiement obligatoire
- **Bon de Sortie → Approbation → Dépense** avec traçabilité complète

## 📊 **6 Nouvelles Tâches - Architecture de Dépendances**

```
Task #15 (Infrastructure) 
    ↓
Task #16 (Workflow Service) ← Task #19 (Bon Approbation)
    ↓                              ↓
Task #17 (Interface)          Task #20 (Liaison Bon-Dépense)
    ↓
Task #18 (Refactoring)
```

## 🔧 **Détails des Tâches**

### **Tâche #15 : Infrastructure Liaison Comptabilité-Inscription**
- **Priorité** : 🔥 HAUTE  
- **Type** : Backend Development
- **Dépendances** : Aucune (tâche foundation)
- **Estimation** : 2-3 jours

**Livrables clés :**
- 4 migrations Laravel sécurisées
- Extensions tables : `workflow_step`, `paiement_validation_id`, `comptabilite_activee`
- Table `esbtp_inscription_workflow_history` pour audit trail
- Gestion places classes : `places_totales`, `places_occupees`, `places_disponibles`

**Sous-tâches (5) :**
1. Migration extension esbtp_inscriptions
2. Migration esbtp_inscription_workflow_history  
3. Migration extension esbtp_classes
4. Mise à jour modèles Laravel
5. Seeders et tests

---

### **Tâche #16 : Service Workflow Inscription vers Étudiant**
- **Priorité** : 🔥 HAUTE
- **Type** : Backend Development  
- **Dépendances** : Task #15
- **Estimation** : 3-4 jours

**Livrables clés :**
- `InscriptionWorkflowService.php` : validation et gestion workflow
- `ClasseManagementService.php` : attribution classes automatique
- Extensions contrôleurs avec méthodes création étudiant
- Events Laravel : `InscriptionValidated`, `EtudiantCreated`

**Workflow states :** `prospect → documents_complets → en_validation → valide → etudiant_cree`

**Sous-tâches (5) :**
1. InscriptionWorkflowService création
2. ClasseManagementService création
3. Extension ESBTPEtudiantController
4. Events et Listeners Laravel
5. Tests intégration workflow

---

### **Tâche #17 : Interface Validation Inscription avec Modal Paiement**
- **Priorité** : 🟡 MOYENNE
- **Type** : Frontend Development
- **Dépendances** : Task #16
- **Estimation** : 2-3 jours

**Livrables clés :**
- Modal Bootstrap 5 paiement rapide
- Sélecteur types paiements (scolarité, cantine, autres)
- Vérification temps réel places classes avec alternatives
- AJAX validation avec feedback visuel
- Indicateurs états workflow avec progression

**Sous-tâches (5) :**
1. Modal paiement Bootstrap 5
2. Vérification places temps réel
3. Interface états workflow
4. Validation AJAX inscription
5. Tests interface utilisateur

---

### **Tâche #18 : Nettoyage Séparation Logique Comptabilité-Inscription**
- **Priorité** : 🟡 MOYENNE
- **Type** : Code Refactoring
- **Dépendances** : Task #17
- **Estimation** : 2-3 jours

**Objectif :** Découpler complètement logique comptabilité des pages inscription

**Livrables clés :**
- Contrôleurs inscription nettoyés (suppression méthodes comptabilité)
- Vues inscription purifiées (suppression références comptabilité)
- Relations modèles clarifiées et spécialisées
- Permissions granulaires : `inscription.validate` vs `comptabilite.manage`
- Tests régression fonctionnalités existantes

**Sous-tâches (5) :**
1. Nettoyage ESBTPInscriptionController
2. Purification vues inscription
3. Découplage modèles relations
4. Permissions granulaires
5. Tests régression complets

---

### **Tâche #19 : Infrastructure Bon de Sortie - Approbation Simplifiée**
- **Priorité** : 🔥 HAUTE
- **Type** : Backend Development
- **Dépendances** : Task #15
- **Estimation** : 2-3 jours

**Innovation :** Approbation sans nouveaux rôles (utilise 4 rôles existants : superadmin, secretaire, enseignant, etudiant)

**Livrables clés :**
- Extension `esbtp_bons_sortie` : `approbateur_id`, `notification_sent_at`, `approved_at`
- Table `esbtp_bon_sortie_notifications` pour traçabilité
- NotificationService étendu avec email/app automatique
- Templates notifications approbation

**Workflow :** `créateur → sélection approbateur → notification → approbation`

**Sous-tâches (5) :**
1. Migration extension esbtp_bons_sortie
2. Table notifications bon sortie
3. Extension NotificationService
4. ESBTPBonSortieController workflow
5. Templates notifications

---

### **Tâche #20 : Liaison Obligatoire Bon de Sortie - Dépense**
- **Priorité** : 🔥 HAUTE
- **Type** : Backend Development
- **Dépendances** : Task #19
- **Estimation** : 3-4 jours

**Contrôle financier :** Aucune dépense sans bon de sortie approuvé

**Livrables clés :**
- Migration `bon_sortie_id NOT NULL` dans esbtp_depenses
- `BonDepenseService` : validation liaison obligatoire
- Interface dépenses rénovée avec sélecteur bons approuvés
- Modal création bon rapide depuis interface dépenses
- Tests intégration end-to-end : inscription→étudiant→bon→dépense

**Sous-tâches (5) :**
1. Migration bon_sortie_id obligatoire
2. BonDepenseService création
3. ESBTPDepenseController extension
4. Interface dépenses rénovée
5. Tests workflow complet

## 📈 **Impact Business & Technique**

### **Bénéfices Métier**
- **Contrôle Financier** : Traçabilité complète flux inscription → dépenses
- **Workflow Sécurisé** : Validation obligatoire avant activation comptabilité
- **Approbation Flexible** : Système approbation bon sans création nouveaux rôles
- **Audit Trail** : Historique complet toutes actions validation/approbation

### **Améliorations Techniques**
- **Architecture Découplée** : Séparation claire inscription/comptabilité/dépenses
- **Events Laravel** : Architecture event-driven pour notifications automatiques
- **Interface Moderne** : Modals Bootstrap 5 responsive avec AJAX temps réel
- **Performance** : Gestion places classes optimisée avec colonnes calculées

## 🔗 **Intégration Existant**

Ces nouvelles tâches s'intègrent parfaitement avec les **14 tâches V1.0 terminées** :
- Utilise `ComptabiliteService`, `NotificationService`, `PDFService` existants
- S'appuie sur dashboard comptabilité et KPIs temps réel (Tasks #3, #13, #14)
- Enrichit système relances et workflow bons existants (Tasks #4, #5, #8)
- Compatible infrastructure events et permissions KLASSCI

## 🚀 **Planning de Développement**

**Semaine 1** : Tasks #15, #19 (Infrastructure parallèle)
**Semaine 2** : Task #16 (Workflow service inscription)
**Semaine 3** : Tasks #17, #18 (Interface et refactoring)
**Semaine 4** : Task #20 (Intégration finale bon-dépense)

**Total estimation :** 14-20 jours pour workflow complet intégré

---

*Créé le 10 juillet 2025 - Version 2.0 Extension ESBTP KLASSCI*
*PRD source : PRD_LIAISON_COMPTABILITE_INSCRIPTION_DEPENSES.txt*