# Documentation des Filtres KLASSCI SAAS

## Vue d'ensemble

Cette documentation décrit l'implémentation des filtres avancés pour les modules **Classes** et **Réinscriptions** du système KLASSCI SAAS.

## 1. Filtres pour esbtp/classes/index

### Filtres disponibles

#### 🔍 Recherche générale
- **Champ** : `search`
- **Fonctionnalité** : Recherche textuelle dans le nom et le code de classe
- **Format** : Saisie libre (LIKE %text%)

#### 🎓 Filière
- **Champ** : `filiere_id`
- **Fonctionnalité** : Filter par filière spécifique
- **Options** : Liste dynamique des filières actives

#### 📚 Niveau d'étude
- **Champ** : `niveau_id` 
- **Fonctionnalité** : Filtrer par niveau académique
- **Options** : Liste dynamique des niveaux actifs

#### 📅 Année universitaire
- **Champ** : `annee_id`
- **Fonctionnalité** : Filtrer par année universitaire
- **Options** : Toutes les années actives (année courante marquée)

#### ✅ Statut de classe
- **Champ** : `statut`
- **Options** :
  - `active` : Classes actives uniquement
  - `inactive` : Classes inactives uniquement
  - Vide : Toutes les classes

#### 🪑 Capacité
- **Champ** : `capacite`
- **Options** :
  - `disponible` : Classes avec places disponibles
  - `pleine` : Classes à capacité maximale
  - Vide : Toutes les classes

### Implémentation technique

```php
// Contrôleur : ESBTPClasseController@index(Request $request)
$query = ESBTPClasse::with(['filiere', 'niveau', 'annee']);

// Filtres conditionnels
if ($request->filled('filiere_id')) {
    $query->where('filiere_id', $request->filiere_id);
}

// Filtre capacité avec sous-requête
if ($request->capacite === 'disponible') {
    $query->whereRaw('places_totales > (SELECT COUNT(*) FROM esbtp_inscriptions WHERE esbtp_inscriptions.classe_id = esbtp_classes.id AND esbtp_inscriptions.status != "annulée")');
}
```

## 2. Filtres pour esbtp/reinscriptions/index

### Filtres disponibles

#### 🔍 Recherche étudiants
- **Champ** : `search`
- **Fonctionnalité** : Recherche par nom, prénom ou matricule étudiant
- **Traitement** : Appliqué sur collections via `applyFiltersToEtudiants()`

#### 📅 Année universitaire
- **Champ** : `annee_id`
- **Fonctionnalité** : Filtrer les données de réinscription par année
- **Impact** : Modifie l'année d'analyse globale

#### 🎓 Filière
- **Champ** : `filiere_id`
- **Fonctionnalité** : Filtrer les étudiants par filière d'inscription
- **Traitement** : Via inscription active de l'étudiant

#### 📚 Niveau
- **Champ** : `niveau_id`
- **Fonctionnalité** : Filtrer les étudiants par niveau d'études
- **Traitement** : Via inscription active de l'étudiant

#### 📊 Statut de réinscription
- **Champ** : `statut_reinscription`
- **Options** :
  - `passage` : Étudiants en passage
  - `rattrapage` : Étudiants en rattrapage  
  - `redoublement` : Étudiants redoublants
  - `abandon` : Étudiants en abandon
  - `valide` : Réinscriptions validées

#### 💰 Statut paiement
- **Champ** : `statut_paiement`
- **Options** :
  - `solde` : Comptes soldés (solde_restant ≤ 0)
  - `impaye` : Comptes impayés (solde_restant > 0)
- **Calcul** : Utilise `calculerSoldeEtudiant()` si nécessaire

### Implémentation technique

```php
// Contrôleur : ESBTPReinscriptionController@loadCategory()
private function applyFiltersToEtudiants($etudiants, Request $request)
{
    // Filtre par recherche textuelle
    if ($request->filled('search')) {
        $search = strtolower($request->search);
        $etudiants = $etudiants->filter(function($item) use ($search) {
            $etudiant = is_array($item) && isset($item['etudiant']) ? $item['etudiant'] : $item;
            $nom = strtolower($etudiant->nom ?? '');
            $prenoms = strtolower($etudiant->prenoms ?? '');
            $matricule = strtolower($etudiant->matricule ?? '');
            
            return str_contains($nom, $search) || 
                   str_contains($prenoms, $search) || 
                   str_contains($matricule, $search);
        });
    }
    
    // Filtre paiement avec calcul dynamique
    if ($request->filled('statut_paiement')) {
        $etudiants = $etudiants->filter(function($item) use ($request) {
            $etudiant = is_array($item) && isset($item['etudiant']) ? $item['etudiant'] : $item;
            
            if (!isset($etudiant->solde_restant)) {
                $this->calculerSoldeEtudiant($item);
            }
            
            if ($request->statut_paiement === 'solde') {
                return $etudiant->solde_restant <= 0;
            } elseif ($request->statut_paiement === 'impaye') {
                return $etudiant->solde_restant > 0;
            }
            
            return true;
        });
    }
    
    return $etudiants;
}
```

## 3. Design et UX

### Grille responsive
```css
/* Grille adaptive pour les filtres */
display: grid; 
grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
gap: var(--space-md);
```

### Indicateur de résultats
- **Classes** : `{{ $classes->count() }} classe(s) trouvée(s)`
- **Réinscriptions** : `Année: {{ $anneeAcademique }}`

### Actions de contrôle
- **Bouton Filtrer** : Soumet le formulaire GET
- **Bouton Réinitialiser** : Lien vers route sans paramètres
- **Persistance des valeurs** : `{{ request('param') }}` dans les champs

## 4. Sécurité et Performance

### Validation des entrées
- Tous les filtres utilisent `$request->filled()` pour éviter les valeurs vides
- Les IDs sont validés via `exists:table,id` dans les relations

### Optimisation des requêtes
- **Classes** : Eager loading avec `with(['filiere', 'niveau', 'annee'])`
- **Réinscriptions** : Filtres appliqués sur collections pour éviter N+1
- Pagination manuelle pour contrôler la charge

### Gestion des erreurs
- Try/catch sur les méthodes de service
- Fallback sur collections vides en cas d'erreur
- Messages d'erreur utilisateur informatifs

## 5. Extensions futures

### Filtres additionnels possibles
- **Classes** : Filtrer par professeur assigné, matières disponibles
- **Réinscriptions** : Filtrer par date de validation, responsable validation
- **Export** : Filtres appliqués aux exports Excel/PDF

### Amélirations UX
- Autocomplete sur les recherches textuelles
- Filtres avec compteurs dynamiques  
- Sauvegarde des préférences de filtrage utilisateur
- Filtres rapides prédéfinis

## 6. Tests et Validation

### Cas de test recommandés
1. **Filtres multiples** : Combinaison de plusieurs filtres simultanément
2. **Performance** : Test avec grandes quantités de données
3. **Edge cases** : Filtres avec résultats vides, caractères spéciaux
4. **Responsive** : Tests sur mobile et tablette
5. **Persistance** : Vérification de la conservation des filtres lors de navigation

---

**Date de création** : 2025-01-16  
**Version** : 1.0  
**Auteur** : Claude Code Assistant  
**Système** : KLASSCI SAAS Educational Management