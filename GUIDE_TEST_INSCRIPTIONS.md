# 🧪 Guide de Test - Améliorations Inscriptions Admin

## ⚡ Tests Rapides à Effectuer

### 1️⃣ Test du Design des Modals

#### Objectif
Vérifier que tous les modals ont le nouveau style KLASSCI avec header coloré, shadow et délimitation.

#### Étapes
1. Aller sur `/esbtp/inscriptions-administration?annee=4`
2. Cliquer sur le bouton **"Associer un paiement"** (icône portefeuille) pour une inscription
3. **✅ Vérifier** :
   - Header avec gradient bleu/violet (#6366f1)
   - Shadow prononcée autour du modal
   - Bordure visible de 2px
   - Coins arrondis (16px)
   - Icône et texte bien alignés dans le header
   - Bouton de fermeture (X) en blanc

4. Répéter pour les autres modals :
   - "Valider un paiement" (pour une inscription avec paiement en attente)
   - "Changer la classe" (pour une inscription avec classe pleine)
   - "Validation groupée" (sélectionner plusieurs inscriptions)
   - "Annuler l'inscription"

---

### 2️⃣ Test du Modal "Valider un Paiement"

#### Problème Résolu
Le modal ne s'affichait pas à cause de styles inline qui écrasaient Bootstrap.

#### Étapes
1. Identifier une inscription avec un **paiement en attente** (badge jaune "⏰ En attente")
2. Cliquer sur le bouton **"Valider paiement"** dans la colonne Actions
3. **✅ Vérifier** :
   - Le modal s'ouvre correctement
   - Les informations du paiement sont affichées (montant, mode, référence)
   - Le header est en gradient KLASSCI
   - Cliquer sur "Valider le paiement"
   - **PAS DE RECHARGEMENT DE PAGE**
   - La ligne se met à jour en AJAX avec animation
   - Le badge passe à "✅ Payé" en vert

---

### 3️⃣ Test "Classe Pleine - Option Changer de Classe"

#### Objectif
Tester le changement de classe en AJAX sans refresh de page.

#### Étapes
1. Identifier une inscription avec le badge rouge **"⚠ Classe pleine"**
2. Dans la colonne "Actions", cliquer sur **"Changer classe"**
3. **✅ Vérifier** :
   - Le modal s'ouvre avec la classe actuelle affichée
   - La liste déroulante contient les classes alternatives
   - Les classes disponibles montrent le nombre de places (ex: "L1 INFO A (5/30 places)")
   - Les classes pleines sont en rouge et marquées "COMPLET"
4. Sélectionner une classe avec places disponibles
5. **✅ Vérifier** :
   - Un message vert s'affiche : "✓ Places disponibles: X"
6. Cliquer sur "Changer la classe"
7. **✅ Vérifier** :
   - Le modal se ferme
   - **PAS DE RECHARGEMENT DE PAGE**
   - La ligne se met à jour en AJAX
   - La colonne "Classe" affiche la nouvelle classe
   - Animation de highlight sur la ligne

---

### 4️⃣ Test "Classe Pleine - Option Forcer la Validation"

#### Objectif
Tester la validation forcée qui contourne la limite de classe.

#### Prérequis
Être connecté en tant que **superAdmin** ou **secretaire**.

#### Étapes
1. Identifier une inscription avec :
   - Badge rouge **"⚠ Classe pleine"**
   - Paiement validé (badge vert)
2. Dans la section problème, cliquer sur **"🗲 Forcer validation"**
3. **✅ Vérifier** :
   - Une confirmation s'affiche : "Êtes-vous sûr de vouloir forcer la validation ?"
4. Cliquer sur "OK"
5. **✅ Vérifier** :
   - **PAS DE RECHARGEMENT DE PAGE**
   - La ligne se met à jour en AJAX
   - Le statut passe à "Validée" (badge vert)
   - Le badge "Classe pleine" disparaît
   - Animation de succès

#### ⚠️ Note Importante
Cette action dépasse la limite de la classe. Utiliser avec précaution !

---

### 5️⃣ Test "Validation Groupée avec Classes Pleines"

#### Objectif
Vérifier que le modal de validation groupée affiche correctement les options pour les classes pleines.

#### Étapes
1. Cocher plusieurs inscriptions incluant :
   - Au moins 1 avec **classe pleine**
   - Au moins 1 avec **paiement en attente**
   - Au moins 1 **sans paiement**
2. Cliquer sur **"✓✓ Valider la sélection"** (en haut)
3. **✅ Vérifier que le modal affiche 3 sections** :

   **Section 1: Sans paiement**
   - Liste des inscriptions sans paiement
   - Bouton "Créer paiement" pour chaque

   **Section 2: Paiement en attente**
   - Liste des inscriptions avec paiement à valider
   - Boutons "Valider paiement" et "Voir dossier"

   **Section 3: Classes pleines** ⭐ NOUVELLE SECTION
   - Liste des inscriptions avec classe pleine
   - Pour chaque inscription : 2 boutons
     - 🔄 **"Changer classe"** (bleu)
     - ⚡ **"Forcer"** (rouge)

4. Tester chaque action :

   **Action "Changer classe"** :
   - Cliquer sur le bouton bleu
   - Le modal groupé se ferme
   - Le modal "Changer classe" s'ouvre
   - Suivre le processus de changement

   **Action "Forcer"** :
   - Cliquer sur le bouton rouge
   - Confirmation demandée
   - Le modal groupé se ferme
   - La validation forcée s'exécute en AJAX
   - La ligne se met à jour

---

### 6️⃣ Test AJAX Général (Pas de Refresh)

#### Objectif
S'assurer qu'AUCUNE action ne recharge la page complète.

#### Actions à Tester
Pour chaque action ci-dessous, **VÉRIFIER QU'IL N'Y A PAS DE RECHARGEMENT DE PAGE** :

- ✅ Créer un paiement via modal
- ✅ Valider un paiement via modal
- ✅ Changer la classe d'une inscription
- ✅ Forcer la validation d'une inscription
- ✅ Valider une inscription individuelle
- ✅ Annuler une inscription

#### Comment Vérifier ?
1. Ouvrir la console du navigateur (F12)
2. Aller dans l'onglet "Network"
3. Effectuer l'action
4. **✅ Vérifier** :
   - Pas de requête vers `inscriptions-administration` (full page reload)
   - Seulement des requêtes AJAX (XHR)
   - La ligne se met à jour visuellement sans "flash" de la page

---

### 7️⃣ Test des Animations

#### Objectif
Vérifier que les animations de feedback visuel fonctionnent.

#### Étapes
1. Effectuer n'importe quelle action AJAX (valider, changer classe, etc.)
2. **✅ Vérifier** :
   - Pendant le traitement :
     - Les boutons d'action disparaissent
     - Un spinner apparaît à leur place
   - Après le succès :
     - La ligne "s'illumine" brièvement (highlight vert)
     - L'animation dure ~1 seconde
     - Les nouvelles données s'affichent
   - En cas d'échec :
     - La ligne s'illumine en rouge
     - Un message d'erreur s'affiche

---

## 🐛 Problèmes Connus et Solutions

### Problème 1 : Le modal ne s'ouvre pas
**Cause** : Bootstrap n'est pas chargé  
**Solution** : Vérifier la console, recharger la page

### Problème 2 : "debugError is not defined"
**Cause** : Ancienne version du code  
**Solution** : La fonction a été ajoutée, vérifier que le fichier est à jour

### Problème 3 : L'animation ne se déclenche pas
**Cause** : CSS non chargé  
**Solution** : Vider le cache et recharger (Ctrl+Shift+R)

### Problème 4 : Les classes alternatives ne s'affichent pas
**Cause** : Problème avec la route backend  
**Solution** : Vérifier la console (F12), regarder l'erreur retournée

---

## 📊 Checklist Complète de Test

```
[ ] 1. Design des modals (header KLASSCI, shadow, bordure)
[ ] 2. Modal "Associer un paiement" s'ouvre correctement
[ ] 3. Modal "Valider un paiement" s'ouvre et valide en AJAX
[ ] 4. Modal "Changer classe" s'ouvre et liste les classes alternatives
[ ] 5. Changement de classe fonctionne en AJAX
[ ] 6. Bouton "Forcer validation" visible (superAdmin/secretaire uniquement)
[ ] 7. Validation forcée contourne la limite de classe
[ ] 8. Modal validation groupée affiche la section "Classes pleines"
[ ] 9. Boutons "Changer classe" et "Forcer" dans validation groupée
[ ] 10. Aucune action ne recharge la page complète
[ ] 11. Animations de highlight fonctionnent (vert = succès, rouge = erreur)
[ ] 12. Spinners de chargement s'affichent pendant les requêtes AJAX
[ ] 13. Messages d'erreur s'affichent en cas de problème
[ ] 14. Les données se mettent à jour en temps réel
[ ] 15. Console JavaScript sans erreurs
```

---

## 🎯 Scénarios de Test Complets

### Scénario A : Inscription Sans Paiement
```
1. Trouver une inscription sans paiement (badge rouge "✗ Non payé")
2. Cliquer sur "Valider" → Modal "Créer paiement" s'ouvre
3. Remplir le formulaire de paiement
4. Cocher "Valider le paiement immédiatement"
5. Cocher "Valider l'inscription après le paiement"
6. Soumettre
7. ✅ Résultat attendu :
   - Paiement créé et validé
   - Inscription validée
   - Ligne mise à jour en AJAX
   - Badge passe à "Validée"
   - Pas de rechargement de page
```

### Scénario B : Classe Pleine - Changement
```
1. Trouver une inscription avec "Classe pleine"
2. Cliquer sur "Changer classe"
3. Sélectionner une classe avec places disponibles
4. Soumettre
5. ✅ Résultat attendu :
   - Classe changée
   - Ligne mise à jour en AJAX
   - Badge "Classe pleine" disparaît
   - Nouvelle classe affichée
```

### Scénario C : Validation Groupée Mixte
```
1. Sélectionner 5 inscriptions :
   - 2 sans paiement
   - 1 avec paiement en attente
   - 2 avec classe pleine
2. Cliquer sur "Valider la sélection"
3. Le modal affiche 3 sections avec les 5 inscriptions
4. Traiter chaque problème individuellement
5. ✅ Résultat attendu :
   - Chaque action se fait en AJAX
   - Les lignes se mettent à jour au fur et à mesure
   - Pas de rechargement de page
```

---

## 🎨 Aperçu Visuel Attendu

### Nouveau Header des Modals
```
┌─────────────────────────────────────────────┐
│ [Gradient Bleu-Violet #6366f1 → #8b5cf6]   │
│                                              │
│  💳 Associer un paiement à l'inscription [X]│
│                                              │
└─────────────────────────────────────────────┘
```

### Section "Classes Pleines" dans Validation Groupée
```
⚠️ Classes pleines
Les inscriptions suivantes ont une classe pleine :

┌──────────────────────────────────────────────┐
│ 👤 KOUADIO Yao (MAT12345)                    │
│     [🔄 Changer classe]  [⚡ Forcer]         │
└──────────────────────────────────────────────┘
```

---

## 📞 En Cas de Problème

1. **Ouvrir la console** (F12)
2. **Regarder l'onglet "Console"** pour les erreurs JavaScript
3. **Regarder l'onglet "Network"** pour les requêtes AJAX échouées
4. **Copier le message d'erreur** et le transmettre à l'équipe dev

---

**Bonne chance pour les tests ! 🚀**