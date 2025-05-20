# Documentation Architecture - ESBTP Système de Suivi des Présences

## Vue d'ensemble

Le système de suivi des présences ESBTP est une application web construite avec Laravel, utilisant une architecture MVC avec des composants supplémentaires pour la gestion hors ligne et la synchronisation.

## Architecture Globale

```mermaid
graph TB
    Client[Client Web/Mobile]
    API[API REST]
    Web[Interface Web]
    Cache[Cache Redis]
    DB[(Base de données MySQL)]
    Queue[File d'attente]
    Worker[Worker]
    Storage[Stockage]

    Client --> API
    Client --> Web
    API --> Cache
    Web --> Cache
    API --> DB
    Web --> DB
    API --> Queue
    Queue --> Worker
    Worker --> DB
    Worker --> Storage
```

## Composants Principaux

### 1. Frontend

#### Interface Web

-   Framework : Laravel Blade + Bootstrap
-   JavaScript : Vue.js pour les composants dynamiques
-   Service Worker pour le mode hors ligne
-   IndexedDB pour le stockage local

#### Application Mobile

-   PWA (Progressive Web App)
-   Responsive Design
-   Gestion du mode hors ligne

### 2. Backend

#### API REST

-   Laravel API Resources
-   JWT Authentication
-   Rate Limiting
-   Validation des requêtes

#### Base de Données

-   MySQL pour le stockage principal
-   Redis pour le cache
-   Migrations Laravel

#### File d'Attente

-   Laravel Queue
-   Redis comme driver
-   Gestion des tâches asynchrones

## Modules Fonctionnels

### 1. Module d'Authentification

```mermaid
sequenceDiagram
    participant U as Utilisateur
    participant A as Auth Controller
    participant J as JWT Service
    participant D as Database

    U->>A: Login Request
    A->>D: Verify Credentials
    D-->>A: User Data
    A->>J: Generate Token
    J-->>A: JWT Token
    A-->>U: Token + User Info
```

### 2. Module de Présence

```mermaid
sequenceDiagram
    participant T as Teacher
    participant C as Code Controller
    participant S as Student
    participant A as Attendance Controller
    participant D as Database

    T->>C: Generate Code
    C->>D: Save Code
    D-->>C: Code Info
    C-->>T: Display Code
    S->>A: Submit Code
    A->>D: Verify Code
    D-->>A: Validation Result
    A->>D: Mark Attendance
    A-->>S: Confirmation
```

### 3. Module Hors Ligne

```mermaid
sequenceDiagram
    participant C as Client
    participant SW as Service Worker
    participant IDB as IndexedDB
    participant S as Sync Controller
    participant D as Database

    C->>SW: Offline Request
    SW->>IDB: Store Data
    IDB-->>SW: Confirmation
    SW-->>C: Success
    C->>S: Sync Request
    S->>IDB: Get Stored Data
    S->>D: Sync Data
    D-->>S: Sync Result
    S-->>C: Sync Complete
```

## Sécurité

### Authentification

-   JWT pour l'API
-   Sessions Laravel pour l'interface web
-   Middleware d'authentification
-   Protection CSRF

### Autorisation

-   Rôles et permissions
-   Middleware d'autorisation
-   Validation des accès

### Protection des Données

-   Encryption des données sensibles
-   Validation des entrées
-   Protection XSS
-   Rate limiting

## Performance

### Mise en Cache

-   Cache de configuration
-   Cache de requêtes
-   Cache de vues

### Optimisation

-   Compression des assets
-   Minification du code
-   Lazy loading des images
-   Pagination des résultats

## Déploiement

### Infrastructure

-   Serveur web : Nginx
-   PHP-FPM
-   MySQL
-   Redis
-   Supervisord pour les workers

### Environnements

-   Développement
-   Test
-   Production

## Monitoring

### Logs

-   Laravel Log
-   Error tracking
-   Audit trail

### Métriques

-   Temps de réponse
-   Utilisation des ressources
-   Taux d'erreur

## Maintenance

### Sauvegardes

-   Base de données
-   Fichiers uploadés
-   Configuration

### Mises à jour

-   Procédure de déploiement
-   Rollback
-   Tests automatisés
