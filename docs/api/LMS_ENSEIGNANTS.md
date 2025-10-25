# 📚 API LMS - Documentation Endpoint Enseignants

## Vue d'ensemble

Endpoint API permettant de récupérer la liste des enseignants avec leurs classes, matières enseignées et volume horaire.

**Base URL**: `http://domain/api/lms`

**Authentification**: Bearer Token (Laravel Sanctum)

---

## Authentification

### Générer un token

```php
// Via Laravel Tinker
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $token = $user->createToken('lms-access', ['lms:access'])->plainTextToken;
>>> echo $token;
```

### Utiliser le token

```bash
curl -H "Authorization: Bearer {votre_token}" http://domain/api/lms/enseignants
```

---

## Endpoint: Liste des Enseignants

### Requête de base (format simple)

**GET** `/api/lms/enseignants`

Retourne la liste simple des enseignants (format compatible avec l'ancienne API).

**Exemple:**
```bash
curl -X GET "http://localhost:8000/api/lms/enseignants" \
  -H "Authorization: Bearer 1|abc123..."
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1634,
      "teacher_id": 1,
      "nom": "KOUASSI Jean",
      "email": "kouassi.jean@esbtp.ci",
      "role": "etudiant",
      "matricule": "ENS1634",
      "specialization": "Mathématiques et Physique",
      "status": "permanent"
    }
  ]
}
```

---

### Requête enrichie (avec détails)

**GET** `/api/lms/enseignants?with_details=true`

Retourne les enseignants avec leurs classes, matières et statistiques de volume horaire.

**Exemple:**
```bash
curl -X GET "http://localhost:8000/api/lms/enseignants?with_details=true" \
  -H "Authorization: Bearer 1|abc123..."
```

**Réponse:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1634,
      "teacher_id": 1,
      "nom": "KOUASSI Jean",
      "email": "kouassi.jean@esbtp.ci",
      "role": "etudiant",
      "matricule": "ENS1634",
      "specialization": "Mathématiques et Physique",
      "status": "permanent",
      "classes": [
        {
          "id": 15,
          "nom": "L3 GC - 2024/2025",
          "filiere": {
            "id": 1,
            "nom": "Génie Civil"
          },
          "niveau": {
            "id": 3,
            "nom": "Licence 3"
          }
        }
      ],
      "matieres": [
        {
          "id": 42,
          "nom": "Mathématiques Appliquées",
          "code": "MATH301",
          "heures_prevues": 40,
          "heures_effectuees": 28,
          "heures_restantes": 12,
          "taux_realisation": 70,
          "nb_seances_total": 20,
          "nb_seances_effectuees": 14,
          "classes": [
            {
              "id": 15,
              "nom": "L3 GC"
            }
          ],
          "seances": [
            {
              "id": 123,
              "date_seance": "2025-10-25",
              "heure_debut": "08:00:00",
              "heure_fin": "10:00:00",
              "classe": "L3 GC",
              "salle": "A101",
              "status": "effectuee"
            }
          ]
        }
      ],
      "statistiques": {
        "total_classes": 3,
        "total_matieres": 5,
        "total_heures_prevues": 120,
        "total_heures_effectuees": 85,
        "total_heures_restantes": 35,
        "taux_realisation_global": 70.83,
        "nb_seances_total": 60,
        "nb_seances_effectuees": 42
      }
    }
  ]
}
```

---

## Paramètres disponibles

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `with_details` | boolean | Activer format enrichi avec classes/matières/stats | `?with_details=true` |
| `filiere_id` | integer | Filtrer par filière | `?filiere_id=1` |
| `niveau_id` | integer | Filtrer par niveau d'études | `?niveau_id=3` |
| `classe_id` | integer | Filtrer par classe spécifique | `?classe_id=15` |
| `matiere_id` | integer | Filtrer par matière | `?matiere_id=42` |

**Combinaison de filtres:**
```bash
# Enseignants qui enseignent Mathématiques en L3 Génie Civil
curl -X GET "http://localhost:8000/api/lms/enseignants?with_details=true&filiere_id=1&niveau_id=3&matiere_id=42" \
  -H "Authorization: Bearer 1|abc123..."
