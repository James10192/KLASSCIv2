# 📚 Guide d'Intégration LMS-KLASSCI

## 🎯 Vue d'Ensemble

Ce guide explique comment intégrer votre LMS avec KLASSCI en utilisant les APIs REST créées. L'intégration permet un flux bidirectionnel de données entre le LMS et KLASSCI.

### **Flux de Données**
```
KLASSCI → LMS : Données en lecture seule (structure, étudiants, planning)
LMS → KLASSCI : Données d'écriture (notes, présences)
```

---

## 🔐 Authentification

### **1. Connexion Initial**

**Endpoint :** `POST /api/lms/auth/login`

```javascript
// Exemple de connexion depuis le LMS
const response = await fetch('https://klassci.school.com/api/lms/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        email: 'enseignant@school.com',
        password: 'password123'
    })
});

const data = await response.json();

if (data.success) {
    // Stocker le token pour les futures requêtes
    const token = data.data.token;
    const userData = data.data.user;

    localStorage.setItem('klassci_token', token);
    localStorage.setItem('klassci_user', JSON.stringify(userData));
}
```

**Réponse :**
```json
{
    "success": true,
    "data": {
        "token": "1|abc123...",
        "token_type": "Bearer",
        "user": {
            "id": 15,
            "nom": "Prof. Dupont",
            "email": "enseignant@school.com",
            "role": "enseignant",
            "enseignant_data": {
                "nb_matieres": 3,
                "nb_classes": 2,
                "matieres_principales": [...]
            }
        }
    },
    "meta": {
        "annee_universitaire_courante": {
            "id": 3,
            "nom": "2024-2025"
        }
    }
}
```

### **2. Utilisation du Token**

Pour toutes les requêtes suivantes, inclure le token dans l'en-tête :

```javascript
const token = localStorage.getItem('klassci_token');

const response = await fetch('/api/lms/matieres', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

---

## 📖 APIs de Lecture (LMS ← KLASSCI)

### **1. Structure Organisationnelle**

```javascript
// Récupérer la structure (filières, niveaux)
const structure = await fetch('/api/lms/structure', {
    headers: { 'Authorization': `Bearer ${token}` }
});
```

### **2. Matières et Cours**

```javascript
// Récupérer les matières de l'enseignant connecté
const matieres = await fetch('/api/lms/matieres', {
    headers: { 'Authorization': `Bearer ${token}` }
});

// Avec filtres optionnels
const matieresFiltered = await fetch('/api/lms/matieres?filiere_id=3&niveau_id=2', {
    headers: { 'Authorization': `Bearer ${token}` }
});
```

**Exemple de réponse :**
```json
{
    "success": true,
    "data": [
        {
            "id": 25,
            "nom": "Mathématiques Appliquées",
            "code": "MATH101",
            "couleur": "#FF5733",
            "heures": {
                "cm": 30,
                "td": 20,
                "tp": 10,
                "total": 60
            },
            "enseignants": [...],
            "classes": [...],
            "lms_metadata": {
                "has_online_courses": false,
                "total_evaluations": 3
            }
        }
    ]
}
```

### **3. Classes et Étudiants**

```javascript
// Récupérer les classes de l'année courante
const classes = await fetch('/api/lms/classes', {
    headers: { 'Authorization': `Bearer ${token}` }
});

// Récupérer les étudiants d'une classe
const etudiants = await fetch('/api/lms/classes/12/etudiants', {
    headers: { 'Authorization': `Bearer ${token}` }
});
```

### **4. Emploi du Temps**

```javascript
// Récupérer l'emploi du temps de la semaine courante
const planning = await fetch('/api/lms/emploi-temps', {
    headers: { 'Authorization': `Bearer ${token}` }
});

