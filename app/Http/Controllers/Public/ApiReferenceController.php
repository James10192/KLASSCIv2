<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class ApiReferenceController extends Controller
{
    /**
     * Render the public API LMS Reference page.
     *
     * Static catalog of public-facing /api/lms/* endpoints — kept in PHP
     * (not auto-generated from routes/api.php) so we control exactly what
     * is published. Internal /api/cli/* admin endpoints are NEVER exposed.
     */
    public function show()
    {
        return response()
            ->view('public.api-reference', [
                'sections' => $this->endpointCatalog(),
            ])
            ->withHeaders([
                'Cache-Control' => 'public, max-age=3600',
                'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
            ]);
    }

    /**
     * @return array<int, array{
     *     anchor: string,
     *     title: string,
     *     description: string,
     *     auth: string,
     *     endpoints: array<int, array{
     *         method: string,
     *         path: string,
     *         summary: string,
     *         description: string,
     *         params?: array<int, array{name: string, in: string, type: string, required: bool, description: string}>,
     *         response?: string,
     *         notes?: string
     *     }>
     * }>
     */
    private function endpointCatalog(): array
    {
        return [
            [
                'anchor' => 'discovery',
                'title' => 'Découverte multi-tenant',
                'description' => "Endpoints publics permettant à un client LMS de découvrir l'établissement KLASSCI auquel il se connecte. Pas de token requis. Rate-limiting strict pour prévenir l'énumération.",
                'auth' => 'public',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/tenant-info',
                        'summary' => "Informations publiques de l'établissement",
                        'description' => "Retourne le nom de l'établissement, son code tenant, ses logos publics et son fuseau horaire. Utile pour personnaliser l'écran de connexion d'une application cliente.",
                        'response' => "{ \"tenant_code\": \"esbtp-abidjan\", \"name\": \"ESBTP Abidjan\", \"logo_url\": \"...\", \"timezone\": \"Africa/Abidjan\" }",
                        'notes' => 'Rate-limit `api` (60 req/min/IP).',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/auth/check-user',
                        'summary' => "Vérifier qu'un utilisateur existe",
                        'description' => "Vérifie la présence d'un username/email côté KLASSCI sans révéler le rôle ni d'autre information sensible. Réponse minimaliste : `exists: true|false`.",
                        'params' => [
                            ['name' => 'identifier', 'in' => 'body', 'type' => 'string', 'required' => true, 'description' => "Username ou email à vérifier"],
                        ],
                        'response' => "{ \"exists\": true }",
                        'notes' => 'Rate-limit `lms-discovery` (10 req/min/IP). Aucun champ rôle ou statut retourné.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/auth/check-availability',
                        'summary' => "Health-check du tenant",
                        'description' => "Vérifie que le tenant KLASSCI est joignable et opérationnel. Renvoie l'année universitaire courante et le statut des modules activés.",
                        'response' => "{ \"available\": true, \"current_year\": \"2025-2026\", \"modules\": [...] }",
                        'notes' => 'Rate-limit `lms-discovery`.',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/auth/documentation',
                        'summary' => "Document de référence machine-readable",
                        'description' => "Retourne un document JSON décrivant les endpoints disponibles. Utile pour générer dynamiquement un client typé.",
                    ],
                ],
            ],

            [
                'anchor' => 'authentification',
                'title' => 'Authentification',
                'description' => "Flow Sanctum standard. Récupération d'un Bearer token via login, puis utilisation du header `Authorization: Bearer {token}` sur toutes les requêtes protégées.",
                'auth' => 'public-then-bearer',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/auth/login',
                        'summary' => "Authentifier un utilisateur et émettre un token",
                        'description' => "Accepte un `identifier` (username ou email) et un `password`. En cas de succès, retourne un token Sanctum à utiliser sur les routes protégées. Le token n'expire pas tant qu'il n'est pas révoqué via `/logout`.",
                        'params' => [
                            ['name' => 'identifier', 'in' => 'body', 'type' => 'string', 'required' => true, 'description' => 'Username ou email'],
                            ['name' => 'password', 'in' => 'body', 'type' => 'string', 'required' => true, 'description' => 'Mot de passe en clair (transmis sur HTTPS uniquement)'],
                            ['name' => 'device_name', 'in' => 'body', 'type' => 'string', 'required' => false, 'description' => "Nom optionnel pour identifier le device dans la table `personal_access_tokens`"],
                        ],
                        'response' => "{ \"token\": \"1|abc...xyz\", \"user\": { \"id\": 12, \"name\": \"...\", \"role\": \"enseignant\" } }",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/auth/me',
                        'summary' => "Profil de l'utilisateur authentifié",
                        'description' => "Retourne les informations du compte associé au token. Utile pour valider que le token est encore actif et récupérer le rôle de l'utilisateur.",
                        'auth' => 'bearer',
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/auth/check',
                        'summary' => "Sonde de validité du token",
                        'description' => "Léger health-check : retourne 200 si le token est valide, 401 sinon.",
                        'auth' => 'bearer',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/auth/logout',
                        'summary' => "Révoquer le token courant",
                        'description' => "Supprime le token utilisé pour la requête. Les autres tokens du même utilisateur restent actifs.",
                        'auth' => 'bearer',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/auth/logout-all',
                        'summary' => "Révoquer tous les tokens de l'utilisateur",
                        'description' => "Supprime tous les Sanctum tokens du compte. Utile en cas de compromission ou de changement de mot de passe.",
                        'auth' => 'bearer',
                    ],
                ],
            ],

            [
                'anchor' => 'structure',
                'title' => 'Structure académique',
                'description' => "Lecture seule. Filières, niveaux d'étude, classes et matières de l'année universitaire courante.",
                'auth' => 'bearer',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/structure',
                        'summary' => "Vue d'ensemble organisationnelle",
                        'description' => "Retourne l'arborescence complète : filières → niveaux → classes pour l'année courante. Utile pour initialiser un menu de navigation côté client.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/filieres',
                        'summary' => 'Liste des filières',
                        'description' => "Retourne toutes les filières actives de l'établissement avec leur code, leur système académique (BTS/LMD) et leur indicateur de tronc commun.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/niveaux-etudes',
                        'summary' => "Liste des niveaux d'étude",
                        'description' => "Retourne tous les niveaux d'étude (BTS1, BTS2, L1, L2, L3, M1, M2) avec leur cycle parent.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/matieres',
                        'summary' => 'Matières accessibles à l\'utilisateur',
                        'description' => "Retourne les matières filtrées par rôle : un enseignant ne voit que ses matières, un étudiant celles de sa classe, un coordinateur celles de sa filière. Pour le LMD, inclut le rattachement UE.",
                        'params' => [
                            ['name' => 'classe_id', 'in' => 'query', 'type' => 'int', 'required' => false, 'description' => 'Filtrer par classe (selon les permissions)'],
                        ],
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/matieres/{id}',
                        'summary' => "Détail d'une matière",
                        'description' => "Retourne le coefficient, le volume horaire, le cycle de la matière, et la liste des évaluations programmées sur l'année courante.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/classes',
                        'summary' => 'Liste des classes accessibles',
                        'description' => "Retourne les classes filtrées par rôle. Inclut le nombre d'étudiants inscrits (workflow_step = etudiant_cree uniquement).",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/classes/{id}',
                        'summary' => "Détail d'une classe",
                        'description' => "Retourne les caractéristiques de la classe, son effectif réel, ses places restantes, ses matières et son emploi du temps.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/classes/{id}/etudiants',
                        'summary' => 'Étudiants inscrits dans une classe',
                        'description' => "Retourne uniquement les étudiants avec `workflow_step = etudiant_cree` (inscriptions validées). Les pré-inscriptions et brouillons ne sont jamais exposés.",
                        'notes' => "L'utilisateur doit avoir une permission de lecture sur la classe ciblée (vérifié via Policy).",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/enseignants',
                        'summary' => 'Liste des enseignants actifs',
                        'description' => "Retourne les enseignants avec leur nom, leur matricule et les matières qu'ils encadrent. Le numéro de téléphone et l'email ne sont retournés qu'aux administrateurs.",
                    ],
                ],
            ],

            [
                'anchor' => 'planning-evaluations',
                'title' => 'Planning et évaluations',
                'description' => "Lecture seule. Emploi du temps, séances de cours et évaluations programmées.",
                'auth' => 'bearer',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/emploi-temps',
                        'summary' => 'Emploi du temps filtré par rôle',
                        'description' => "Pour un étudiant, retourne les séances de sa classe. Pour un enseignant, ses séances. Pour un coordinateur, celles de sa filière. Format compatible iCalendar (date, heure début/fin, salle, matière).",
                        'params' => [
                            ['name' => 'date_debut', 'in' => 'query', 'type' => 'date', 'required' => false, 'description' => 'Borne inférieure (ISO 8601)'],
                            ['name' => 'date_fin', 'in' => 'query', 'type' => 'date', 'required' => false, 'description' => 'Borne supérieure (ISO 8601)'],
                        ],
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/evaluations',
                        'summary' => 'Évaluations programmées',
                        'description' => "Retourne les évaluations à venir (devoir, partiel, examen final) accessibles à l'utilisateur. Inclut la date, la matière, le coefficient et le type.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/me/dashboard',
                        'summary' => "Dashboard étudiant (rôle étudiant uniquement)",
                        'description' => "Retourne un résumé personnalisé : prochaines séances, dernières notes, taux de présence, solde de scolarité, alertes éventuelles.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/me/teacher-dashboard',
                        'summary' => 'Dashboard enseignant (rôle enseignant uniquement)',
                        'description' => "Retourne les KPIs enseignant : prochaines séances à émarger, évaluations à corriger, classes encadrées, taux de présence moyen sur les cours dispensés.",
                    ],
                ],
            ],

            [
                'anchor' => 'visioconferences',
                'title' => 'Visioconférences (sync LMS → KLASSCI)',
                'description' => "Endpoints permettant à un LMS externe de gérer les rooms vidéo et de remonter les présences vidéo dans KLASSCI.",
                'auth' => 'bearer',
                'endpoints' => [
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/seances/upcoming',
                        'summary' => 'Séances à venir éligibles à une room vidéo',
                        'description' => "Retourne les séances de cours qui doivent commencer prochainement et pour lesquelles le LMS doit créer une salle de visioconférence.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/seances/{id}/participants',
                        'summary' => "Participants attendus à une séance",
                        'description' => "Retourne la liste des étudiants inscrits à la classe de la séance (uniquement ceux dont l'inscription est validée).",
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/seances/{id}/validate-participant',
                        'summary' => "Valider la connexion d'un participant",
                        'description' => "Le LMS appelle cet endpoint quand un étudiant rejoint la room vidéo. KLASSCI vérifie qu'il fait bien partie de la classe et retourne ok/refusé.",
                        'params' => [
                            ['name' => 'user_id', 'in' => 'body', 'type' => 'int', 'required' => true, 'description' => "ID utilisateur KLASSCI"],
                        ],
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/attendances/from-video-session',
                        'summary' => 'Synchroniser les présences depuis une session vidéo',
                        'description' => "À la fin d'une session vidéo, le LMS pousse la liste des participants effectifs avec leur durée de présence. KLASSCI crée les enregistrements de présence correspondants.",
                        'params' => [
                            ['name' => 'seance_id', 'in' => 'body', 'type' => 'int', 'required' => true, 'description' => 'Identifiant KLASSCI de la séance'],
                            ['name' => 'participants', 'in' => 'body', 'type' => 'array', 'required' => true, 'description' => "Tableau d'objets `{ user_id, joined_at, left_at }`"],
                        ],
                    ],
                ],
            ],

            [
                'anchor' => 'ecriture',
                'title' => "Données en écriture",
                'description' => "Endpoints permettant au LMS d'écrire dans KLASSCI : notes des évaluations passées en ligne, présences des cours en ligne, mise à jour du statut d'un cours.",
                'auth' => 'bearer',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/evaluations/{id}/notes',
                        'summary' => "Soumettre les notes d'une évaluation",
                        'description' => "Le LMS pousse les notes obtenues à une évaluation passée en ligne. KLASSCI met à jour les notes existantes ou les crée. Les permissions sont vérifiées : seul le LMS authentifié comme enseignant de la matière peut écrire.",
                        'params' => [
                            ['name' => 'notes', 'in' => 'body', 'type' => 'array', 'required' => true, 'description' => "Tableau d'objets `{ etudiant_id, note, commentaire }`"],
                        ],
                        'notes' => 'Idempotent : envoyer la même note 2 fois ne crée pas de doublon.',
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/cours/{id}/presences',
                        'summary' => 'Enregistrer les présences à un cours',
                        'description' => "Crée ou met à jour les présences pour une séance de cours en ligne. Réservé aux enseignants ou administrateurs autorisés sur la séance.",
                    ],
                    [
                        'method' => 'PUT',
                        'path' => '/api/lms/cours/{id}/statut',
                        'summary' => "Mettre à jour le statut d'un cours",
                        'description' => "Marque une séance comme `terminee` (avec heure de fin réelle), `annulee` ou `reportee`. Met à jour le suivi des heures réalisées.",
                    ],
                ],
            ],

            [
                'anchor' => 'notifications',
                'title' => 'Notifications',
                'description' => "Envoi de rappels et lecture des préférences de notification.",
                'auth' => 'bearer',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/lms/notifications/send-session-reminder',
                        'summary' => 'Envoyer un rappel de séance',
                        'description' => "Déclenche l'envoi d'un email/notification aux participants d'une séance à venir. Utilisé par le LMS pour automatiser les rappels avant un cours en ligne.",
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/lms/notifications/preferences/{userId}',
                        'summary' => "Préférences de notification d'un utilisateur",
                        'description' => "Retourne le détail des préférences de notification (email/SMS/push) de l'utilisateur ciblé. Accessible uniquement si l'appelant a la permission de gérer cet utilisateur.",
                    ],
                ],
            ],
        ];
    }
}