```

---

## Description des champs

### Format simple

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID du user (users.id) - pour compatibilité |
| `teacher_id` | integer | ID de l'enseignant (esbtp_teachers.id) - pour relations séances |
| `nom` | string | Nom complet de l'enseignant |
| `email` | string | Email de l'enseignant |
| `role` | string | Rôle de l'utilisateur |
| `matricule` | string | Matricule de l'enseignant |
| `specialization` | string | Spécialisation de l'enseignant |
| `status` | string | Statut (permanent/vacataire) |

### Format enrichi (champs supplémentaires)

#### `classes[]`
Liste des classes où l'enseignant a des séances (année universitaire courante uniquement).

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID de la classe |
| `nom` | string | Nom de la classe |
| `filiere.id` | integer | ID de la filière |
| `filiere.nom` | string | Nom de la filière |
| `niveau.id` | integer | ID du niveau d'études |
| `niveau.nom` | string | Nom du niveau |

#### `matieres[]`
Liste des matières enseignées avec détails de volume horaire.

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID de la matière |
| `nom` | string | Nom de la matière |
| `code` | string | Code de la matière |
| `heures_prevues` | float | Heures prévues (source: pivot > planning général > séances) |
| `heures_effectuees` | float | Heures effectivement réalisées (attendances present/late) |
| `heures_restantes` | float | Heures restantes à effectuer |
| `taux_realisation` | float | Pourcentage de réalisation (%) |
| `nb_seances_total` | integer | Nombre total de séances planifiées |
| `nb_seances_effectuees` | integer | Nombre de séances effectuées |
| `classes[]` | array | Classes où cette matière est enseignée |
| `seances[]` | array | Liste des séances (détails ci-dessous) |

#### `matieres[].seances[]`
Détails des séances de cours.

| Champ | Type | Description |
|-------|------|-------------|
| `id` | integer | ID de la séance |
| `date_seance` | date | Date de la séance (Y-m-d) |
| `heure_debut` | time | Heure de début (H:i:s) |
| `heure_fin` | time | Heure de fin (H:i:s) |
| `classe` | string | Nom de la classe |
| `salle` | string | Salle de cours |
| `status` | string | Statut (effectuee/a_venir/annulee) |

#### `statistiques`
Statistiques globales de l'enseignant.

| Champ | Type | Description |
|-------|------|-------------|
| `total_classes` | integer | Nombre total de classes enseignées |
| `total_matieres` | integer | Nombre total de matières enseignées |
| `total_heures_prevues` | float | Total des heures prévues |
| `total_heures_effectuees` | float | Total des heures effectuées |
| `total_heures_restantes` | float | Total des heures restantes |
| `taux_realisation_global` | float | Taux de réalisation moyen (%) |
| `nb_seances_total` | integer | Nombre total de séances |
| `nb_seances_effectuees` | integer | Nombre de séances effectuées |

---

## Architecture et Sources de Données

### 1. Classes
**Source**: Séances de cours (`esbtp_seance_cours`) avec emploi du temps de l'année courante

**Logique**:
- Une classe est listée si l'enseignant a au moins 1 séance dans cette classe
- Les séances doivent venir d'un emploi du temps avec `annee_universitaire_id` = année courante (`is_current=true`)
- Les séances passées ET futures sont comptabilisées

### 2. Matières
**Source DOUBLE** (priorité dans cet ordre):

1. **Pivot table** `esbtp_enseignant_matiere`:
   - Champ `heures_prevues` utilisé si disponible
   - Lien direct enseignant ↔ matière pour l'année courante

2. **Planning général** `esbtp_planifications_academiques` + `esbtp_planification_teachers`:
   - Champ `volume_horaire_total` utilisé si pivot vide
   - Permet d'avoir l'historique et les planifications futures

3. **Fallback séances**: Si aucune des 2 sources précédentes, calcul de la somme des durées de séances planifiées

**Pourquoi double source?**
> Permet d'avoir à la fois l'historique complet de l'enseignant par matière ET les séances passées/à venir, même si la planification n'est pas encore finalisée dans le système.

### 3. Volume Horaire

**Heures prévues**: Priorité → Pivot > Planning général > Somme durées séances

**Heures effectuées**: Calculé depuis `esbtp_teacher_attendances`
- Filtre: `status IN ('present', 'late')` ET `type = 'start'`
- Calcul: `nb_attendances × durée_moyenne_séance`
- Validation: Utilise le maximum entre calcul et données DB

**Heures restantes**: `heures_prevues - heures_effectuees`

**Taux réalisation**: `(heures_effectuees / heures_prevues) × 100`

---

## Cas d'usage

### 1. Dashboard LMS - Vue enseignant
```bash
# Récupérer TOUS les détails de l'enseignant connecté
curl -X GET "http://localhost:8000/api/lms/enseignants?with_details=true" \
  -H "Authorization: Bearer {teacher_token}"