// Avec période spécifique
const planningPeriode = await fetch('/api/lms/emploi-temps?date_debut=2024-10-15&date_fin=2024-10-21', {
    headers: { 'Authorization': `Bearer ${token}` }
});
```

### **5. Évaluations Programmées**

```javascript
// Récupérer les évaluations programmées
const evaluations = await fetch('/api/lms/evaluations', {
    headers: { 'Authorization': `Bearer ${token}` }
});
```

---

## ✏️ APIs d'Écriture (LMS → KLASSCI)

### **1. Sauvegarder les Notes d'Évaluation**

```javascript
// Sauvegarder les notes après une évaluation en ligne
const saveNotes = async (evaluationId, notesData) => {
    const response = await fetch(`/api/lms/evaluations/${evaluationId}/notes`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            notes: [
                {
                    etudiant_id: 123,
                    note: 16.5,
                    is_absent: false,
                    commentaire: "Très bon travail"
                },
                {
                    etudiant_id: 124,
                    note: null,
                    is_absent: true,
                    commentaire: "Absent"
                }
            ],
            date_saisie: "2024-10-15",
            commentaire_general: "Évaluation en ligne réussie"
        })
    });

    return await response.json();
};
```

### **2. Enregistrer les Présences**

```javascript
// Enregistrer les présences après un cours en ligne
const saveAttendance = async (coursId, attendanceData) => {
    const response = await fetch(`/api/lms/cours/${coursId}/presences`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            date_cours: "2024-10-15",
            heure_debut: "14:00",
            heure_fin: "16:00",
            enseignant_present: true,
            etudiants_presents: [123, 125, 127],
            etudiants_absents: [124, 126],
            duree_effective_minutes: 120,
            commentaire: "Cours de révision",
            type_cours: "visio"
        })
    });

    return await response.json();
};
```

### **3. Mettre à Jour le Statut d'un Cours**

```javascript
// Marquer un cours comme "en cours" quand la visio démarre
const updateCourseStatus = async (coursId, statut) => {
    const response = await fetch(`/api/lms/cours/${coursId}/statut`, {
        method: 'PUT',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            statut: statut, // 'en_cours', 'realise', 'annule'
            commentaire: "Cours démarré via LMS",
            lien_visio: "https://zoom.us/j/123456789"
        })
    });

    return await response.json();
};
```

---

## 🔄 Workflows d'Intégration

### **Workflow 1 : Démarrage d'un Cours en Ligne**

```javascript
// 1. Récupérer l'emploi du temps
const planning = await fetch('/api/lms/emploi-temps');
const cours = planning.data.find(c => c.id === coursId);

// 2. Marquer le cours comme "en cours"
await updateCourseStatus(coursId, 'en_cours');

// 3. Démarrer la visioconférence dans le LMS
const visioUrl = await startVisioConference(cours);

// 4. Mettre à jour avec le lien de visio
await updateCourseStatus(coursId, 'en_cours', visioUrl);

// 5. À la fin du cours, enregistrer les présences
await saveAttendance(coursId, attendanceData);

// 6. Marquer le cours comme terminé
await updateCourseStatus(coursId, 'realise');
```

### **Workflow 2 : Évaluation en Ligne**

```javascript
// 1. Récupérer les évaluations programmées
const evaluations = await fetch('/api/lms/evaluations');
const evaluation = evaluations.data.find(e => e.id === evaluationId);

// 2. Créer l'évaluation en ligne dans le LMS
const lmsExam = await createLMSExam(evaluation);

// 3. Les étudiants passent l'évaluation

// 4. Récupérer les résultats du LMS
const results = await getLMSExamResults(lmsExam.id);

// 5. Convertir au format KLASSCI et sauvegarder
const notesKLASSCI = convertToKLASSCIFormat(results);
await saveNotes(evaluationId, notesKLASSCI);
```

### **Workflow 3 : Synchronisation Périodique**

```javascript
// Synchronisation quotidienne des données
const syncData = async () => {
    try {
        // 1. Vérifier l'authentification
        const authCheck = await fetch('/api/lms/auth/check');
        if (!authCheck.success) {
            await reAuthenticate();
        }

        // 2. Récupérer les nouvelles données
        const [matieres, classes, planning] = await Promise.all([
            fetch('/api/lms/matieres'),
            fetch('/api/lms/classes'),
            fetch('/api/lms/emploi-temps')
        ]);

        // 3. Mettre à jour le cache local du LMS
        await updateLMSCache({
            matieres: matieres.data,
            classes: classes.data,
            planning: planning.data
        });

        console.log('Synchronisation réussie');
    } catch (error) {
        console.error('Erreur de synchronisation:', error);
    }
};

