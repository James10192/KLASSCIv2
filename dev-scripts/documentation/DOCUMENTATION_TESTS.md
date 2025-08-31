# 📋 Documentation des Tests - Planning Général & Système Enseignant

## 🎯 Vue d'ensemble

Le système de **Planning Général** et **Gestion des Enseignants** a été complètement refondu avec une approche moderne inspirée des meilleures pratiques d'écoles comme IFRAN Abidjan.

---

## 🔐 Accès aux Interfaces

### 1. **Planning Général - Vue Principale**
```
URL: /esbtp/planning-general
Description: Interface principale du planning académique
Fonctionnalités: Vue d'ensemble, statistiques, navigation vers autres modules
```

### 2. **Planning Général - Interface de Test/Planification**
```
URL: /esbtp/planning-general/test
Description: Interface de planification académique par filière/niveau/semestre
Fonctionnalités: Création, modification, validation des planifications
```

### 3. **Gestion des Enseignants - Nouveau Système**
```
URL: /esbtp/enseignants
Description: Interface moderne de gestion des profils enseignants
Fonctionnalités: Profils détaillés, disponibilités, affectations
```

### 4. **Dashboard Enseignants**
```
URL: /esbtp/enseignants/dashboard
Description: Tableau de bord avec statistiques des enseignants
Fonctionnalités: KPIs, répartitions, alertes
```

---

## 🧪 Tests Recommandés

### **Phase 1: Test du Planning Général**

#### Test 1.1: Accès à l'interface principale
```bash
# Naviguer vers la vue principale
URL: http://localhost:8000/esbtp/planning-general

# Vérifications:
✅ Affichage des statistiques générales
✅ Sélection d'année universitaire fonctionnelle
✅ Navigation vers autres modules
✅ Bouton "Gestion Enseignants" visible et fonctionnel
```

#### Test 1.2: Interface de planification
```bash
# Naviguer vers l'interface de test
URL: http://localhost:8000/esbtp/planning-general/test

# Vérifications:
✅ Sélecteurs année/filière/niveau/semestre fonctionnels
✅ Affichage des statistiques de planification
✅ Formulaire d'ajout de planification
✅ Liste des planifications existantes
✅ Actions de validation/suppression
```

#### Test 1.3: Création d'une planification
```bash
# Dans l'interface de test:
1. Sélectionner: Année 2025-2026, BTS1 BATIMENT, Première année, Semestre 1
2. Ajouter planification:
   - Matière: Choisir une matière disponible
   - Volume Total: 60h
   - CM: 20h, TD: 25h, TP: 15h
   - Coefficient: 2
   - ECTS: 4
3. Cliquer "Ajouter la Planification"

# Vérifications:
✅ Planification créée et visible dans la liste
✅ Statistiques mises à jour
✅ Validation de la cohérence (CM+TD+TP = Total)
```

### **Phase 2: Test du Système Enseignant**

#### Test 2.1: Accès à l'interface enseignants
```bash
# Naviguer vers la gestion des enseignants
URL: http://localhost:8000/esbtp/enseignants

# Vérifications:
✅ Interface moderne affichée
✅ Message de transition visible
✅ Fonctionnalités listées
✅ Liens vers planning général fonctionnels
```

#### Test 2.2: Base de données enseignants
```bash
# Vérifier les tables créées
Tables à vérifier:
- esbtp_enseignant_profiles
- esbtp_enseignant_disponibilites  
- esbtp_enseignant_affectations

# Commande pour vérifier:
php artisan tinker
>> Schema::hasTable('esbtp_enseignant_profiles')
>> Schema::hasTable('esbtp_enseignant_disponibilites')
>> Schema::hasTable('esbtp_enseignant_affectations')
```

### **Phase 3: Tests d'intégration**

#### Test 3.1: Navigation entre modules
```bash
# Test du flow de navigation:
1. Planning Général → Gestion Enseignants → Retour Planning
2. Interface test → Vue principale → Interface test
3. Vérifier tous les liens de navigation

# Vérifications:
✅ Navigation fluide entre modules
✅ Boutons et liens fonctionnels
✅ Cohérence de l'interface
```

