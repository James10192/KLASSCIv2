# 🎓 Guide d'Architecture LMS pour KLASSCI

## 🎯 Vue d'Ensemble

Ce guide présente l'architecture complète pour développer un **Learning Management System (LMS)** moderne intégré avec KLASSCI. L'approche recommandée privilégie une **architecture microservices** avec **interface utilisateur Angular** et **backend Java Spring Boot** pour une solution enterprise robuste et évolutive.

---

## 🏗️ **Architecture Générale Recommandée - Enterprise 2024**

### **Stack Technologique Enterprise**

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND - Angular 17                    │
├─────────────────────────────────────────────────────────────┤
│  • Angular 17+ avec Micro Frontend Architecture            │
│  • TypeScript Strict Mode & Signal-based Components       │
│  • Angular Material + CDK pour UI/UX Enterprise           │
│  • NgRx pour State Management                              │
│  • PWA Support + Service Workers                           │
│  • Standalone Components & Lazy Loading                    │
└─────────────────────────────────────────────────────────────┘
                               │
                     REST APIs + GraphQL (optionnel)
                               │
┌─────────────────────────────────────────────────────────────┐
│                 API GATEWAY - Spring Cloud                  │
├─────────────────────────────────────────────────────────────┤
│  • Spring Cloud Gateway                                    │
│  • Load Balancer & Circuit Breaker                         │
│  • Rate Limiting & Authentication                          │
│  • Request Routing & Composition                           │
└─────────────────────────────────────────────────────────────┘
                               │
┌─────────────────────────────────────────────────────────────┐
│              BACKEND - Java Spring Boot Microservices      │
├─────────────────────────────────────────────────────────────┤
│  • Authentication Service (Spring Security + JWT)          │
│  • Course Management Service                               │
│  • Assessment Engine Service                               │
│  • Video Streaming Service                                 │
│  • Notification Service (WebSocket + Email)                │
│  • Analytics & Reporting Service                           │
│  • Integration Service (KLASSCI Sync)                      │
└─────────────────────────────────────────────────────────────┘
                               │
                        Intégration APIs
                               │
┌─────────────────────────────────────────────────────────────┐
│                    KLASSCI APIs                             │
├─────────────────────────────────────────────────────────────┤
│  • Structure & Classes                                      │
│  • Étudiants & Enseignants                                 │
│  • Notes & Évaluations                                     │
│  • Emploi du Temps                                         │
│  • Présences                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 📂 **Structure de Projet Enterprise - Angular + Spring Boot**

### **Architecture Micro Frontend + Microservices**

```
lms-klassci-enterprise/
├── 📁 frontend/                      # Applications Angular
│   ├── 📁 shell-app/                 # Application Shell (Micro Frontend)
│   │   ├── 📁 src/
│   │   │   ├── 📁 app/
│   │   │   │   ├── 📁 core/          # Services Core, Guards, Interceptors
│   │   │   │   ├── 📁 shared/        # Modules partagés
│   │   │   │   ├── 📁 layout/        # Components Layout
│   │   │   │   └── app.module.ts
│   │   │   ├── 📁 assets/
│   │   │   └── main.ts
│   │   ├── angular.json
│   │   ├── package.json
│   │   └── webpack.config.js         # Module Federation
│   │
│   ├── 📁 course-mfe/                # Micro Frontend Cours
│   │   ├── 📁 src/
│   │   │   ├── 📁 app/
│   │   │   │   ├── 📁 features/
│   │   │   │   │   ├── 📁 course-player/  # Lecteur de cours
│   │   │   │   │   ├── 📁 course-list/    # Liste des cours
│   │   │   │   │   └── 📁 course-detail/  # Détail du cours
│   │   │   │   ├── 📁 services/
│   │   │   │   │   ├── course.service.ts
│   │   │   │   │   └── progress.service.ts
│   │   │   │   └── 📁 state/         # NgRx State Management
│   │   │   │       ├── 📁 actions/
│   │   │   │       ├── 📁 reducers/
│   │   │   │       ├── 📁 effects/
│   │   │   │       └── 📁 selectors/
│   │   │   └── main.ts
│   │   └── webpack.config.js
│   │
│   ├── 📁 assessment-mfe/            # Micro Frontend Évaluations
│   │   ├── 📁 src/
│   │   │   ├── 📁 app/
│   │   │   │   ├── 📁 features/
│   │   │   │   │   ├── 📁 quiz-engine/    # Moteur de quiz
│   │   │   │   │   ├── 📁 exam-proctoring/ # Surveillance d'examen
│   │   │   │   │   └── 📁 grade-management/ # Gestion des notes
│   │   │   │   └── 📁 services/
│   │   │   └── main.ts
│   │   └── webpack.config.js
│   │
│   ├── 📁 live-class-mfe/            # Micro Frontend Classes Live
│   │   ├── 📁 src/
│   │   │   ├── 📁 app/
│   │   │   │   ├── 📁 features/
│   │   │   │   │   ├── 📁 video-conference/ # Vidéoconférence
│   │   │   │   │   ├── 📁 whiteboard/     # Tableau blanc
│   │   │   │   │   └── 📁 chat/           # Chat en temps réel
│   │   │   │   └── 📁 services/
│   │   │   └── main.ts
│   │   └── webpack.config.js
│   │
│   ├── 📁 mobile-app/                # Application mobile (Ionic Angular)
│   │   ├── 📁 src/
│   │   │   ├── 📁 app/
│   │   │   │   ├── 📁 pages/
│   │   │   │   ├── 📁 services/
│   │   │   │   └── 📁 shared/
│   │   │   └── main.ts
│   │   ├── ionic.config.json
│   │   └── capacitor.config.ts
│   │
│   └── 📁 shared-libs/               # Bibliothèques partagées Angular
│       ├── 📁 ui-kit/                # Design System Angular Material
│       ├── 📁 klassci-client/        # Client HTTP pour KLASSCI APIs
│       ├── 📁 models/                # Interfaces TypeScript
│       └── 📁 utils/                 # Utilitaires communs
│
├── 📁 backend/                       # Microservices Spring Boot
│   ├── 📁 api-gateway/               # Spring Cloud Gateway
│   │   ├── 📁 src/main/java/com/lms/gateway/
│   │   │   ├── 📁 config/            # Configuration Gateway
│   │   │   ├── 📁 filters/           # Filtres personnalisés
│   │   │   └── GatewayApplication.java
│   │   ├── 📁 src/main/resources/
│   │   │   ├── application.yml
│   │   │   └── bootstrap.yml
│   │   └── pom.xml
│   │
│   ├── 📁 auth-service/              # Service d'Authentification
│   │   ├── 📁 src/main/java/com/lms/auth/
│   │   │   ├── 📁 controller/        # REST Controllers
│   │   │   ├── 📁 service/           # Business Logic
│   │   │   ├── 📁 repository/        # Data Access Layer
│   │   │   ├── 📁 model/             # JPA Entities
│   │   │   ├── 📁 dto/               # Data Transfer Objects
│   │   │   ├── 📁 config/            # Security Configuration
│   │   │   └── AuthServiceApplication.java
│   │   ├── 📁 src/main/resources/
│   │   │   ├── application.yml
│   │   │   └── db/migration/         # Flyway Migrations
│   │   └── pom.xml
│   │
│   ├── 📁 course-service/            # Service de Gestion des Cours
│   │   ├── 📁 src/main/java/com/lms/course/
│   │   │   ├── 📁 controller/
│   │   │   ├── 📁 service/
│   │   │   ├── 📁 repository/
│   │   │   ├── 📁 model/
│   │   │   └── CourseServiceApplication.java
│   │   └── pom.xml
│   │
│   ├── 📁 assessment-service/        # Service d'Évaluation
│   │   ├── 📁 src/main/java/com/lms/assessment/
│   │   └── pom.xml
│   │
│   ├── 📁 video-service/             # Service de Streaming Vidéo
│   │   ├── 📁 src/main/java/com/lms/video/
│   │   └── pom.xml
│   │
│   ├── 📁 notification-service/      # Service de Notifications
│   │   ├── 📁 src/main/java/com/lms/notification/
│   │   └── pom.xml
│   │
│   ├── 📁 klassci-integration-service/ # Service d'Intégration KLASSCI
│   │   ├── 📁 src/main/java/com/lms/klassci/
│   │   │   ├── 📁 client/            # Feign Clients pour KLASSCI
│   │   │   ├── 📁 scheduler/         # Jobs de Synchronisation
│   │   │   └── 📁 mapper/            # Mappers de données
│   │   └── pom.xml
│   │
│   ├── 📁 eureka-server/             # Service Discovery
│   │   ├── 📁 src/main/java/com/lms/eureka/
│   │   └── pom.xml
│   │
│   ├── 📁 config-server/             # Configuration Server
│   │   ├── 📁 src/main/java/com/lms/config/
│   │   └── pom.xml
│   │
│   └── 📁 shared-libs/               # Bibliothèques partagées Java
│       ├── 📁 common-utils/          # Utilitaires communs
│       ├── 📁 security-lib/          # Sécurité partagée
│       └── 📁 klassci-models/        # Modèles KLASSCI
│
├── 📁 infrastructure/                # Infrastructure et Déploiement
│   ├── 📁 docker/                    # Conteneurs Docker
│   │   ├── docker-compose.yml
│   │   ├── Dockerfile.angular
│   │   └── Dockerfile.springboot
│   ├── 📁 kubernetes/                # Déploiement K8s
│   │   ├── 📁 frontend/
│   │   ├── 📁 backend/
│   │   └── 📁 ingress/
│   ├── 📁 terraform/                 # Infrastructure as Code
│   └── 📁 monitoring/                # Prometheus, Grafana
│
├── 📁 config-repo/                   # Configuration externalisée
│   ├── 📁 dev/
│   ├── 📁 staging/
│   └── 📁 prod/
│
└── 📁 docs/                          # Documentation
    ├── architecture.md
    ├── api-documentation.md
    ├── deployment-guide.md
    └── development-setup.md
```

