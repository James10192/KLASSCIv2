# Documentation API - ESBTP Système de Suivi des Présences

## Vue d'ensemble

Cette documentation détaille les endpoints API disponibles dans le système de suivi des présences ESBTP. L'API suit les principes REST et utilise JSON pour les échanges de données.

## Base URL

```
http://votre-domaine.com/api/v1
```

## Authentification

L'API utilise JWT (JSON Web Tokens) pour l'authentification. Incluez le token dans le header de chaque requête :

```
Authorization: Bearer <votre_token>
```

## Endpoints

### Authentification

#### Login

```http
POST /auth/login
```

**Corps de la requête :**

```json
{
    "email": "utilisateur@esbtp.com",
    "password": "mot_de_passe"
}
```

**Réponse :**

```json
{
    "token": "jwt_token",
    "user": {
        "id": 1,
        "name": "Nom Utilisateur",
        "role": "teacher"
    }
}
```

### Gestion des Présences

#### Générer un Code de Présence

```http
POST /attendance/generate-code
```

**Corps de la requête :**

```json
{
    "course_id": 123,
    "duration": 15
}
```

**Réponse :**

```json
{
    "code": "ABC123",
    "expires_at": "2024-03-21T10:15:00Z"
}
```

#### Marquer une Présence

```http
POST /attendance/mark
```

**Corps de la requête :**

```json
{
    "code": "ABC123",
    "student_id": 456
}
```

**Réponse :**

```json
{
    "status": "success",
    "attendance_id": 789
}
```

### Rapports

#### Obtenir les Statistiques de Présence

```http
GET /reports/attendance-stats
```

**Paramètres :**

```
start_date: 2024-03-01
end_date: 2024-03-31
course_id: 123 (optionnel)
```

**Réponse :**

```json
{
    "total_sessions": 20,
    "attendance_rate": 95.5,
    "details": [
        {
            "date": "2024-03-01",
            "present": 25,
            "absent": 2
        }
    ]
}
```

### Mode Hors Ligne

#### Synchroniser les Données

```http
POST /sync/attendance
```

**Corps de la requête :**

```json
{
    "offline_records": [
        {
            "code": "ABC123",
            "student_id": 456,
            "timestamp": "2024-03-21T09:00:00Z"
        }
    ]
}
```

**Réponse :**

```json
{
    "synced": 1,
    "conflicts": 0
}
```

## Codes d'Erreur

| Code | Description           |
| ---- | --------------------- |
| 400  | Requête invalide      |
| 401  | Non authentifié       |
| 403  | Non autorisé          |
| 404  | Ressource non trouvée |
| 409  | Conflit               |
| 500  | Erreur serveur        |

## Limites de Taux

-   100 requêtes par minute par IP
-   1000 requêtes par heure par utilisateur authentifié

## Versioning

L'API utilise le versioning dans l'URL. La version actuelle est v1.

## Bonnes Pratiques

1. Utilisez HTTPS pour toutes les requêtes
2. Mettez en cache les réponses quand c'est possible
3. Implémentez une gestion des erreurs robuste
4. Suivez les limites de taux

## Support

Pour toute assistance technique :

-   Email : api-support@esbtp.com
-   Documentation complète : [Lien](../README.md)
-   Environnement de test : `http://api-test.esbtp.com`