// Programmer la synchronisation
setInterval(syncData, 24 * 60 * 60 * 1000); // Toutes les 24h
```

---

## 🛠️ Configuration du LMS

### **1. Variables d'Environnement**

```env
# Configuration KLASSCI API
KLASSCI_API_URL=https://klassci.school.com/api/lms
KLASSCI_CLIENT_ID=your_lms_client_id
KLASSCI_CLIENT_SECRET=your_lms_secret

# Cache et session
KLASSCI_TOKEN_CACHE_TTL=86400
KLASSCI_SYNC_INTERVAL=3600
```

### **2. Classe de Service LMS**

```javascript
class KLASSCIService {
    constructor() {
        this.baseURL = process.env.KLASSCI_API_URL;
        this.token = localStorage.getItem('klassci_token');
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const headers = {
            'Authorization': `Bearer ${this.token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...options.headers
        };

        const response = await fetch(url, {
            ...options,
            headers
        });

        if (response.status === 401) {
            // Token expiré, réauthentifier
            await this.reAuthenticate();
            return this.request(endpoint, options);
        }

        return await response.json();
    }

    // Méthodes spécifiques
    async getMatieres(filters = {}) {
        const params = new URLSearchParams(filters);
        return this.request(`/matieres?${params}`);
    }

    async saveNotes(evaluationId, notes) {
        return this.request(`/evaluations/${evaluationId}/notes`, {
            method: 'POST',
            body: JSON.stringify({ notes })
        });
    }

    // ... autres méthodes
}
```

---

## 🚨 Gestion des Erreurs

### **Codes d'Erreur Communs**

| Code | Signification | Action |
|------|---------------|--------|
| 401 | Token invalide/expiré | Réauthentifier |
| 403 | Accès non autorisé | Vérifier les rôles |
| 404 | Ressource introuvable | Vérifier les IDs |
| 422 | Données invalides | Valider les données |
| 500 | Erreur serveur | Réessayer plus tard |

### **Exemple de Gestion d'Erreurs**

```javascript
const handleAPIError = (error, context) => {
    switch (error.status) {
        case 401:
            // Rediriger vers la connexion
            window.location.href = '/login';
            break;

        case 403:
            showError('Accès non autorisé à cette ressource');
            break;

        case 422:
            // Afficher les erreurs de validation
            showValidationErrors(error.errors);
            break;

        case 500:
            showError('Erreur serveur. Veuillez réessayer plus tard.');
            // Logger l'erreur pour le debug
            console.error('API Error:', error, context);
            break;

        default:
            showError('Une erreur inattendue s\'est produite');
    }
};
```

---

## 📊 Monitoring et Logs

### **1. Logs Côté KLASSCI**

Les APIs KLASSCI loggent automatiquement :
- Tentatives de connexion
- Sauvegardes de notes depuis le LMS
- Enregistrements de présences
- Erreurs d'API

### **2. Monitoring Côté LMS**

Recommandations pour monitorer l'intégration :

```javascript
// Métriques à suivre
const metrics = {
    api_calls_success: 0,
    api_calls_error: 0,
    sync_duration: 0,
    last_sync_time: null,
    token_refreshes: 0
};

// Wrapper pour mesurer les performances
const monitoredRequest = async (endpoint, options) => {
    const start = Date.now();

    try {
        const response = await klassciService.request(endpoint, options);
        metrics.api_calls_success++;
        return response;
    } catch (error) {
        metrics.api_calls_error++;
        throw error;
    } finally {
        metrics.sync_duration = Date.now() - start;
    }
};
```

---

## 🔧 Dépannage

### **Problèmes Fréquents**

1. **Token expiré**
   ```javascript
   // Vérifier la validité du token
   const checkToken = await fetch('/api/lms/auth/check');
   ```

2. **Données manquantes**
   ```javascript
   // Vérifier l'année universitaire courante
   const structure = await fetch('/api/lms/structure');
   ```

3. **Erreurs de permissions**
   ```javascript
   // Vérifier le profil utilisateur
   const profile = await fetch('/api/lms/auth/me');
   ```

---

## 📞 Support

### **Documentation API Complète**
- **Endpoint :** `GET /api/lms/documentation`
- **Authentification :** `GET /api/lms/auth/documentation`

### **Logs de Debug**
- Fichiers logs : `storage/logs/laravel.log`
- Rechercher : `LMS`, `API`, `Sanctum`

### **Contact**
- **Équipe :** KLASSCI Development Team
- **Email :** support@klassci.school.com