---

## 🔌 **Client KLASSCI - Angular + Java Spring Boot**

### **Frontend - Angular Service & Models**

```typescript
// frontend/shared-libs/klassci-client/src/lib/models/klassci.models.ts

export interface KLASSCIConfig {
  baseUrl: string;
  apiKey?: string;
  timeout?: number;
  retryAttempts?: number;
}

export interface AuthResponse {
  token: string;
  user: UserInfo;
  meta: {
    annee_universitaire_courante: {
      id: number;
      nom: string;
    };
  };
}

export interface UserInfo {
  id: number;
  email: string;
  role: 'enseignant' | 'coordinateur' | 'etudiant';
  permissions: string[];
  prenom: string;
  nom: string;
  profile: {
    avatar?: string;
    telephone?: string;
    date_naissance?: string;
  };
}

export interface Classe {
  id: number;
  nom: string;
  code: string;
  effectif_max: number;
  effectif_actuel: number;
  filiere: Filiere;
  niveau_etude: NiveauEtude;
  matieres: Matiere[];
  etudiants: Etudiant[];
}

export interface Etudiant {
  id: number;
  matricule: string;
  nom: string;
  prenom: string;
  email: string;
  statut_inscription: 'valide' | 'en_attente' | 'suspendu';
  informations_lms: {
    premiere_connexion: string | null;
    derniere_activite: string | null;
    progression_generale: number;
  };
}

export interface Evaluation {
  id: number;
  titre: string;
  type: 'controle_continu' | 'examen_final' | 'projet';
  coefficient: number;
  note_max: number;
  date_prevue: string;
  duree_minutes: number;
  matiere: Matiere;
  classe: Classe;
  enseignant: Enseignant;
  etudiants_concernes: Etudiant[];
  consignes: {
    description: string;
    materiel_autorise: string[];
    duree_max: number;
    type_questions: string[];
  };
  lms_metadata: {
    peut_etre_en_ligne: boolean;
    auto_correction: boolean;
    surveillance_requise: boolean;
  };
}

export interface Filiere {
  id: number;
  nom: string;
  code: string;
  description?: string;
}

export interface NiveauEtude {
  id: number;
  nom: string;
  code: string;
  ordre: number;
}

export interface Matiere {
  id: number;
  nom: string;
  code: string;
  coefficient: number;
  is_active: boolean;
}

export interface Enseignant {
  id: number;
  nom: string;
  prenom: string;
  email: string;
  specialites: string[];
}

export interface CoursEmploiTemps {
  id: number;
  matiere: Matiere;
  classe: Classe;
  enseignant: Enseignant;
  date_cours: string;
  heure_debut: string;
  heure_fin: string;
  salle?: string;
  type_cours: 'presentiel' | 'visio' | 'hybride';
  statut: 'programme' | 'en_cours' | 'realise' | 'annule';
}

// Angular Service pour intégration KLASSCI
// frontend/shared-libs/klassci-client/src/lib/services/klassci-api.service.ts

import { Injectable } from '@angular/core';
import { HttpClient, HttpParams, HttpErrorResponse } from '@angular/common/http';
import { Observable, throwError, BehaviorSubject } from 'rxjs';
import { catchError, map, retry, tap } from 'rxjs/operators';

@Injectable({
  providedIn: 'root'
})
export class KLASSCIApiService {
  private baseUrl = 'http://localhost:8000/api/lms';
  private tokenSubject = new BehaviorSubject<string | null>(localStorage.getItem('klassci_token'));
  public token$ = this.tokenSubject.asObservable();

  constructor(private http: HttpClient) {}

  // ==========================================
  // AUTHENTIFICATION
  // ==========================================

  authenticate(email: string, password: string): Observable<AuthResponse> {
    return this.http.post<{success: boolean; data: AuthResponse}>(`${this.baseUrl}/auth/login`, {
      email,
      password
    }).pipe(
      map(response => response.data),
      tap(authResponse => {
        this.setToken(authResponse.token);
      }),
      catchError(this.handleError)
    );
  }

  logout(): void {
    localStorage.removeItem('klassci_token');
    this.tokenSubject.next(null);
  }

  refreshToken(): Observable<boolean> {
    return this.http.get<{success: boolean}>(`${this.baseUrl}/auth/check`).pipe(
      map(response => response.success),
      catchError(() => {
        this.logout();
        return throwError(() => new Error('Token expired'));
      })
    );
  }

  private setToken(token: string): void {
    localStorage.setItem('klassci_token', token);
    this.tokenSubject.next(token);
  }

  // ==========================================
  // DONNÉES DE STRUCTURE
  // ==========================================

  getStructure(): Observable<{filieres: Filiere[]; niveaux_etude: NiveauEtude[]}> {
    return this.http.get<{success: boolean; data: any}>(`${this.baseUrl}/structure`).pipe(
      map(response => response.data),
      retry(2),
      catchError(this.handleError)
    );
  }

  getClasses(filters?: {filiere_id?: number; niveau_id?: number}): Observable<Classe[]> {
    let params = new HttpParams();
    if (filters?.filiere_id) {
      params = params.set('filiere_id', filters.filiere_id.toString());
    }
    if (filters?.niveau_id) {
      params = params.set('niveau_id', filters.niveau_id.toString());
    }

    return this.http.get<{success: boolean; data: Classe[]}>(`${this.baseUrl}/classes`, { params }).pipe(
      map(response => response.data),
      retry(2),
      catchError(this.handleError)
    );
  }

  getEtudiantsClasse(classeId: number): Observable<Etudiant[]> {
    return this.http.get<{success: boolean; data: Etudiant[]}>(`${this.baseUrl}/classes/${classeId}/etudiants`).pipe(
      map(response => response.data),
      retry(2),
      catchError(this.handleError)
    );
  }

  getMatieres(filters?: {filiere_id?: number; niveau_id?: number}): Observable<Matiere[]> {
    let params = new HttpParams();
    if (filters?.filiere_id) {
      params = params.set('filiere_id', filters.filiere_id.toString());
    }
    if (filters?.niveau_id) {
      params = params.set('niveau_id', filters.niveau_id.toString());
    }

    return this.http.get<{success: boolean; data: Matiere[]}>(`${this.baseUrl}/matieres`, { params }).pipe(
      map(response => response.data),
      retry(2),
      catchError(this.handleError)
    );
  }

  // ==========================================
  // EMPLOI DU TEMPS
  // ==========================================

  getEmploiTemps(filters?: {
    date_debut?: string;
    date_fin?: string;
    classe_id?: number;
    enseignant_id?: number;
  }): Observable<CoursEmploiTemps[]> {
    let params = new HttpParams();

    Object.entries(filters || {}).forEach(([key, value]) => {
      if (value !== undefined) {
        params = params.set(key, value.toString());
      }
    });

    return this.http.get<{success: boolean; data: CoursEmploiTemps[]}>(`${this.baseUrl}/emploi-temps`, { params }).pipe(
      map(response => response.data),
      retry(2),
      catchError(this.handleError)
    );
  }

  // ==========================================
  // ÉVALUATIONS
  // ==========================================

  getEvaluations(filters?: {
    matiere_id?: number;
    classe_id?: number;
    statut?: 'programmee' | 'en_cours' | 'terminee';
  }): Observable<Evaluation[]> {
    let params = new HttpParams();

    Object.entries(filters || {}).forEach(([key, value]) => {
      if (value !== undefined) {
        params = params.set(key, value.toString());
      }
    });

    return this.http.get<{success: boolean; data: Evaluation[]}>(`${this.baseUrl}/evaluations`, { params }).pipe(
      map(response => response.data),
      retry(2),
      catchError(this.handleError)
    );
  }

  previewEvaluationNotes(evaluationId: number, notes: any[]): Observable<any> {
    return this.http.post<{success: boolean; data: any}>(`${this.baseUrl}/evaluations/${evaluationId}/notes/preview`, {
      notes
    }).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  saveEvaluationNotes(evaluationId: number, notes: any[], metadata?: any): Observable<any> {
    return this.http.post<{success: boolean; data: any}>(`${this.baseUrl}/evaluations/${evaluationId}/notes`, {
      notes,
      ...metadata
    }).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  // ==========================================
  // PRÉSENCES ET COURS
  // ==========================================

  updateCourseStatus(coursId: number, statut: string, metadata?: any): Observable<void> {
    return this.http.put<{success: boolean}>(`${this.baseUrl}/cours/${coursId}/statut`, {
      statut,
      ...metadata
    }).pipe(
      map(() => void 0),
      catchError(this.handleError)
    );
  }

  saveCourseAttendance(coursId: number, attendance: any): Observable<any> {
    return this.http.post<{success: boolean; data: any}>(`${this.baseUrl}/cours/${coursId}/presences`, attendance).pipe(
      map(response => response.data),
      catchError(this.handleError)
    );
  }

  // ==========================================
  // GESTION D'ERREURS
  // ==========================================

  private handleError(error: HttpErrorResponse): Observable<never> {
    let errorMessage = 'Une erreur est survenue';

    if (error.error instanceof ErrorEvent) {
      // Erreur côté client
      errorMessage = `Erreur: ${error.error.message}`;
    } else {
      // Erreur côté serveur
      switch (error.status) {
        case 401:
          errorMessage = 'Non autorisé - Veuillez vous reconnecter';
          break;
        case 403:
          errorMessage = 'Accès refusé';
          break;
        case 404:
          errorMessage = 'Ressource non trouvée';
          break;
        case 500:
          errorMessage = 'Erreur serveur interne';
          break;
        default:
          errorMessage = `Erreur ${error.status}: ${error.message}`;
      }
    }

    console.error('KLASSCI API Error:', errorMessage);
    return throwError(() => new Error(errorMessage));
  }
}

// Interceptor HTTP pour l'authentification automatique
// frontend/shared-libs/klassci-client/src/lib/interceptors/auth.interceptor.ts

import { Injectable } from '@angular/core';
import { HttpInterceptor, HttpRequest, HttpHandler, HttpEvent } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    const token = localStorage.getItem('klassci_token');

    if (token && req.url.includes('/api/lms')) {
      const authReq = req.clone({
        headers: req.headers.set('Authorization', `Bearer ${token}`)
      });
      return next.handle(authReq);
    }

    return next.handle(req);
  }
}
```

