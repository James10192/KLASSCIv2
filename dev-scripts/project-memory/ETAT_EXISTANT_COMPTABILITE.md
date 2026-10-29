# 📊 ÉTAT EXISTANT - Module Comptabilité ESBTP

## 🗄️ **MODÈLES EXISTANTS**

### ✅ **Modèles comptabilité déjà présents**

-   `ESBTPComptabiliteConfiguration` - Configuration du module comptabilité
-   `ESBTPPaiement` - Gestion des paiements
-   `ESBTPFacture` - Facturation
-   `ESBTPFactureDetail` - Détails des factures
-   `ESBTPDepense` - Gestion des dépenses
-   `ESBTPCategorieDepense` - Catégories de dépenses
-   `ESBTPFournisseur` - Gestion des fournisseurs
-   `ESBTPSalaire` - Gestion des salaires
-   `ESBTPBourse` - Gestion des bourses
-   `ESBTPTransactionFinanciere` - Transactions financières
-   `ESBTPTypeFrais` - Types de frais (déjà existe !)
-   `ESBTPFraisScolarite` - Frais de scolarité
-   `ESBTPRelance` - Système de relances (déjà existe !)
-   `ESBTPKPI` - KPIs (déjà existe !)
-   `ESBTPCategoriePaiement` - Catégories de paiement

### 🔍 **Modèles connexes**

-   `ESBTPEtudiant` - Étudiants
-   `ESBTPInscription` - Inscriptions
-   `ESBTPClasse` - Classes
-   `ESBTPFiliere` - Filières

## 📋 **MIGRATIONS EXISTANTES**

### ✅ **Tables comptabilité existantes**

-   `esbtp_comptabilite_configurations`
-   `esbtp_paiements`
-   `esbtp_factures` + `esbtp_facture_details`
-   `esbtp_depenses` + `esbtp_categories_depenses`
-   `esbtp_fournisseurs`
-   `esbtp_salaires`
-   `esbtp_bourses`
-   `esbtp_transactions_financieres`
-   `esbtp_types_frais` (déjà existe !)
-   `esbtp_frais_scolarite`
-   `esbtp_relances` (déjà existe !)
-   `esbtp_kpis` (déjà existe !)
-   `esbtp_categories_paiements`

## 🎯 **ANALYSE SELON LE PRD**

### ❌ **CE QUI MANQUE (à créer)**

Selon le document `adaptations_completes_klassci.md`, les nouvelles migrations nécessaires sont :

1. **Extensions de tables existantes :**

    - `esbtp_depenses` : ajouter colonnes workflow (numero_bon, statut_workflow, workflow_data, approved_by, date_approbation)
    - `esbtp_paiements` : ajouter colonnes (reference_externe, metadata, relance_id)

2. **Nouvelles permissions :**
    - comptabilite.dashboard.view
    - comptabilite.bons.approve
    - comptabilite.config.manage
    - comptabilite.reports.export
    - comptabilite.relances.send

### ✅ **CE QUI EXISTE DÉJÀ (ne pas recréer)**

-   Table `esbtp_types_frais` ✅
-   Table `esbtp_relances` ✅
-   Table `esbtp_kpis` ✅
-   Modèle `ESBTPTypeFrais` ✅
-   Modèle `ESBTPRelance` ✅
-   Modèle `ESBTPKPI` ✅

## 🚀 **PLAN D'ACTION**

### 1️⃣ **Extensions de tables (priorité haute)**

-   Étendre `esbtp_depenses` pour workflow bons de sortie
-   Étendre `esbtp_paiements` pour métadonnées et relances

### 2️⃣ **Nouvelles permissions (priorité haute)**

-   Ajouter les permissions manquantes dans la table `permissions`

### 3️⃣ **Validation des modèles existants**

-   Vérifier que les modèles existants ont toutes les relations nécessaires
-   S'assurer que les fillable et casts sont complets

## ⚠️ **ATTENTION - ÉVITER LES DOUBLONS**

-   **NE PAS** recréer `esbtp_types_frais` (existe déjà)
-   **NE PAS** recréer `esbtp_relances` (existe déjà)
-   **NE PAS** recréer `esbtp_kpis` (existe déjà)
-   **NE PAS** recréer les modèles correspondants

## 📝 **DOCUMENTATION RÉFÉRENCE**

-   Basé sur `adaptations_completes_klassci.md`
-   Vérifié par grep search dans app/Models et database/migrations
-   Date de vérification : $(date)
