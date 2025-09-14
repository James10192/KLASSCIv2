# Filtrage par Année Universitaire - Dashboard

## Modifications apportées

### 1. ✅ Filtrage par année universitaire en cours dans les dashboards
**Problème** : Les dashboards affichaient toutes les données sans filtrer par année universitaire, rendant les statistiques peu pertinentes pour l'année en cours.

**Solution** :
- **Dashboard Coordinateur** : Ajout du filtrage par `annee_universitaire_id` via `ESBTPAnneeUniversitaire::where('is_current', true)`
- **Dashboard SuperAdmin** : Même logique de filtrage appliquée
- **Graphiques** : Mise à jour des graphiques pour afficher uniquement les données de l'année en cours

### 2. ✅ Correction du filtrage des classes
**Problème** : Les classes étaient filtrées par année universitaire alors qu'elles existent indépendamment.

**Solution** :
- Retrait du filtrage par `annee_universitaire_id` pour le comptage des classes
- Maintien du filtrage via les inscriptions pour les étudiants

### 3. ✅ Correction des espacements KPI
**Problème** : Espacement insuffisant entre icône, nombre et titre dans les cartes KPI de la page personnel unified.

**Solution** :
```css
.stat-value {
    line-height: 1.2;           /* Ajout pour meilleur espacement */
}

.stat-label {
    margin-top: var(--space-xs); /* Ajout d'espace au-dessus */
}
```

## Logique de filtrage appliquée

### Dashboard Coordinateur
- **Étudiants** : Filtrés via `ESBTPInscription` avec `annee_universitaire_id` et `status = 'active'`
- **Classes** : Pas de filtrage par année (global)
- **Évaluations** : Filtrées via la relation `classe.annee_universitaire_id`
- **Emplois du temps** : Filtrés directement par `annee_universitaire_id`
- **Présences** : Filtrées via `etudiant.inscriptions` avec année en cours et statut actif
- **Inscriptions récentes** : Filtrées par `annee_universitaire_id`

### Dashboard SuperAdmin
- Même logique que coordinateur
- **Graphiques supplémentaires** :
  - **Répartition par filière** : Filtrée via `inscriptions.annee_universitaire_id`
  - **Évolution mensuelle** : Basée sur les inscriptions de l'année en cours
  - **Inscriptions par mois** : Filtrées par année universitaire courante

### Données non filtrées (restent globales)
- **Filières** : Comptage global (structural)
- **Classes** : Comptage global (structural)
- **Matières** : Comptage global (structural)
- **Enseignants** : Comptage global (structural)

## Fallback système
En cas d'absence d'année universitaire marquée comme `is_current = true`, le système utilise les données globales pour éviter l'affichage de zéros.

## Fichiers modifiés
- `app/Http/Controllers/DashboardController.php` : Ajout filtrage année universitaire
- `resources/views/esbtp/personnel/unified-index.blade.php` : Correction espacements KPI

## Impact utilisateur
- **Pertinence des données** : Les KPI reflètent maintenant l'année académique en cours
- **Graphiques cohérents** : Les visualisations sont alignées sur la période active
- **Interface améliorée** : Meilleur espacement dans les cartes statistiques

---
*Modifications appliquées le {{ date('Y-m-d H:i:s') }}*