### **Backend - Java Spring Boot Services**

```java
// backend/klassci-integration-service/src/main/java/com/lms/klassci/model/KlassciUser.java

package com.lms.klassci.model;

import lombok.Data;
import lombok.NoArgsConstructor;
import lombok.AllArgsConstructor;

import java.time.LocalDateTime;
import java.util.List;

@Data
@NoArgsConstructor
@AllArgsConstructor
public class KlassciUser {
    private Long id;
    private String email;
    private String nom;
    private String prenom;
    private String role; // enseignant, coordinateur, etudiant
    private List<String> permissions;
    private UserProfile profile;
    private LocalDateTime lastSyncAt;

    @Data
    @NoArgsConstructor
    @AllArgsConstructor
    public static class UserProfile {
        private String avatar;
        private String telephone;
        private String dateNaissance;
    }
}

// backend/klassci-integration-service/src/main/java/com/lms/klassci/model/KlassciClasse.java

package com.lms.klassci.model;

import lombok.Data;
import lombok.NoArgsConstructor;
import lombok.AllArgsConstructor;

import java.util.List;

@Data
@NoArgsConstructor
@AllArgsConstructor
public class KlassciClasse {
    private Long id;
    private String nom;
    private String code;
    private Integer effectifMax;
    private Integer effectifActuel;
    private KlassciFiliere filiere;
    private KlassciNiveauEtude niveauEtude;
    private List<KlassciMatiere> matieres;
    private List<KlassciEtudiant> etudiants;
}

@Data
@NoArgsConstructor
@AllArgsConstructor
class KlassciFiliere {
    private Long id;
    private String nom;
    private String code;
    private String description;
}

@Data
@NoArgsConstructor
@AllArgsConstructor
class KlassciNiveauEtude {
    private Long id;
    private String nom;
    private String code;
    private Integer ordre;
}

@Data
@NoArgsConstructor
@AllArgsConstructor
class KlassciMatiere {
    private Long id;
    private String nom;
    private String code;
    private Double coefficient;
    private Boolean isActive;
}

@Data
@NoArgsConstructor
@AllArgsConstructor
class KlassciEtudiant {
    private Long id;
    private String matricule;
    private String nom;
    private String prenom;
    private String email;
    private String statutInscription;
    private InformationsLms informationsLms;

    @Data
    @NoArgsConstructor
    @AllArgsConstructor
    public static class InformationsLms {
        private LocalDateTime premiereConnexion;
        private LocalDateTime derniereActivite;
        private Double progressionGenerale;
    }
}

// backend/klassci-integration-service/src/main/java/com/lms/klassci/client/KlassciApiClient.java

package com.lms.klassci.client;

import com.lms.klassci.model.*;
import org.springframework.cloud.openfeign.FeignClient;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@FeignClient(
    name = "klassci-api",
    url = "${app.klassci.api.base-url}",
    configuration = KlassciClientConfig.class
)
public interface KlassciApiClient {

    // ==========================================
    // AUTHENTIFICATION
    // ==========================================

    @PostMapping("/api/lms/auth/login")
    ApiResponse<AuthenticationResponse> authenticate(
        @RequestBody AuthenticationRequest request
    );

    @GetMapping("/api/lms/auth/check")
    ApiResponse<Boolean> checkToken();

    @GetMapping("/api/lms/auth/me")
    ApiResponse<KlassciUser> getCurrentUser();

    // ==========================================
    // STRUCTURE
    // ==========================================

    @GetMapping("/api/lms/structure")
    ApiResponse<StructureResponse> getStructure();

    @GetMapping("/api/lms/classes")
    ApiResponse<List<KlassciClasse>> getClasses(
        @RequestParam(required = false) Long filiereId,
        @RequestParam(required = false) Long niveauId
    );

    @GetMapping("/api/lms/classes/{classeId}/etudiants")
    ApiResponse<List<KlassciEtudiant>> getEtudiantsClasse(
        @PathVariable Long classeId
    );

    @GetMapping("/api/lms/matieres")
    ApiResponse<List<KlassciMatiere>> getMatieres(
        @RequestParam(required = false) Long filiereId,
        @RequestParam(required = false) Long niveauId
    );

    // ==========================================
    // EMPLOI DU TEMPS
    // ==========================================

    @GetMapping("/api/lms/emploi-temps")
    ApiResponse<List<KlassciCoursEmploiTemps>> getEmploiTemps(
        @RequestParam(required = false) String dateDebut,
        @RequestParam(required = false) String dateFin,
        @RequestParam(required = false) Long classeId,
        @RequestParam(required = false) Long enseignantId
    );

    // ==========================================
    // ÉVALUATIONS
    // ==========================================

    @GetMapping("/api/lms/evaluations")
    ApiResponse<List<KlassciEvaluation>> getEvaluations(
        @RequestParam(required = false) Long matiereId,
        @RequestParam(required = false) Long classeId,
        @RequestParam(required = false) String statut
    );

    @PostMapping("/api/lms/evaluations/{evaluationId}/notes/preview")
    ApiResponse<NotesPreviewResponse> previewEvaluationNotes(
        @PathVariable Long evaluationId,
        @RequestBody NotesRequest request
    );

    @PostMapping("/api/lms/evaluations/{evaluationId}/notes")
    ApiResponse<SaveNotesResponse> saveEvaluationNotes(
        @PathVariable Long evaluationId,
        @RequestBody SaveNotesRequest request
    );

    // ==========================================
    // COURS ET PRÉSENCES
    // ==========================================

    @PutMapping("/api/lms/cours/{coursId}/statut")
    ApiResponse<Void> updateCourseStatus(
        @PathVariable Long coursId,
        @RequestBody UpdateCourseStatusRequest request
    );

    @PostMapping("/api/lms/cours/{coursId}/presences")
    ApiResponse<SaveAttendanceResponse> saveCourseAttendance(
        @PathVariable Long coursId,
        @RequestBody SaveAttendanceRequest request
    );
}

// backend/klassci-integration-service/src/main/java/com/lms/klassci/config/KlassciClientConfig.java

package com.lms.klassci.config;

import feign.RequestInterceptor;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

@Configuration
public class KlassciClientConfig {

    @Value("${app.klassci.api.token:}")
    private String klassciApiToken;

    @Bean
    public RequestInterceptor requestInterceptor() {
        return requestTemplate -> {
            if (klassciApiToken != null && !klassciApiToken.isEmpty()) {
                requestTemplate.header("Authorization", "Bearer " + klassciApiToken);
            }
            requestTemplate.header("Content-Type", "application/json");
            requestTemplate.header("Accept", "application/json");
        };
    }
}

// backend/klassci-integration-service/src/main/java/com/lms/klassci/service/KlassciIntegrationService.java

package com.lms.klassci.service;

import com.lms.klassci.client.KlassciApiClient;
import com.lms.klassci.model.*;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.cache.annotation.Cacheable;
import org.springframework.retry.annotation.Backoff;
import org.springframework.retry.annotation.Retryable;
import org.springframework.stereotype.Service;

import java.util.List;
import java.util.Optional;

@Slf4j
@Service
@RequiredArgsConstructor
public class KlassciIntegrationService {

    private final KlassciApiClient klassciApiClient;

    // ==========================================
    // AUTHENTIFICATION
    // ==========================================

    @Retryable(value = {Exception.class}, maxAttempts = 3, backoff = @Backoff(delay = 1000))
    public Optional<AuthenticationResponse> authenticate(String email, String password) {
        try {
            log.info("Tentative d'authentification pour l'utilisateur: {}", email);

            AuthenticationRequest request = new AuthenticationRequest(email, password);
            ApiResponse<AuthenticationResponse> response = klassciApiClient.authenticate(request);

            if (response.isSuccess()) {
                log.info("Authentification réussie pour l'utilisateur: {}", email);
                return Optional.of(response.getData());
            } else {
                log.warn("Échec de l'authentification pour l'utilisateur: {}", email);
                return Optional.empty();
            }
        } catch (Exception e) {
            log.error("Erreur lors de l'authentification pour l'utilisateur: {}", email, e);
            throw new KlassciIntegrationException("Erreur d'authentification KLASSCI", e);
        }
    }

    public boolean validateToken() {
        try {
            ApiResponse<Boolean> response = klassciApiClient.checkToken();
            return response.isSuccess() && response.getData();
        } catch (Exception e) {
            log.error("Erreur lors de la validation du token", e);
            return false;
        }
    }

    public Optional<KlassciUser> getCurrentUser() {
        try {
            ApiResponse<KlassciUser> response = klassciApiClient.getCurrentUser();
            return response.isSuccess() ? Optional.of(response.getData()) : Optional.empty();
        } catch (Exception e) {
            log.error("Erreur lors de la récupération de l'utilisateur courant", e);
            return Optional.empty();
        }
    }

    // ==========================================
    // DONNÉES DE STRUCTURE
    // ==========================================

    @Cacheable(value = "klassci-structure", unless = "#result == null")
    @Retryable(value = {Exception.class}, maxAttempts = 3, backoff = @Backoff(delay = 1000))
    public Optional<StructureResponse> getStructure() {
        try {
            log.info("Récupération de la structure depuis KLASSCI");

            ApiResponse<StructureResponse> response = klassciApiClient.getStructure();

            if (response.isSuccess()) {
                log.info("Structure récupérée avec succès: {} filières, {} niveaux",
                    response.getData().getFilieres().size(),
                    response.getData().getNiveauxEtude().size());
                return Optional.of(response.getData());
            } else {
                log.warn("Échec de récupération de la structure");
                return Optional.empty();
            }
        } catch (Exception e) {
            log.error("Erreur lors de la récupération de la structure", e);
            throw new KlassciIntegrationException("Erreur récupération structure KLASSCI", e);
        }
    }

    @Cacheable(value = "klassci-classes", key = "#filiereId + '_' + #niveauId", unless = "#result == null")
    @Retryable(value = {Exception.class}, maxAttempts = 3, backoff = @Backoff(delay = 1000))
    public List<KlassciClasse> getClasses(Long filiereId, Long niveauId) {
        try {
            log.info("Récupération des classes - filière: {}, niveau: {}", filiereId, niveauId);

            ApiResponse<List<KlassciClasse>> response = klassciApiClient.getClasses(filiereId, niveauId);

            if (response.isSuccess()) {
                log.info("Classes récupérées: {} résultats", response.getData().size());
                return response.getData();
            } else {
                log.warn("Échec de récupération des classes");
                return List.of();
            }
        } catch (Exception e) {
            log.error("Erreur lors de la récupération des classes", e);
            throw new KlassciIntegrationException("Erreur récupération classes KLASSCI", e);
        }
    }

    @Cacheable(value = "klassci-etudiants-classe", key = "#classeId", unless = "#result == null")
    @Retryable(value = {Exception.class}, maxAttempts = 3, backoff = @Backoff(delay = 1000))
    public List<KlassciEtudiant> getEtudiantsClasse(Long classeId) {
        try {
            log.info("Récupération des étudiants pour la classe: {}", classeId);

            ApiResponse<List<KlassciEtudiant>> response = klassciApiClient.getEtudiantsClasse(classeId);

            if (response.isSuccess()) {
                log.info("Étudiants récupérés pour la classe {}: {} étudiants", classeId, response.getData().size());
                return response.getData();
            } else {
                log.warn("Échec de récupération des étudiants pour la classe: {}", classeId);
                return List.of();
            }
        } catch (Exception e) {
            log.error("Erreur lors de la récupération des étudiants pour la classe: {}", classeId, e);
            throw new KlassciIntegrationException("Erreur récupération étudiants KLASSCI", e);
        }
    }

    // ==========================================
    // ÉVALUATIONS ET NOTES
    // ==========================================

    public Optional<SaveNotesResponse> saveEvaluationNotes(Long evaluationId, SaveNotesRequest request) {
        try {
            log.info("Sauvegarde des notes pour l'évaluation: {}", evaluationId);

            ApiResponse<SaveNotesResponse> response = klassciApiClient.saveEvaluationNotes(evaluationId, request);

            if (response.isSuccess()) {
                log.info("Notes sauvegardées avec succès pour l'évaluation: {}", evaluationId);
                return Optional.of(response.getData());
            } else {
                log.warn("Échec de sauvegarde des notes pour l'évaluation: {}", evaluationId);
                return Optional.empty();
            }
        } catch (Exception e) {
            log.error("Erreur lors de la sauvegarde des notes pour l'évaluation: {}", evaluationId, e);
            throw new KlassciIntegrationException("Erreur sauvegarde notes KLASSCI", e);
        }
    }

    // ==========================================
    // PRÉSENCES
    // ==========================================

    public Optional<SaveAttendanceResponse> saveCourseAttendance(Long coursId, SaveAttendanceRequest request) {
        try {
            log.info("Sauvegarde des présences pour le cours: {}", coursId);

            ApiResponse<SaveAttendanceResponse> response = klassciApiClient.saveCourseAttendance(coursId, request);

            if (response.isSuccess()) {
                log.info("Présences sauvegardées avec succès pour le cours: {}", coursId);
                return Optional.of(response.getData());
            } else {
                log.warn("Échec de sauvegarde des présences pour le cours: {}", coursId);
                return Optional.empty();
            }
        } catch (Exception e) {
            log.error("Erreur lors de la sauvegarde des présences pour le cours: {}", coursId, e);
            throw new KlassciIntegrationException("Erreur sauvegarde présences KLASSCI", e);
        }
    }
}

// backend/klassci-integration-service/src/main/java/com/lms/klassci/exception/KlassciIntegrationException.java

package com.lms.klassci.exception;

public class KlassciIntegrationException extends RuntimeException {

    public KlassciIntegrationException(String message) {
        super(message);
    }

    public KlassciIntegrationException(String message, Throwable cause) {
        super(message, cause);
    }
}
```