#### Test 3.2: Données de test existantes
```bash
# Vérifier les planifications créées:
URL: http://localhost:8000/esbtp/planning-general/test
Sélectionner: 2025-2026, BTS1 BATIMENT, Première année, Semestre 1

# Doit afficher:
✅ 4 planifications existantes (créées automatiquement)
✅ Statistiques cohérentes
✅ Possibilité d'ajouter de nouvelles planifications
```

---

## 🏗️ Structure Technique

### **Modèles Créés**
- `ESBTPPlanificationAcademique`: Planifications académiques
- `ESBTPEnseignantProfile`: Profils enseignants détaillés
- `ESBTPEnseignantDisponibilite`: Disponibilités horaires
- `ESBTPEnseignantAffectation`: Affectations et historique

### **Contrôleurs**
- `ESBTPPlanningGeneralController`: Gestion du planning
- `ESBTPEnseignantProfileController`: Gestion des enseignants

### **Vues Principales**
- `planning-general/index.blade.php`: Vue principale
- `planning-general/index-test.blade.php`: Interface planification
- `enseignants/index.blade.php`: Gestion enseignants

### **Routes Ajoutées**
```php
// Planning Général
/esbtp/planning-general
/esbtp/planning-general/test
/esbtp/planning-general/store-planification
/esbtp/planning-general/destroy-planification/{id}
/esbtp/planning-general/valider-planification/{id}

// Enseignants (nouveau système)
/esbtp/enseignants
/esbtp/enseignants/dashboard
/esbtp/enseignants/create
/esbtp/enseignants/{id}
/esbtp/enseignants/{id}/edit
/esbtp/enseignants/{id}/valider
/esbtp/enseignants/{id}/affecter
/esbtp/enseignants/{id}/disponibilites
```

---

## 📊 Données de Test Disponibles

### **Planifications Académiques**
```sql
-- 4 planifications créées pour BTS1 BATIMENT, Première année, Semestre 1
SELECT * FROM esbtp_planifications_academiques;
```

### **Années Universitaires**
- 2025-2026 (année actuelle)
- Autres années disponibles (2020-2041)

### **Filières Disponibles**
- BTS1 Tronc commun, BTS1 BATIMENT, BTS1 GTP, etc.
- BTS2 Tronc commun, BTS2 BAT, BTS2 GTP, etc.

### **Niveaux d'Étude**
- Première année BTS (Année 1)
- Deuxième année BTS (Année 2)

---

## 🔧 Commandes Utiles

### **Vérifier les Migrations**
```bash
php artisan migrate:status
```

### **Créer des Données de Test**
```bash
php artisan tinker
# Code disponible dans les fichiers de contrôleur
```

### **Vérifier les Routes**
```bash
php artisan route:list | grep -E "(planning|enseignant)"
```

---

## 🚨 Points d'Attention

### **Prérequis**
- ✅ Base de données configurée
- ✅ Migrations exécutées
- ✅ Authentification fonctionnelle
- ✅ Permissions utilisateur configurées

### **Limitations Actuelles**
- Interface enseignants en mode "construction" (fonctionnel mais interface simplifiée)
- Ancien système enseignant encore présent (sera nettoyé)
- Vues complètes à finaliser pour les CRUD enseignants

### **Prochaines Étapes**
1. Finaliser les vues CRUD enseignants
2. Nettoyer l'ancien système
3. Intégrer génération automatique emplois du temps
4. Tests utilisateurs finaux

---

## ✅ Checklist de Test Complet

### **Tests Fonctionnels**
- [ ] Accès aux interfaces principales
- [ ] Navigation entre modules
- [ ] Création de planifications
- [ ] Validation des données
- [ ] Affichage des statistiques
- [ ] Responsivité mobile

### **Tests Techniques**
- [ ] Migrations appliquées
- [ ] Relations base de données
- [ ] Performance des requêtes
- [ ] Gestion des erreurs
- [ ] Sécurité des routes

### **Tests Utilisateur**
- [ ] Ergonomie générale
- [ ] Intuitivité des interfaces
- [ ] Feedback utilisateur
- [ ] Messages d'erreur clairs

---

## 📞 Support

Pour toute question ou problème:
1. Vérifier cette documentation
2. Consulter les logs Laravel (`storage/logs/`)
3. Utiliser les outils de développement navigateur
4. Vérifier la base de données directement

**Dernière mise à jour**: 18 Juillet 2025
**Version**: 2.0.0 (Refonte complète)