```

**Utilisation**: Afficher les classes, matières et statistiques de l'enseignant dans son tableau de bord.

---

### 2. Planning - Enseignants d'une classe
```bash
# Récupérer les enseignants qui enseignent dans la classe L3 GC
curl -X GET "http://localhost:8000/api/lms/enseignants?with_details=true&classe_id=15" \
  -H "Authorization: Bearer {token}"
```

**Utilisation**: Afficher les enseignants d'une classe avec leurs matières et horaires.

---

### 3. Statistiques - Enseignants par filière/niveau
```bash
# Récupérer les enseignants de Génie Civil Licence 3
curl -X GET "http://localhost:8000/api/lms/enseignants?with_details=true&filiere_id=1&niveau_id=3" \
  -H "Authorization: Bearer {token}"
```

**Utilisation**: Analyse de la charge de travail par filière/niveau.

---

### 4. Suivi matière - Enseignants d'une matière
```bash
# Récupérer les enseignants qui enseignent Mathématiques
curl -X GET "http://localhost:8000/api/lms/enseignants?with_details=true&matiere_id=42" \
  -H "Authorization: Bearer {token}"
```

**Utilisation**: Coordination entre enseignants d'une même matière.

---

## Performance

**Format simple**: ~14ms (sans enrichissement)
**Format enrichi**: ~30ms (avec classes, matières, séances, statistiques)

**Optimisations appliquées**:
- Eager loading des relations (`with()`)
- Cache des calculs répétitifs
- Queries groupées pour statistiques
- Logs de performance détaillés

---

## Rétrocompatibilité

✅ **Aucun breaking change**: Le format simple (`without with_details`) retourne exactement le même format que l'ancienne API.

**Migration progressive**:
1. Anciens clients LMS continuent d'utiliser `GET /api/lms/enseignants` (format simple)
2. Nouveaux développements utilisent `GET /api/lms/enseignants?with_details=true` (format enrichi)
3. Pas de période de transition nécessaire

---

## Sécurité

**Authentification**: Requise via Bearer Token (Sanctum)

**Permissions**: Aucune permission spécifique requise, mais le token doit être valide

**Filtrage données**:
- Seules les données de l'année universitaire courante sont retournées
- Les enseignants inactifs (`is_active=false`) sont exclus
- Les enseignants supprimés (soft deleted) sont exclus

---

## Codes d'erreur

| Code | Message | Description |
|------|---------|-------------|
| 401 | Unauthenticated | Token manquant ou invalide |
| 404 | Not Found | Route introuvable |
| 500 | Internal Server Error | Erreur serveur (voir logs) |

---

## Support

**Logs**: Disponibles dans `storage/logs/laravel.log`

**Recherche logs**: `grep "LMS Enseignants API" storage/logs/laravel.log`

**Contact technique**: [Votre contact]

---

## Historique des modifications

### Version 2.0 - 25 octobre 2025

**Type** : Enhancement (backward compatible)

**Changements** :
- ✅ Ajout paramètre `?with_details=true` pour format enrichi
- ✅ Inclusion classes enseignées (via séances année courante)
- ✅ Inclusion matières avec dual-source (pivot + planning général)
- ✅ Calcul volume horaire complet (prévues/effectuées/restantes)
- ✅ Statistiques globales par enseignant
- ✅ Filtres avancés : filiere_id, niveau_id, classe_id, matiere_id
- ✅ Performance optimisée : 14ms (simple), 30ms (enrichi)

**Breaking changes** : Aucun (format simple inchangé)

**Migration** : Aucune action requise, opt-in via paramètre

---

*Dernière mise à jour: 25 octobre 2025*