---

## 🎨 **Composants Angular Enterprise**

### **Dashboard Principal avec Micro Frontend**

```typescript
// frontend/shell-app/src/app/pages/dashboard/dashboard.component.ts

import { Component, OnInit } from '@angular/core';
import { Observable } from 'rxjs';
import { Store } from '@ngrx/store';
import { selectUser, selectUserRole } from '../../core/auth/auth.selectors';
import { selectDashboardData, selectDashboardLoading } from './store/dashboard.selectors';
import { loadDashboardData } from './store/dashboard.actions';
import { UserInfo, Classe } from '../../shared/models/klassci.models';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit {
  user$ = this.store.select(selectUser);
  userRole$ = this.store.select(selectUserRole);
  dashboardData$ = this.store.select(selectDashboardData);
  loading$ = this.store.select(selectDashboardLoading);

  constructor(private store: Store) {}

  ngOnInit(): void {
    this.store.dispatch(loadDashboardData());
  }

  trackByClasseId(index: number, classe: Classe): number {
    return classe.id;
  }
}
```

```html
<!-- frontend/shell-app/src/app/pages/dashboard/dashboard.component.html -->

<div class="dashboard-container" *ngIf="!loading$ | async; else loadingTemplate">

  <!-- En-tête personnalisé -->
  <mat-card class="welcome-card">
    <mat-card-header>
      <div mat-card-avatar class="avatar-icon">
        <mat-icon>account_circle</mat-icon>
      </div>
      <mat-card-title>
        Bonjour {{ (user$ | async)?.prenom }} 👋
      </mat-card-title>
      <mat-card-subtitle>
        Continuez votre apprentissage où vous vous êtes arrêté
      </mat-card-subtitle>
    </mat-card-header>
  </mat-card>

  <!-- Layout adaptatif basé sur le rôle -->
  <div class="dashboard-grid">

    <!-- Colonne principale -->
    <div class="main-content">

      <!-- Mes cours actuels -->
      <mat-card class="courses-card">
        <mat-card-header>
          <mat-card-title>Mes Cours</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <div class="courses-grid">
            <app-course-card
              *ngFor="let classe of (dashboardData$ | async)?.classes; trackBy: trackByClasseId"
              [classe]="classe"
              [userRole]="userRole$ | async">
            </app-course-card>
          </div>
        </mat-card-content>
      </mat-card>

      <!-- Évaluations en cours (enseignant) -->
      <mat-card
        *ngIf="(userRole$ | async) === 'enseignant'"
        class="evaluations-card">
        <mat-card-header>
          <mat-card-title>Évaluations à Corriger</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <app-pending-evaluations></app-pending-evaluations>
        </mat-card-content>
      </mat-card>

      <!-- Notes récentes (étudiant) -->
      <mat-card
        *ngIf="(userRole$ | async) === 'etudiant'"
        class="grades-card">
        <mat-card-header>
          <mat-card-title>Mes Dernières Notes</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <app-recent-grades></app-recent-grades>
        </mat-card-content>
      </mat-card>
    </div>

    <!-- Barre latérale -->
    <div class="sidebar-content">

      <!-- Emploi du temps -->
      <mat-card class="schedule-card">
        <mat-card-header>
          <mat-card-title>Prochains Cours</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <app-upcoming-classes></app-upcoming-classes>
        </mat-card-content>
      </mat-card>

      <!-- Progression générale -->
      <mat-card class="progress-card">
        <mat-card-header>
          <mat-card-title>Ma Progression</mat-card-title>
        </mat-card-header>
        <mat-card-content>
          <app-progress-overview></app-progress-overview>
        </mat-card-content>
      </mat-card>

      <!-- Notifications -->
      <mat-card class="notifications-card">
        <mat-card-header>
          <mat-card-title>Notifications</mat-card-title>
          <button mat-icon-button>
            <mat-icon [matBadge]="(dashboardData$ | async)?.unreadNotifications"
                      matBadgeColor="warn">
              notifications
            </mat-icon>
          </button>
        </mat-card-header>
        <mat-card-content>
          <app-notification-panel></app-notification-panel>
        </mat-card-content>
      </mat-card>
    </div>
  </div>
</div>

<ng-template #loadingTemplate>
  <div class="loading-container">
    <mat-spinner diameter="50"></mat-spinner>
    <p>Chargement du tableau de bord...</p>
  </div>
</ng-template>
```

```scss
/* frontend/shell-app/src/app/pages/dashboard/dashboard.component.scss */

.dashboard-container {
  padding: 24px;
  background-color: #f5f5f5;
  min-height: 100vh;
}

.welcome-card {
  margin-bottom: 24px;

  .avatar-icon {
    background-color: #3f51b5;
    color: white;
  }
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
  }
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.sidebar-content {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.courses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 16px;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 50vh;
  gap: 16px;
}

mat-card {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  border-radius: 8px;
}
```

### **Lecteur de Cours Angular avec WebRTC**

```typescript
// frontend/course-mfe/src/app/features/course-player/course-player.component.ts

import { Component, OnInit, OnDestroy, ViewChild, ElementRef, Input } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Store } from '@ngrx/store';
import { Observable, Subject, interval } from 'rxjs';
import { takeUntil, filter } from 'rxjs/operators';

import { Course, Lesson, ProgressUpdate } from '../../models/course.models';
import { selectCurrentCourse, selectCurrentLesson, selectProgress } from './store/course-player.selectors';
import { loadCourse, updateProgress, completeLesson } from './store/course-player.actions';

@Component({
  selector: 'app-course-player',
  templateUrl: './course-player.component.html',
  styleUrls: ['./course-player.component.scss']
})
export class CoursePlayerComponent implements OnInit, OnDestroy {
  @ViewChild('videoPlayer', { static: false }) videoPlayer!: ElementRef<HTMLVideoElement>;

  course$ = this.store.select(selectCurrentCourse);
  currentLesson$ = this.store.select(selectCurrentLesson);
  progress$ = this.store.select(selectProgress);

  private destroy$ = new Subject<void>();
  private progressInterval$ = interval(5000).pipe(takeUntil(this.destroy$));

  isPlaying = false;
  showTranscript = false;
  currentProgress = 0;
  notes = '';

  constructor(
    private route: ActivatedRoute,
    private store: Store
  ) {}

  ngOnInit(): void {
    const courseId = this.route.snapshot.params['courseId'];
    this.store.dispatch(loadCourse({ courseId }));

    // Auto-sauvegarde du progrès
    this.progressInterval$.subscribe(() => {
      this.saveProgress();
    });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  onVideoPlay(): void {
    this.isPlaying = true;
  }

  onVideoPause(): void {
    this.isPlaying = false;
  }

  onVideoTimeUpdate(): void {
    if (this.videoPlayer?.nativeElement) {
      const video = this.videoPlayer.nativeElement;
      this.currentProgress = (video.currentTime / video.duration) * 100;

      // Marquer comme terminé si > 90%
      if (this.currentProgress > 90) {
        this.store.dispatch(completeLesson());
      }
    }
  }

  private saveProgress(): void {
    if (this.videoPlayer?.nativeElement && this.isPlaying) {
      const progressUpdate: ProgressUpdate = {
        progress: this.currentProgress,
        timeSpent: this.videoPlayer.nativeElement.currentTime,
        metadata: {
          videoProgress: this.currentProgress,
          notes: this.notes
        }
      };

      this.store.dispatch(updateProgress({ progressUpdate }));
    }
  }

  togglePlayPause(): void {
    if (this.videoPlayer?.nativeElement) {
      const video = this.videoPlayer.nativeElement;
      if (video.paused) {
        video.play();
      } else {
        video.pause();
      }
    }
  }

  toggleTranscript(): void {
    this.showTranscript = !this.showTranscript;
  }

  saveNotes(): void {
    // Logique de sauvegarde des notes
    console.log('Notes sauvegardées:', this.notes);
  }

  goToNextLesson(): void {
    // Logique pour passer au cours suivant
  }

  goToPreviousLesson(): void {
    // Logique pour revenir au cours précédent
  }
}
```

```html
<!-- frontend/course-mfe/src/app/features/course-player/course-player.component.html -->

<div class="course-player-container" *ngIf="course$ | async as course">

  <!-- Zone vidéo principale -->
  <mat-card class="video-card">
    <div class="video-container">
      <video
        #videoPlayer
        [src]="(currentLesson$ | async)?.videoUrl"
        [poster]="(currentLesson$ | async)?.thumbnail"
        class="video-player"
        (play)="onVideoPlay()"
        (pause)="onVideoPause()"
        (timeupdate)="onVideoTimeUpdate()"
        controls>
      </video>

      <!-- Overlay de contrôles personnalisés -->
      <div class="video-overlay">
        <div class="video-controls">
          <div class="controls-left">
            <button mat-icon-button (click)="togglePlayPause()" class="play-button">
              <mat-icon>{{ isPlaying ? 'pause_circle' : 'play_circle' }}</mat-icon>
            </button>
            <span class="progress-text">{{ currentProgress | number:'1.0-0' }}% terminé</span>
          </div>

          <div class="controls-right">
            <button mat-stroked-button
                    (click)="toggleTranscript()"
                    class="transcript-button">
              Transcript
            </button>
            <mat-icon>volume_up</mat-icon>
          </div>
        </div>

        <!-- Barre de progression -->
        <mat-progress-bar
          mode="determinate"
          [value]="currentProgress"
          class="progress-bar">
        </mat-progress-bar>
      </div>
    </div>
  </mat-card>

  <!-- Panneau inférieur -->
  <mat-card class="content-card">
    <mat-card-header>
      <mat-card-title>{{ (currentLesson$ | async)?.title }}</mat-card-title>
      <mat-card-subtitle>{{ (currentLesson$ | async)?.description }}</mat-card-subtitle>

      <div class="lesson-navigation">
        <button mat-button (click)="goToPreviousLesson()">
          <mat-icon>skip_previous</mat-icon>
          Précédent
        </button>
        <button mat-raised-button color="primary" (click)="goToNextLesson()">
          Suivant
          <mat-icon>skip_next</mat-icon>
        </button>
      </div>
    </mat-card-header>

    <mat-card-content>
      <!-- Tabs: Notes, Ressources, Discussion -->
      <mat-tab-group>
        <mat-tab label="Mes Notes">
          <div class="notes-container">
            <mat-form-field appearance="outline" class="notes-field">
              <mat-label>Prenez vos notes ici...</mat-label>
              <textarea
                matInput
                [(ngModel)]="notes"
                rows="8"
                placeholder="Prenez vos notes ici...">
              </textarea>
            </mat-form-field>
            <div class="notes-actions">
              <button mat-raised-button color="accent" (click)="saveNotes()">
                <mat-icon>save</mat-icon>
                Sauvegarder
              </button>
            </div>
          </div>
        </mat-tab>

        <mat-tab label="Ressources">
          <app-lesson-resources [lesson]="currentLesson$ | async"></app-lesson-resources>
        </mat-tab>

        <mat-tab label="Discussion">
          <app-lesson-discussion [lesson]="currentLesson$ | async"></app-lesson-discussion>
        </mat-tab>
      </mat-tab-group>
    </mat-card-content>
  </mat-card>

  <!-- Playlist des leçons -->
  <mat-card class="playlist-card">
    <mat-card-header>
      <mat-card-title>Plan du cours</mat-card-title>
    </mat-card-header>
    <mat-card-content>
      <mat-list>
        <mat-list-item
          *ngFor="let lesson of course.lessons; let i = index"
          [class.active]="lesson.id === (currentLesson$ | async)?.id"
          (click)="selectLesson(lesson)">

          <mat-icon matListIcon>
            {{ i < (progress$ | async)?.currentLessonIndex ? 'check_circle' : 'play_circle_outline' }}
          </mat-icon>

          <div matLine>{{ lesson.title }}</div>
          <div matLine class="lesson-duration">{{ lesson.duration }} min</div>

          <mat-progress-bar
            *ngIf="lesson.progress > 0"
            mode="determinate"
            [value]="lesson.progress"
            class="lesson-progress">
          </mat-progress-bar>
        </mat-list-item>
      </mat-list>
    </mat-card-content>
  </mat-card>
</div>
```

### **Système d'Évaluation Angular avec NgRx**

```typescript
// frontend/assessment-mfe/src/app/features/quiz-engine/quiz-engine.component.ts

import { Component, OnInit, OnDestroy, Input } from '@angular/core';
import { Store } from '@ngrx/store';
import { Observable, Subject, timer } from 'rxjs';
import { takeUntil, filter } from 'rxjs/operators';

import { Evaluation, Question, QuestionResult } from '../../models/assessment.models';
import {
  selectCurrentEvaluation,
  selectAnswers,
  selectTimeRemaining,
  selectCurrentQuestionIndex
} from './store/quiz.selectors';
import {
  loadEvaluation,
  answerQuestion,
  submitEvaluation,
  nextQuestion,
  previousQuestion,
  autoSubmit
} from './store/quiz.actions';

@Component({
  selector: 'app-quiz-engine',
  templateUrl: './quiz-engine.component.html',
  styleUrls: ['./quiz-engine.component.scss']
})
export class QuizEngineComponent implements OnInit, OnDestroy {
  @Input() evaluationId!: number;

  evaluation$ = this.store.select(selectCurrentEvaluation);
  answers$ = this.store.select(selectAnswers);
  timeRemaining$ = this.store.select(selectTimeRemaining);
  currentQuestionIndex$ = this.store.select(selectCurrentQuestionIndex);

  private destroy$ = new Subject<void>();
  isSubmitting = false;

  constructor(private store: Store) {}

  ngOnInit(): void {
    this.store.dispatch(loadEvaluation({ evaluationId: this.evaluationId }));

    // Timer automatique
    this.timeRemaining$.pipe(
      takeUntil(this.destroy$),
      filter(time => time === 0)
    ).subscribe(() => {
      this.store.dispatch(autoSubmit());
    });
  }

  ngOnDestroy(): void {
    this.destroy$.next();
    this.destroy$.complete();
  }

  onAnswerChange(questionId: number, answer: any): void {
    this.store.dispatch(answerQuestion({ questionId, answer }));
  }

  onNextQuestion(): void {
    this.store.dispatch(nextQuestion());
  }

  onPreviousQuestion(): void {
    this.store.dispatch(previousQuestion());
  }

  onSubmitEvaluation(): void {
    this.isSubmitting = true;
    this.store.dispatch(submitEvaluation());
  }

  goToQuestion(index: number): void {
    // Navigation directe vers une question
  }

  formatTime(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  }

  getQuestionIcon(index: number, answered: boolean): string {
    if (answered) return 'check_circle';
    return 'radio_button_unchecked';
  }

  getQuestionColor(index: number, current: boolean, answered: boolean): string {
    if (current) return 'primary';
    if (answered) return 'accent';
    return '';
  }
}
```

---

## ⚙️ **Backend Services Spring Boot Enterprise**

### **Service d'Authentification avec Spring Security**

```java
// backend/auth-service/src/main/java/com/lms/auth/controller/AuthController.java

package com.lms.auth.controller;

import com.lms.auth.dto.AuthRequest;
import com.lms.auth.dto.AuthResponse;
import com.lms.auth.dto.RefreshTokenRequest;
import com.lms.auth.service.AuthService;
import com.lms.common.dto.ApiResponse;

import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import javax.validation.Valid;

@Slf4j
@RestController
@RequestMapping("/api/auth")
@RequiredArgsConstructor
@CrossOrigin(origins = "*", maxAge = 3600)
public class AuthController {

    private final AuthService authService;

    @PostMapping("/login")
    public ResponseEntity<ApiResponse<AuthResponse>> authenticate(
            @Valid @RequestBody AuthRequest authRequest) {

        log.info("Tentative d'authentification pour: {}", authRequest.getEmail());

        try {
            AuthResponse authResponse = authService.authenticate(
                authRequest.getEmail(),
                authRequest.getPassword()
            );

            return ResponseEntity.ok(
                ApiResponse.success(authResponse, "Authentification réussie")
            );

        } catch (Exception e) {
            log.error("Erreur d'authentification pour {}: {}", authRequest.getEmail(), e.getMessage());
            return ResponseEntity.badRequest().body(
                ApiResponse.error("Identifiants incorrects", null)
            );
        }
    }

    @PostMapping("/refresh")
    public ResponseEntity<ApiResponse<AuthResponse>> refreshToken(
            @Valid @RequestBody RefreshTokenRequest refreshRequest) {

        try {
            AuthResponse authResponse = authService.refreshToken(refreshRequest.getRefreshToken());

            return ResponseEntity.ok(
                ApiResponse.success(authResponse, "Token rafraîchi avec succès")
            );

        } catch (Exception e) {
            log.error("Erreur lors du rafraîchissement du token: {}", e.getMessage());
            return ResponseEntity.badRequest().body(
                ApiResponse.error("Token de rafraîchissement invalide", null)
            );
        }
    }

    @PostMapping("/logout")
    public ResponseEntity<ApiResponse<Void>> logout(
            @RequestHeader("Authorization") String token) {

        try {
            String jwtToken = token.replace("Bearer ", "");
            authService.logout(jwtToken);

            return ResponseEntity.ok(
                ApiResponse.success(null, "Déconnexion réussie")
            );

        } catch (Exception e) {
            log.error("Erreur lors de la déconnexion: {}", e.getMessage());
            return ResponseEntity.badRequest().body(
                ApiResponse.error("Erreur lors de la déconnexion", null)
            );
        }
    }

    @GetMapping("/me")
    public ResponseEntity<ApiResponse<UserInfo>> getCurrentUser(
            @RequestHeader("Authorization") String token) {

        try {
            String jwtToken = token.replace("Bearer ", "");
            UserInfo userInfo = authService.getCurrentUser(jwtToken);

            return ResponseEntity.ok(
                ApiResponse.success(userInfo, "Informations utilisateur récupérées")
            );

        } catch (Exception e) {
            log.error("Erreur lors de la récupération des informations utilisateur: {}", e.getMessage());
            return ResponseEntity.badRequest().body(
                ApiResponse.error("Token invalide", null)
            );
        }
    }

    @GetMapping("/check")
    public ResponseEntity<ApiResponse<Boolean>> checkToken(
            @RequestHeader("Authorization") String token) {

        try {
            String jwtToken = token.replace("Bearer ", "");
            boolean isValid = authService.validateToken(jwtToken);

            return ResponseEntity.ok(
                ApiResponse.success(isValid, "Token validé")
            );

        } catch (Exception e) {
            return ResponseEntity.ok(
                ApiResponse.success(false, "Token invalide")
            );
        }
    }
}

// backend/auth-service/src/main/java/com/lms/auth/service/AuthService.java

package com.lms.auth.service;

import com.lms.auth.dto.AuthResponse;
import com.lms.auth.dto.UserInfo;
import com.lms.auth.entity.User;
import com.lms.auth.entity.RefreshToken;
import com.lms.auth.repository.UserRepository;
import com.lms.auth.repository.RefreshTokenRepository;
import com.lms.auth.security.JwtTokenProvider;
import com.lms.klassci.service.KlassciIntegrationService;

import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;

import org.springframework.security.authentication.AuthenticationManager;
import org.springframework.security.authentication.UsernamePasswordAuthenticationToken;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.AuthenticationException;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.Optional;
import java.util.UUID;

@Slf4j
@Service
@RequiredArgsConstructor
@Transactional
public class AuthService {

    private final AuthenticationManager authenticationManager;
    private final JwtTokenProvider jwtTokenProvider;
    private final UserRepository userRepository;
    private final RefreshTokenRepository refreshTokenRepository;
    private final KlassciIntegrationService klassciIntegrationService;

    public AuthResponse authenticate(String email, String password) {
        try {
            // 1. Authentification via KLASSCI
            Optional<AuthenticationResponse> klassciAuth =
                klassciIntegrationService.authenticate(email, password);

            if (klassciAuth.isEmpty()) {
                throw new AuthenticationException("Échec de l'authentification KLASSCI") {};
            }

            // 2. Synchroniser l'utilisateur localement
            User user = syncUserFromKlassci(klassciAuth.get().getUser());

            // 3. Générer les tokens JWT
            String accessToken = jwtTokenProvider.generateAccessToken(user);
            String refreshToken = generateRefreshToken(user);

            // 4. Construire la réponse
            return AuthResponse.builder()
                .accessToken(accessToken)
                .refreshToken(refreshToken)
                .tokenType("Bearer")
                .expiresIn(jwtTokenProvider.getAccessTokenValidityInSeconds())
                .user(mapToUserInfo(user))
                .build();

        } catch (Exception e) {
            log.error("Erreur lors de l'authentification pour {}: {}", email, e.getMessage());
            throw new AuthenticationException("Identifiants incorrects") {};
        }
    }

    public AuthResponse refreshToken(String refreshTokenValue) {
        Optional<RefreshToken> refreshToken = refreshTokenRepository
            .findByTokenAndExpiryDateAfter(refreshTokenValue, LocalDateTime.now());

        if (refreshToken.isEmpty()) {
            throw new RuntimeException("Token de rafraîchissement invalide ou expiré");
        }

        User user = refreshToken.get().getUser();

        // Générer un nouveau access token
        String newAccessToken = jwtTokenProvider.generateAccessToken(user);

        // Optionnel: Générer un nouveau refresh token
        String newRefreshToken = generateRefreshToken(user);

        // Supprimer l'ancien refresh token
        refreshTokenRepository.delete(refreshToken.get());

        return AuthResponse.builder()
            .accessToken(newAccessToken)
            .refreshToken(newRefreshToken)
            .tokenType("Bearer")
            .expiresIn(jwtTokenProvider.getAccessTokenValidityInSeconds())
            .user(mapToUserInfo(user))
            .build();
    }

    public void logout(String token) {
        try {
            String username = jwtTokenProvider.getUsernameFromToken(token);
            User user = userRepository.findByEmail(username)
                .orElseThrow(() -> new RuntimeException("Utilisateur non trouvé"));

            // Supprimer tous les refresh tokens de l'utilisateur
            refreshTokenRepository.deleteByUser(user);

            // Optionnel: Blacklister le token JWT (nécessite Redis)
            // jwtTokenProvider.blacklistToken(token);

            log.info("Utilisateur {} déconnecté avec succès", username);

        } catch (Exception e) {
            log.error("Erreur lors de la déconnexion: {}", e.getMessage());
            throw new RuntimeException("Erreur lors de la déconnexion");
        }
    }

    public UserInfo getCurrentUser(String token) {
        String username = jwtTokenProvider.getUsernameFromToken(token);
        User user = userRepository.findByEmail(username)
            .orElseThrow(() -> new RuntimeException("Utilisateur non trouvé"));

        return mapToUserInfo(user);
    }

    public boolean validateToken(String token) {
        return jwtTokenProvider.validateToken(token);
    }

    private User syncUserFromKlassci(KlassciUser klassciUser) {
        Optional<User> existingUser = userRepository.findByEmail(klassciUser.getEmail());

        User user;
        if (existingUser.isPresent()) {
            user = existingUser.get();
            // Mise à jour des informations
            user.setNom(klassciUser.getNom());
            user.setPrenom(klassciUser.getPrenom());
            user.setRole(klassciUser.getRole());
            user.setPermissions(klassciUser.getPermissions());
            user.setLastSyncAt(LocalDateTime.now());
        } else {
            // Création d'un nouvel utilisateur
            user = User.builder()
                .email(klassciUser.getEmail())
                .nom(klassciUser.getNom())
                .prenom(klassciUser.getPrenom())
                .role(klassciUser.getRole())
                .permissions(klassciUser.getPermissions())
                .klassciId(klassciUser.getId())
                .isActive(true)
                .createdAt(LocalDateTime.now())
                .lastSyncAt(LocalDateTime.now())
                .build();
        }

        return userRepository.save(user);
    }

    private String generateRefreshToken(User user) {
        // Supprimer les anciens refresh tokens
        refreshTokenRepository.deleteByUser(user);

        RefreshToken refreshToken = RefreshToken.builder()
            .token(UUID.randomUUID().toString())
            .user(user)
            .expiryDate(LocalDateTime.now().plusDays(7)) // 7 jours
            .build();

        refreshTokenRepository.save(refreshToken);
        return refreshToken.getToken();
    }

    private UserInfo mapToUserInfo(User user) {
        return UserInfo.builder()
            .id(user.getId())
            .email(user.getEmail())
            .nom(user.getNom())
            .prenom(user.getPrenom())
            .role(user.getRole())
            .permissions(user.getPermissions())
            .profile(user.getProfile())
            .build();
    }
}
```

### **Service de Gestion des Cours avec JPA**

```java
// backend/course-service/src/main/java/com/lms/course/service/CourseService.java

package com.lms.course.service;

import com.lms.course.entity.Course;
import com.lms.course.entity.Lesson;
import com.lms.course.entity.Progress;
import com.lms.course.repository.CourseRepository;
import com.lms.course.repository.ProgressRepository;
import com.lms.course.dto.CourseCreateRequest;
import com.lms.course.dto.ProgressUpdateRequest;
import com.lms.klassci.service.KlassciIntegrationService;

import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;

import org.springframework.cache.annotation.Cacheable;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;
import java.util.Optional;

@Slf4j
@Service
@RequiredArgsConstructor
@Transactional
public class CourseService {

    private final CourseRepository courseRepository;
    private final ProgressRepository progressRepository;
    private final KlassciIntegrationService klassciIntegrationService;

    @Cacheable(value = "user-courses", key = "#userId + '_' + #role")
    public List<Course> getCoursesByUser(Long userId, String role) {
        try {
            log.info("Récupération des cours pour l'utilisateur {} avec le rôle {}", userId, role);

            if ("student".equals(role)) {
                return getStudentCourses(userId);
            } else if ("teacher".equals(role)) {
                return getTeacherCourses(userId);
            }

            return List.of();

        } catch (Exception e) {
            log.error("Erreur lors de la récupération des cours pour l'utilisateur {}: {}", userId, e.getMessage());
            throw new RuntimeException("Erreur récupération cours", e);
        }
    }

    private List<Course> getStudentCourses(Long studentId) {
        // Récupérer les classes de l'étudiant depuis KLASSCI
        List<KlassciClasse> classes = klassciIntegrationService.getStudentClasses(studentId);

        List<Course> courses = new ArrayList<>();

        for (KlassciClasse classe : classes) {
            for (KlassciMatiere matiere : classe.getMatieres()) {
                Optional<Course> course = courseRepository.findByKlassciClassIdAndKlassciSubjectId(
                    classe.getId(), matiere.getId()
                );

                if (course.isPresent()) {
                    // Ajouter les informations de progression
                    CourseProgress progress = getStudentProgress(studentId, course.get().getId());
                    course.get().setProgress(progress);
                    courses.add(course.get());
                }
            }
        }

        return courses;
    }

    private List<Course> getTeacherCourses(Long teacherId) {
        // Récupérer les cours assignés à l'enseignant
        return courseRepository.findByTeacherIdAndIsActiveTrue(teacherId);
    }

    public CourseProgress getStudentProgress(Long studentId, Long courseId) {
        List<Progress> progressRecords = progressRepository.findByStudentIdAndCourseId(studentId, courseId);
        Course course = courseRepository.findById(courseId)
            .orElseThrow(() -> new RuntimeException("Cours non trouvé"));

        int totalLessons = course.getLessons().size();
        int completedLessons = (int) progressRecords.stream()
            .filter(Progress::isCompleted)
            .count();

        double percentage = totalLessons > 0 ? (double) completedLessons / totalLessons * 100 : 0;
        int totalTimeSpent = progressRecords.stream()
            .mapToInt(Progress::getTimeSpent)
            .sum();

        LocalDateTime lastActivity = progressRecords.stream()
            .map(Progress::getLastAccessAt)
            .max(LocalDateTime::compareTo)
            .orElse(null);

        return CourseProgress.builder()
            .totalLessons(totalLessons)
            .completedLessons(completedLessons)
            .percentage(percentage)
            .totalTimeSpent(totalTimeSpent)
            .lastActivity(lastActivity)
            .currentLesson(findCurrentLesson(progressRecords, course.getLessons()))
            .build();
    }

    @Transactional
    public void updateProgress(Long studentId, Long courseId, Long lessonId, ProgressUpdateRequest request) {
        try {
            log.info("Mise à jour du progrès - étudiant: {}, cours: {}, leçon: {}",
                studentId, courseId, lessonId);

            Progress progress = progressRepository.findByStudentIdAndCourseIdAndLessonId(
                studentId, courseId, lessonId
            ).orElse(new Progress());

            progress.setStudentId(studentId);
            progress.setCourseId(courseId);
            progress.setLessonId(lessonId);
            progress.setProgress(request.getProgress());
            progress.setTimeSpent(request.getTimeSpent());
            progress.setCompleted(request.getProgress() >= 90); // Terminé à 90%
            progress.setLastAccessAt(LocalDateTime.now());
            progress.setMetadata(request.getMetadata());

            progressRepository.save(progress);

            // Déclencher des événements pour badges, notifications, etc.
            handleProgressEvents(studentId, courseId, request);

            log.info("Progrès mis à jour avec succès");

        } catch (Exception e) {
            log.error("Erreur lors de la mise à jour du progrès: {}", e.getMessage());
            throw new RuntimeException("Erreur mise à jour progrès", e);
        }
    }

    @Transactional
    public Course createCourse(CourseCreateRequest request) {
        try {
            log.info("Création d'un nouveau cours: {}", request.getTitle());

            Course course = Course.builder()
                .title(request.getTitle())
                .description(request.getDescription())
                .klassciSubjectId(request.getKlassciSubjectId())
                .klassciClassId(request.getKlassciClassId())
                .teacherId(request.getTeacherId())
                .thumbnail(request.getThumbnail())
                .status(CourseStatus.DRAFT)
                .isActive(true)
                .createdAt(LocalDateTime.now())
                .updatedAt(LocalDateTime.now())
                .build();

            Course savedCourse = courseRepository.save(course);

            // Créer les leçons si fournies
            if (request.getLessons() != null && !request.getLessons().isEmpty()) {
                createLessons(savedCourse, request.getLessons());
            }

            log.info("Cours créé avec succès - ID: {}", savedCourse.getId());
            return savedCourse;

        } catch (Exception e) {
            log.error("Erreur lors de la création du cours: {}", e.getMessage());
            throw new RuntimeException("Erreur création cours", e);
        }
    }

    private void createLessons(Course course, List<LessonCreateRequest> lessonRequests) {
        for (int i = 0; i < lessonRequests.size(); i++) {
            LessonCreateRequest lessonRequest = lessonRequests.get(i);

            Lesson lesson = Lesson.builder()
                .course(course)
                .title(lessonRequest.getTitle())
                .description(lessonRequest.getDescription())
                .type(lessonRequest.getType())
                .content(lessonRequest.getContent())
                .videoUrl(lessonRequest.getVideoUrl())
                .duration(lessonRequest.getDuration())
                .order(i + 1)
                .isActive(true)
                .createdAt(LocalDateTime.now())
                .build();

            course.getLessons().add(lesson);
        }

        courseRepository.save(course);
    }

    private void handleProgressEvents(Long studentId, Long courseId, ProgressUpdateRequest request) {
        // Badges et récompenses
        if (request.getProgress() >= 100) {
            // Déclencher événement de completion de leçon
            log.info("Leçon terminée par l'étudiant {}", studentId);
        }

        // Notifications
        if (request.getProgress() >= 90) {
            // Envoyer notification de fin de cours
            log.info("Notification de fin de cours pour l'étudiant {}", studentId);
        }

        // Analytics
        log.debug("Progrès enregistré: {}% pour l'étudiant {}", request.getProgress(), studentId);
    }

    private Lesson findCurrentLesson(List<Progress> progressRecords, List<Lesson> lessons) {
        // Logique pour trouver la leçon courante basée sur le progrès
        return lessons.stream()
            .filter(lesson -> progressRecords.stream()
                .noneMatch(p -> p.getLessonId().equals(lesson.getId()) && p.isCompleted()))
            .findFirst()
            .orElse(lessons.get(lessons.size() - 1)); // Dernière leçon si toutes sont terminées
    }
}
```

---

## 🚀 **Configuration Docker & Déploiement Enterprise**

### **Docker Compose pour Développement**

```yaml
# docker-compose.dev.yml
version: '3.8'

services:
  # Base de données PostgreSQL
  postgres:
    image: postgres:15-alpine
    container_name: lms-postgres
    environment:
      POSTGRES_DB: lms_dev
      POSTGRES_USER: lms_user
      POSTGRES_PASSWORD: lms_password
    ports:
      - "5432:5432"
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./database/init:/docker-entrypoint-initdb.d
    networks:
      - lms-network

  # Cache Redis
  redis:
    image: redis:7-alpine
    container_name: lms-redis
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    command: redis-server --appendonly yes
    networks:
      - lms-network

  # Service Discovery - Eureka
  eureka-server:
    build:
      context: ./backend/eureka-server
      dockerfile: Dockerfile
    container_name: lms-eureka
    ports:
      - "8761:8761"
    environment:
      - SPRING_PROFILES_ACTIVE=dev
    networks:
      - lms-network

  # Configuration Server
  config-server:
    build:
      context: ./backend/config-server
      dockerfile: Dockerfile
    container_name: lms-config
    ports:
      - "8888:8888"
    environment:
      - SPRING_PROFILES_ACTIVE=dev
      - EUREKA_CLIENT_SERVICE_URL_DEFAULTZONE=http://eureka-server:8761/eureka
    depends_on:
      - eureka-server
    networks:
      - lms-network

  # Service d'Authentification
  auth-service:
    build:
      context: ./backend/auth-service
      dockerfile: Dockerfile
    container_name: lms-auth-service
    ports:
      - "8081:8080"
    environment:
      - SPRING_PROFILES_ACTIVE=dev
      - EUREKA_CLIENT_SERVICE_URL_DEFAULTZONE=http://eureka-server:8761/eureka
      - SPRING_CONFIG_IMPORT=configserver:http://config-server:8888
      - SPRING_DATASOURCE_URL=jdbc:postgresql://postgres:5432/lms_dev
      - SPRING_DATASOURCE_USERNAME=lms_user
      - SPRING_DATASOURCE_PASSWORD=lms_password
      - SPRING_REDIS_HOST=redis
      - KLASSCI_API_BASE_URL=http://klassci.example.com/api
    depends_on:
      - postgres
      - redis
      - eureka-server
      - config-server
    networks:
      - lms-network

  # Service de Cours
  course-service:
    build:
      context: ./backend/course-service
      dockerfile: Dockerfile
    container_name: lms-course-service
    ports:
      - "8082:8080"
    environment:
      - SPRING_PROFILES_ACTIVE=dev
      - EUREKA_CLIENT_SERVICE_URL_DEFAULTZONE=http://eureka-server:8761/eureka
      - SPRING_CONFIG_IMPORT=configserver:http://config-server:8888
      - SPRING_DATASOURCE_URL=jdbc:postgresql://postgres:5432/lms_dev
      - SPRING_DATASOURCE_USERNAME=lms_user
      - SPRING_DATASOURCE_PASSWORD=lms_password
      - SPRING_REDIS_HOST=redis
    depends_on:
      - postgres
      - redis
      - eureka-server
      - config-server
    networks:
      - lms-network

  # Service d'Évaluation
  assessment-service:
    build:
      context: ./backend/assessment-service
      dockerfile: Dockerfile
    container_name: lms-assessment-service
    ports:
      - "8083:8080"
    environment:
      - SPRING_PROFILES_ACTIVE=dev
      - EUREKA_CLIENT_SERVICE_URL_DEFAULTZONE=http://eureka-server:8761/eureka
      - SPRING_CONFIG_IMPORT=configserver:http://config-server:8888
      - SPRING_DATASOURCE_URL=jdbc:postgresql://postgres:5432/lms_dev
      - SPRING_DATASOURCE_USERNAME=lms_user
      - SPRING_DATASOURCE_PASSWORD=lms_password
    depends_on:
      - postgres
      - eureka-server
      - config-server
    networks:
      - lms-network

  # Service d'Intégration KLASSCI
  klassci-integration-service:
    build:
      context: ./backend/klassci-integration-service
      dockerfile: Dockerfile
    container_name: lms-klassci-service
    ports:
      - "8084:8080"
    environment:
      - SPRING_PROFILES_ACTIVE=dev
      - EUREKA_CLIENT_SERVICE_URL_DEFAULTZONE=http://eureka-server:8761/eureka
      - SPRING_CONFIG_IMPORT=configserver:http://config-server:8888
      - KLASSCI_API_BASE_URL=http://klassci.example.com
      - KLASSCI_API_TOKEN=${KLASSCI_API_TOKEN}
      - SPRING_REDIS_HOST=redis
    depends_on:
      - redis
      - eureka-server
      - config-server
    networks:
      - lms-network

  # API Gateway
  api-gateway:
    build:
      context: ./backend/api-gateway
      dockerfile: Dockerfile
    container_name: lms-api-gateway
    ports:
      - "8080:8080"
    environment:
      - SPRING_PROFILES_ACTIVE=dev
      - EUREKA_CLIENT_SERVICE_URL_DEFAULTZONE=http://eureka-server:8761/eureka
      - SPRING_CONFIG_IMPORT=configserver:http://config-server:8888
      - SPRING_REDIS_HOST=redis
    depends_on:
      - eureka-server
      - config-server
      - auth-service
      - course-service
      - assessment-service
    networks:
      - lms-network

  # Frontend Angular - Shell App
  angular-shell:
    build:
      context: ./frontend/shell-app
      dockerfile: Dockerfile.dev
    container_name: lms-angular-shell
    ports:
      - "4200:4200"
    environment:
      - API_BASE_URL=http://localhost:8080
    volumes:
      - ./frontend/shell-app:/app
      - /app/node_modules
    networks:
      - lms-network

  # Monitoring - Prometheus
  prometheus:
    image: prom/prometheus:latest
    container_name: lms-prometheus
    ports:
      - "9090:9090"
    volumes:
      - ./infrastructure/monitoring/prometheus.yml:/etc/prometheus/prometheus.yml
      - prometheus_data:/prometheus
    command:
      - '--config.file=/etc/prometheus/prometheus.yml'
      - '--storage.tsdb.path=/prometheus'
      - '--web.console.libraries=/etc/prometheus/console_libraries'
      - '--web.console.templates=/etc/prometheus/consoles'
    networks:
      - lms-network

  # Monitoring - Grafana
  grafana:
    image: grafana/grafana:latest
    container_name: lms-grafana
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=admin123
    volumes:
      - grafana_data:/var/lib/grafana
      - ./infrastructure/monitoring/grafana:/etc/grafana/provisioning
    networks:
      - lms-network

volumes:
  postgres_data:
  redis_data:
  prometheus_data:
  grafana_data:

networks:
  lms-network:
    driver: bridge
```

### **Guide de Déploiement Production**

```bash
#!/bin/bash
# deploy-production.sh

set -e

echo "🚀 Déploiement LMS KLASSCI en Production"

# Variables d'environnement
ENVIRONMENT="prod"
NAMESPACE="lms-production"
DOMAIN="lms.votre-domaine.com"

# 1. Vérifications pré-déploiement
echo "🔍 Vérifications pré-déploiement..."

# Vérifier que toutes les variables sont définies
required_vars=(
    "KLASSCI_API_URL"
    "KLASSCI_API_TOKEN"
    "DB_PASSWORD"
    "JWT_SECRET"
    "REDIS_PASSWORD"
)

for var in "${required_vars[@]}"; do
    if [[ -z "${!var}" ]]; then
        echo "❌ Variable d'environnement manquante: $var"
        exit 1
    fi
done

echo "✅ Variables d'environnement validées"

# 2. Build des images Docker
echo "🔨 Construction des images Docker..."

# Backend services
docker build -t lms/auth-service:latest ./backend/auth-service
docker build -t lms/course-service:latest ./backend/course-service
docker build -t lms/assessment-service:latest ./backend/assessment-service
docker build -t lms/api-gateway:latest ./backend/api-gateway

# Frontend
docker build -t lms/angular-shell:latest ./frontend/shell-app

echo "✅ Images construites avec succès"

# 3. Push vers le registre Docker
echo "📦 Push vers le registre Docker..."

docker tag lms/auth-service:latest your-registry.com/lms/auth-service:latest
docker push your-registry.com/lms/auth-service:latest

docker tag lms/course-service:latest your-registry.com/lms/course-service:latest
docker push your-registry.com/lms/course-service:latest

echo "✅ Images pushées vers le registre"

# 4. Déploiement Kubernetes
echo "☸️ Déploiement Kubernetes..."

# Créer le namespace si nécessaire
kubectl create namespace ${NAMESPACE} --dry-run=client -o yaml | kubectl apply -f -

# Appliquer les configurations
kubectl apply -f ./infrastructure/kubernetes/configmaps/ -n ${NAMESPACE}
kubectl apply -f ./infrastructure/kubernetes/secrets/ -n ${NAMESPACE}
kubectl apply -f ./infrastructure/kubernetes/backend/ -n ${NAMESPACE}
kubectl apply -f ./infrastructure/kubernetes/frontend/ -n ${NAMESPACE}
kubectl apply -f ./infrastructure/kubernetes/ingress/ -n ${NAMESPACE}

echo "✅ Déploiement Kubernetes terminé"

# 5. Vérification des services
echo "🔍 Vérification des services..."

# Attendre que tous les pods soient prêts
kubectl wait --for=condition=ready pod -l app=auth-service -n ${NAMESPACE} --timeout=300s
kubectl wait --for=condition=ready pod -l app=course-service -n ${NAMESPACE} --timeout=300s
kubectl wait --for=condition=ready pod -l app=api-gateway -n ${NAMESPACE} --timeout=300s

echo "✅ Tous les services sont opérationnels"

# 6. Tests de santé
echo "🧪 Tests de santé..."

# Test de l'API Gateway
GATEWAY_URL="https://${DOMAIN}/api/health"
response=$(curl -s -o /dev/null -w "%{http_code}" ${GATEWAY_URL})

if [[ $response -eq 200 ]]; then
    echo "✅ API Gateway opérationnelle"
else
    echo "❌ API Gateway non disponible (HTTP $response)"
    exit 1
fi

# Test d'intégration KLASSCI
AUTH_URL="https://${DOMAIN}/api/auth/health"
response=$(curl -s -o /dev/null -w "%{http_code}" ${AUTH_URL})

if [[ $response -eq 200 ]]; then
    echo "✅ Service d'authentification opérationnel"
else
    echo "❌ Service d'authentification non disponible (HTTP $response)"
    exit 1
fi

echo "🎉 Déploiement terminé avec succès!"
echo "🌐 LMS accessible sur: https://${DOMAIN}"
echo "📊 Monitoring disponible sur: https://monitoring.${DOMAIN}"
```

---

## 🎯 **Conclusion et Prochaines Étapes**

### **Résumé de l'Architecture**

Cette architecture **Angular + Java Spring Boot** offre une solution enterprise complète pour un LMS intégré avec KLASSCI :

- **Frontend Micro Frontend** avec Angular 17+ et NgRx
- **Backend Microservices** avec Spring Boot et Spring Cloud
- **Intégration KLASSCI** robuste avec gestion d'erreurs et cache
- **Sécurité** avec JWT, Spring Security et validation d'accès
- **Scalabilité** avec architecture modulaire et conteneurisation
- **Monitoring** avec Prometheus, Grafana et logs centralisés

### **Phases d'Implémentation Recommandées**

1. **Phase 1 (4 semaines)** : Infrastructure et authentification
2. **Phase 2 (6 semaines)** : Services core et intégration KLASSCI
3. **Phase 3 (4 semaines)** : Frontend micro frontends et UX
4. **Phase 4 (2 semaines)** : Tests, monitoring et déploiement

### **Technologies Clés Utilisées**

- **Frontend** : Angular 17, NgRx, Angular Material, TypeScript
- **Backend** : Spring Boot 3, Spring Cloud, Spring Security, JPA/Hibernate
- **Base de données** : PostgreSQL, Redis
- **Infrastructure** : Docker, Kubernetes, Prometheus, Grafana
- **Intégration** : OpenFeign, Circuit Breaker, API Gateway

Cette architecture garantit une solution moderne, maintenable et évolutive pour répondre aux besoins enterprise du LMS intégré avec KLASSCI.
