<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ESBTPClasseController;
use App\Http\Controllers\ESBTPEtudiantController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*
 * Export securise de reinscription — SEULE surface non authentifiee de
 * l'application. Consomme par le site klassci.com, qui signe chaque appel avec
 * le secret partage de l'etablissement.
 *
 * La limitation de debit ne figure PAS ici, volontairement : Laravel trie la
 * pile d'intergiciels par `middlewarePriority`, ou `ThrottleRequests` figure,
 * si bien que l'ordre ecrit dans ce fichier n'est pas l'ordre d'execution — un
 * `throttle:` pose ici passait AVANT le garde, et comptait donc sur des champs
 * non authentifies. Elle vit desormais dans le garde, apres verification de la
 * signature. Voir PortailPublicGuard.
 *
 * `throttle:api` du groupe API est retire pour la meme famille de raisons : il
 * compte sur `$request->ip()`, qui vaut ici l'adresse de sortie du site
 * vitrine — partagee par toute l'ecole, et renouvelee a chaque demarrage a
 * froid chez l'hebergeur. Le laisser plafonnerait le portail a 60 requetes par
 * minute pour l'etablissement entier, et permettrait a un voisin d'hebergement
 * de fermer le canal.
 *
 * Le plancher de temps de reponse n'enveloppe que le traitement reel, pas les
 * refus : un refus rapide ne revele rien, et le faire attendre offrirait un
 * amplificateur de deni de service.
 */
Route::prefix('public/reinscription')
    ->withoutMiddleware(['throttle:api'])
    ->middleware(['portail.public', 'reinscription.plancher'])
    ->group(function () {
        Route::post('/lookup', [\App\Http\Controllers\API\Public\ReinscriptionPortalController::class, 'lookup'])
            ->name('api.public.reinscription.lookup');
        Route::post('/submit', [\App\Http\Controllers\API\Public\ReinscriptionPortalController::class, 'submit'])
            ->name('api.public.reinscription.submit');
    });

/*
 * Candidatures des NOUVEAUX etudiants. Meme garde, meme signature, meme fenetre
 * de dates — seul l'interrupteur differe, une ecole pouvant vouloir reinscrire
 * les siens sans ouvrir aux exterieurs, ou l'inverse.
 *
 * Pas de plancher de temps de reponse ici, et c'est deliberé : le plancher
 * existe pour rendre indistinguables « ce dossier existe » et « il n'existe
 * pas ». Une candidature ne consulte aucun dossier — il n'y a rien a
 * enumerer — donc rien a masquer, et faire attendre chaque envoi ne
 * protegerait personne tout en immobilisant un processus PHP.
 */
Route::prefix('public/inscription')
    ->withoutMiddleware(['throttle:api'])
    ->group(function () {
        // Le garde est declare POINT PAR POINT, et non sur le groupe : les
        // deux entrees ne pesent pas pareil, et un garde de groupe s'ajouterait
        // a celui de la route au lieu de le remplacer — chaque appel serait
        // alors compte deux fois, dans deux seaux differents.
        //
        // `catalogue` : /choix ne sert que des noms de filieres, de niveaux et
        // de nationalites — rien d'un etudiant, rien a enumerer. Il compte donc
        // dans un seau a part et large, sans quoi OUVRIR le formulaire couterait
        // autant que le deposer, et la file d'attente devant le formulaire
        // fermerait le canal des envois.
        Route::post('/choix', [\App\Http\Controllers\API\Public\CandidaturePortalController::class, 'choix'])
            ->middleware('portail.public:candidatures,catalogue')
            ->name('api.public.inscription.choix');
        Route::post('/submit', [\App\Http\Controllers\API\Public\CandidaturePortalController::class, 'submit'])
            ->middleware('portail.public:candidatures')
            ->name('api.public.inscription.submit');
    });

Route::prefix('public/rendez-vous')
    ->withoutMiddleware(['throttle:api'])
    ->group(function () {
        Route::post('/creneaux', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'creneaux'])
            ->middleware('portail.public:rendezvous,catalogue')
            ->name('api.public.rendez-vous.creneaux');
        Route::post('/reserver', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'reserver'])
            ->middleware('portail.public:rendezvous')
            ->name('api.public.rendez-vous.reserver');
        Route::post('/consulter', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'consulter'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.consulter');
        Route::post('/deplacer', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'deplacer'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.deplacer');
        Route::post('/annuler', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'annuler'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.annuler');
        Route::post('/retrouver', [\App\Http\Controllers\API\Public\RendezVousPortalController::class, 'retrouver'])
            ->middleware(['portail.public:rendezvous', 'reinscription.plancher'])
            ->name('api.public.rendez-vous.retrouver');
    });

/*
 * Identite publique de l'etablissement, lue par le site klassci.com.
 *
 * Le site affiche les logos des ecoles qu'il sert, et habille le formulaire
 * d'inscription aux couleurs de celle qu'on a choisie. Les deux viennent des
 * reglages que l'ecole a deja remplis pour ses documents PDF : elle ne
 * configure son identite qu'une fois, et elle vaut partout.
 *
 * Pas de signature, contrairement aux deux autres surfaces publiques : celles-
 * la parlent d'un etudiant, celle-ci ne parle que de l'etablissement, et ne
 * sert rien qu'il n'imprime deja en tete de chaque bulletin. `throttle:api` du
 * groupe suffit : il ne protege ici aucun secret, il empeche seulement qu'on
 * se serve du point d'entree comme hebergeur d'images.
 */
Route::prefix('public/etablissement')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\Public\EtablissementPublicController::class, 'show'])
        ->name('api.public.etablissement');
    Route::get('/logo', [\App\Http\Controllers\API\Public\EtablissementPublicController::class, 'logo'])
        ->name('api.public.etablissement.logo');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// MailPulse is the transport only. KLASSCI validates and answers parent requests itself.
Route::post('/v1/integrations/mailpulse/parent-chatbot/inbound', App\Http\Controllers\API\ParentChatbotInboundController::class)
    ->middleware('throttle:30,1')
    ->name('api.mailpulse.parent-chatbot.inbound');

// Throttled because the link is unauthenticated by design and each hit renders
// a full PDF: a link forwarded into a group chat must not become a CPU sink.
Route::get('/v1/parent-chatbot/report-cards/{bulletin}', App\Http\Controllers\ParentChatbotReportCardController::class)
    ->middleware(['signed', 'throttle:20,1'])
    ->name('parent-chatbot.report-card');

// Routes API pour ESBTP
//
// Ces trois adresses rendaient la structure academique complete de l ecole —
// classes, capacites, effectifs, filieres, niveaux — a qui la demandait, sans
// authentification, sur toutes les instances. Leurs seuls appelants etaient des
// pages de test laissees dans public/, supprimees en meme temps ; l application,
// elle, passe par les routes web equivalentes, qui sont protegees.
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/classes/{classe}/matieres', [ESBTPClasseController::class, 'getMatieresForApi'])
        ->name('api.classes.matieres');
});

// Routes pour le calcul des absences
Route::middleware(['auth:sanctum'])->prefix('absences')->group(function () {
    Route::post('/calculer', 'App\Http\Controllers\ESBTPCalculAbsencesController@calculerAbsencesEtudiant');
    Route::post('/resume-par-seance', 'App\Http\Controllers\ESBTPCalculAbsencesController@resumeAbsencesParSeance');
});

Route::middleware(['auth:sanctum'])->group(function () {
    // Attendance sync route
    Route::post('/attendance/sync', [App\Http\Controllers\ESBTP\Api\AttendanceSyncController::class, 'sync'])
        ->name('api.attendance.sync');
});

Route::middleware(['auth:sanctum'])->get('/classes/{id}/available-places', [ESBTPClasseController::class, 'getAvailablePlaces']);

Route::middleware(['auth:sanctum'])->post('/inscriptions/validate', [ESBTPEtudiantController::class, 'validateInscription'])->name('api.inscriptions.validate');

Route::middleware(['auth:sanctum'])->get('/classes', [ESBTPClasseController::class, 'indexApi']);

/*
|--------------------------------------------------------------------------
| API Routes LMS - KLASSCI Integration
|--------------------------------------------------------------------------
|
| Routes pour l'intégration entre le LMS et KLASSCI.
| Ces routes permettent au LMS d'accéder aux données KLASSCI
| et d'envoyer les résultats (notes, présences) vers KLASSCI.
|
*/

// Routes d'authentification LMS (sans middleware auth)
Route::prefix('lms/auth')->group(function () {
    // Meme limiteur que la connexion web : 5 essais par minute et par identifiant,
    // 10 par minute et par IP. Sans lui, cette porte n heritait que du plafond
    // general de 60 par minute — soit douze fois plus d essais de mot de passe
    // que par le formulaire, sur les memes comptes.
    Route::post('/login', [App\Http\Controllers\API\AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('api.lms.auth.login');
    Route::get('/documentation', [App\Http\Controllers\API\AuthController::class, 'documentation'])
        ->name('api.lms.auth.docs');

    // Découverte multi-tenant (rate-limited, sans auth)
    Route::middleware('throttle:lms-discovery')->group(function () {
        Route::post('/check-user', [App\Http\Controllers\API\AuthController::class, 'checkUser'])
            ->name('api.lms.auth.check-user');
        Route::post('/check-availability', [App\Http\Controllers\API\AuthController::class, 'checkAvailability'])
            ->name('api.lms.auth.check-availability');
    });
});

// Informations publiques du tenant (sans auth, rate-limited)
Route::middleware('throttle:api')->get('lms/tenant-info', [App\Http\Controllers\API\AuthController::class, 'tenantInfo'])
    ->name('api.lms.tenant-info');

// Routes LMS protégées par authentification Sanctum
Route::middleware(['auth:sanctum'])->prefix('lms')->name('api.lms.')->group(function () {

    // ================================
    // AUTHENTIFICATION & PROFIL
    // ================================
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/me', [App\Http\Controllers\API\AuthController::class, 'me']);
        Route::post('/logout', [App\Http\Controllers\API\AuthController::class, 'logout']);
        Route::post('/logout-all', [App\Http\Controllers\API\AuthController::class, 'logoutAll']);
        Route::get('/check', [App\Http\Controllers\API\AuthController::class, 'check']);
    });

    // ================================
    // DONNÉES EN LECTURE SEULE
    // ================================

    // Structure organisationnelle
    Route::get('/structure', [App\Http\Controllers\API\LMSDataController::class, 'structure'])
        ->name('structure');

    // Matières et cours
    Route::get('/matieres', [App\Http\Controllers\API\LMSDataController::class, 'matieres'])
        ->name('matieres');
    Route::get('/matieres/{matiereId}', [App\Http\Controllers\API\LMSDataController::class, 'matiereDetails'])
        ->name('matieres.details');

    // Classes et étudiants
    Route::get('/classes', [App\Http\Controllers\API\LMSDataController::class, 'classes'])
        ->name('classes');
    Route::get('/classes/{classeId}', [App\Http\Controllers\API\LMSDataController::class, 'classeDetails'])
        ->name('classes.details');
    Route::get('/classes/{classeId}/etudiants', [App\Http\Controllers\API\LMSDataController::class, 'etudiantsClasse'])
        ->name('classes.etudiants');

    // Enseignants actifs
    Route::get('/enseignants', [App\Http\Controllers\API\LMSDataController::class, 'enseignants'])
        ->name('enseignants');

    // KPIs pour le dashboard
    Route::get('/filieres', [App\Http\Controllers\API\LMSDataController::class, 'filieres'])
        ->name('filieres');
    Route::get('/niveaux-etudes', [App\Http\Controllers\API\LMSDataController::class, 'niveauxEtudes'])
        ->name('niveaux_etudes');

    // Emploi du temps
    Route::get('/emploi-temps', [App\Http\Controllers\API\LMSDataController::class, 'emploiTemps'])
        ->name('emploi_temps');

    // Évaluations programmées
    Route::get('/evaluations', [App\Http\Controllers\API\LMSDataController::class, 'evaluations'])
        ->name('evaluations');

    // Dashboard étudiant (réservé aux étudiants)
    Route::get('/me/dashboard', [App\Http\Controllers\API\LMSDataController::class, 'studentDashboard'])
        ->name('me.dashboard');

    // Dashboard enseignant (réservé aux enseignants)
    Route::get('/me/teacher-dashboard', [App\Http\Controllers\API\LMSDataController::class, 'teacherDashboard'])
        ->name('me.teacher-dashboard');

    // ================================
    // VISIOCONFÉRENCES (SUPPORT LMS)
    // ================================

    // Séances à venir pour créer les rooms
    Route::get('/seances/upcoming', [App\Http\Controllers\API\LMSDataController::class, 'upcomingSeances'])
        ->name('seances.upcoming');

    // Participants d'une séance
    Route::get('/seances/{seanceId}/participants', [App\Http\Controllers\API\LMSDataController::class, 'seanceParticipants'])
        ->name('seances.participants');

    // Valider un participant
    Route::post('/seances/{seanceId}/validate-participant', [App\Http\Controllers\API\LMSDataController::class, 'validateParticipant'])
        ->name('seances.validate-participant');

    // Sync attendances depuis visio (LMS → KLASSCI)
    Route::post('/attendances/from-video-session', [App\Http\Controllers\API\LMSDataController::class, 'syncVideoAttendances'])
        ->name('attendances.from-video-session');

    // ================================
    // NOTIFICATIONS
    // ================================

    // Envoyer rappels de séance
    Route::post('/notifications/send-session-reminder', [App\Http\Controllers\API\LMSDataController::class, 'sendSessionReminder'])
        ->name('notifications.send-session-reminder');

    // Récupérer préférences de notification
    Route::get('/notifications/preferences/{userId}', [App\Http\Controllers\API\LMSDataController::class, 'notificationPreferences'])
        ->name('notifications.preferences');

    // ================================
    // DONNÉES EN ÉCRITURE (LMS → KLASSCI)
    // ================================

    // Soumettre notes d'évaluations passées en ligne
    Route::post('/evaluations/{evaluationId}/notes', [App\Http\Controllers\API\LMSDataController::class, 'submitEvaluationNotes'])
        ->name('evaluations.notes.submit');

    // Présences cours en ligne
    Route::post('/cours/{coursId}/presences', [App\Http\Controllers\API\LMSWriteController::class, 'saveCourseAttendance'])
        ->name('cours.presences.save');

    // Statut des cours
    Route::put('/cours/{coursId}/statut', [App\Http\Controllers\API\LMSWriteController::class, 'updateCourseStatus'])
        ->name('cours.statut.update');
});

// ================================
// ROUTES DE DOCUMENTATION
// ================================
Route::get('/lms/documentation', function () {
    return response()->json([
        'title' => 'API LMS-KLASSCI Integration',
        'version' => '1.0.0',
        'description' => 'API pour l\'intégration entre le LMS et KLASSCI',
        'base_url' => url('/api/lms'),
        'authentication' => [
            'type' => 'Bearer Token (Laravel Sanctum)',
            'login_endpoint' => '/api/lms/auth/login',
            'header_format' => 'Authorization: Bearer {token}'
        ],
        'endpoints' => [
            'read_only' => [
                'GET /api/lms/structure' => 'Structure organisationnelle (filières, niveaux)',
                'GET /api/lms/matieres' => 'Liste des matières accessibles',
                'GET /api/lms/classes' => 'Classes de l\'année courante',
                'GET /api/lms/classes/{id}/etudiants' => 'Étudiants d\'une classe',
                'GET /api/lms/emploi-temps' => 'Emploi du temps filtré par rôle',
                'GET /api/lms/evaluations' => 'Évaluations programmées'
            ],
            'write_only' => [
                'POST /api/lms/evaluations/{id}/notes' => 'Sauvegarder notes d\'évaluation',
                'POST /api/lms/cours/{id}/presences' => 'Enregistrer présences cours',
                'PUT /api/lms/cours/{id}/statut' => 'Mettre à jour statut cours'
            ]
        ],
        'roles_supported' => ['enseignant', 'coordinateur', 'etudiant'],
        'data_scope' => 'Année universitaire courante uniquement',
        'contact' => [
            'team' => 'KLASSCI Development Team',
            'documentation' => url('/api/lms/auth/documentation')
        ]
    ]);
})->name('api.lms.documentation');

/*
|--------------------------------------------------------------------------
| CLI API Routes — KLASSCI Remote Management
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('cli')->name('api.cli.')->group(function () {
    // Read endpoints — Data & KPIs
    Route::get('/stats', [App\Http\Controllers\API\CLI\CLIDataController::class, 'stats'])->name('stats');
    Route::get('/classes', [App\Http\Controllers\API\CLI\CLIDataController::class, 'classes'])->name('classes');
    Route::get('/payments', [App\Http\Controllers\API\CLI\CLIDataController::class, 'payments'])->name('payments');
    Route::get('/relances', [App\Http\Controllers\API\CLI\CLIDataController::class, 'relances'])->name('relances');
    Route::get('/recouvrement', [App\Http\Controllers\API\CLI\CLIDataController::class, 'recouvrement'])->name('recouvrement');
    Route::get('/journal-caisse', [App\Http\Controllers\API\CLI\CLIDataController::class, 'journalCaisse'])->name('journal-caisse');
    Route::get('/audit-comptable', [App\Http\Controllers\API\CLI\CLIDataController::class, 'auditComptable'])->name('audit-comptable');
    Route::get('/settings', [App\Http\Controllers\API\CLI\CLIDataController::class, 'settings'])->name('settings');
    Route::get('/frais/bareme', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'bareme'])->name('frais.bareme');
    Route::get('/frais/soldes-inscription', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'soldesInscription'])->name('frais.soldes-inscription');
    Route::get('/personnel-scores', [App\Http\Controllers\API\CLI\CLIDataController::class, 'personnelScores'])->name('personnel-scores');
    Route::get('/academic-pilotage/diagnose', [App\Http\Controllers\API\CLI\CLIAcademicPilotageController::class, 'diagnose'])
        ->name('academic-pilotage.diagnose');

    // Read endpoints — Students & Inscriptions
    Route::get('/students', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'students'])->name('students');
    Route::get('/students/{id}', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'studentShow'])->name('students.show');
    Route::get('/inscriptions', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'inscriptions'])->name('inscriptions');
    Route::get('/resultats/etudiant/{id}/diagnose', [App\Http\Controllers\API\CLI\CLIResultatController::class, 'studentDiagnose'])
        ->name('resultats.student.diagnose');
    Route::get('/resultats/etudiant/{id}/bulletin-consistency-diagnose', [App\Http\Controllers\API\CLI\CLIResultatController::class, 'bulletinConsistencyDiagnose'])
        ->name('resultats.student.bulletin-consistency-diagnose');
    Route::get('/attendance/etudiant/{id}/absence-diagnose', [App\Http\Controllers\API\CLI\CLIAttendanceController::class, 'absenceDiagnose'])
        ->whereNumber('id')
        ->name('attendance.absence-diagnose');
    Route::post('/attendance/backfill-note-assiduite', [App\Http\Controllers\API\CLI\CLIAttendanceController::class, 'backfillNoteAssiduite'])
        ->name('attendance.backfill-note-assiduite');
    Route::post('/bulletins/recalculate-ranks', [App\Http\Controllers\API\CLI\CLIBulletinController::class, 'recalculateRanks'])
        ->name('bulletins.recalculate-ranks');
    Route::post('/bulletins/generate-missing', [App\Http\Controllers\API\CLI\CLIBulletinController::class, 'generateMissing'])
        ->name('bulletins.generate-missing');
    Route::post('/bulletins/backfill-averages', [App\Http\Controllers\API\CLI\CLIBulletinController::class, 'backfillAverages'])
        ->name('bulletins.backfill-averages');
    Route::post('/bulletins/backfill-subject-ranks', [App\Http\Controllers\API\CLI\CLIBulletinController::class, 'backfillSubjectRanks'])
        ->name('bulletins.backfill-subject-ranks');
    Route::get('/bts-tc/inscriptions/{id}/diagnose', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'diagnoseInscription'])
        ->name('bts-tc.inscriptions.diagnose');
    Route::get('/bts-tc/students/{id}/journey', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'studentJourney'])
        ->name('bts-tc.students.journey');
    Route::get('/bts-tc/students/{id}/results-consistency', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'resultsConsistency'])
        ->name('bts-tc.students.results-consistency');
    Route::get('/bts-tc/classes/{id}/orientation-check', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'classOrientationCheck'])
        ->name('bts-tc.classes.orientation-check');
    Route::get('/bts-tc/legacy-audit', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'legacyAudit'])
        ->name('bts-tc.legacy-audit');
    Route::get('/bts-tc/orientation-targets-audit', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'orientationTargetsAudit'])
        ->name('bts-tc.orientation-targets-audit');
    Route::get('/bts-tc/inscriptions/{id}/specialisation-integrity', [App\Http\Controllers\API\CLI\CLIBtsSpecialisationIntegrityController::class, 'diagnose'])
        ->whereNumber('id')
        ->name('bts-tc.inscriptions.specialisation-integrity');
    Route::post('/bts-tc/inscriptions/{id}/specialisation-integrity/repair', [App\Http\Controllers\API\CLI\CLIBtsSpecialisationIntegrityController::class, 'repair'])
        ->whereNumber('id')
        ->name('bts-tc.inscriptions.specialisation-integrity.repair');
    Route::post('/bts-tc/filieres/{id}/mark-tronc-commun', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'markFiliereTroncCommun'])
        ->name('bts-tc.filieres.mark-tronc-commun');
    Route::post('/bts-tc/classes/{id}/targets', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'addOrientationTarget'])
        ->name('bts-tc.classes.targets.store');
    Route::post('/bts-tc/inscriptions/{id}/orient', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'orientInscription'])
        ->name('bts-tc.inscriptions.orient');
    Route::post('/bts-tc/inscriptions/{id}/seed-academic-sample', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'seedAcademicSample'])
        ->name('bts-tc.inscriptions.seed-academic-sample');
    Route::post('/bts-tc/inscriptions/{id}/sync', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'syncInscription'])
        ->name('bts-tc.inscriptions.sync');
    Route::post('/bts-tc/sync-all', [App\Http\Controllers\API\CLI\CLIBtsTroncCommunController::class, 'syncAll'])
        ->name('bts-tc.sync-all');

    // Diagnose matière liaisons (filière+niveau pivot vs filieres/niveaux relations)
    Route::get('/matieres/diagnose-liaisons', [App\Http\Controllers\API\CLI\CLIMatiereController::class, 'diagnoseLiaisons'])
        ->name('matieres.diagnose-liaisons');

    // Inspecte les planifications académiques (matières d'une classe : filière+niveau+semestre)
    Route::get('/matieres/planifications', [App\Http\Controllers\API\CLI\CLIMatiereController::class, 'planifications'])
        ->name('matieres.planifications');

    // Nettoie les matières de spécialité rattachées par erreur à une classe tronc commun (dry-run par défaut)
    Route::post('/matieres/cleanup-tronc-commun', [App\Http\Controllers\API\CLI\CLIMatiereController::class, 'cleanupTroncCommun'])
        ->name('matieres.cleanup-tronc-commun');

    // Read endpoints — Academic years
    Route::get('/annee', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'annee'])->name('annee');
    Route::get('/evaluations/coverage', [App\Http\Controllers\API\CLI\CLIEvaluationCoverageController::class, 'index'])
        ->name('evaluations.coverage');

    // Read endpoints — Users
    Route::get('/users', [App\Http\Controllers\API\CLI\CLIUserController::class, 'users'])->name('users');

    // Paie enseignants — seed démo (taux profs + séances suivant le planning horaire)
    Route::post('/paie/seed-demo', [App\Http\Controllers\API\CLI\CLIPaieController::class, 'seedDemo'])->name('paie.seed-demo');

    // MailPulse: test notification vers destinataires de test uniquement
    Route::post('/mailpulse/test-notification', [App\Http\Controllers\API\CLI\CLIMailPulseController::class, 'testNotification'])
        ->middleware('throttle:10,1')
        ->name('mailpulse.test-notification');
    Route::post('/mailpulse/parent-chatbot/e2e/prepare', [App\Http\Controllers\API\CLI\CLIMailPulseController::class, 'prepareParentChatbotFixture'])
        ->middleware('throttle:10,1')
        ->name('mailpulse.parent-chatbot.e2e.prepare');
    Route::post('/mailpulse/parent-chatbot/e2e/inbound', [App\Http\Controllers\API\CLI\CLIMailPulseController::class, 'triggerParentChatbotInbound'])
        ->middleware('throttle:20,1')
        ->name('mailpulse.parent-chatbot.e2e.inbound');
    Route::post('/mailpulse/parent-chatbot/e2e/cleanup', [App\Http\Controllers\API\CLI\CLIMailPulseController::class, 'cleanupParentChatbotFixture'])
        ->middleware('throttle:10,1')
        ->name('mailpulse.parent-chatbot.e2e.cleanup');

    // Write endpoints
    Route::post('/inscriptions/{id}/validate', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'validateInscription'])->name('inscriptions.validate');
    Route::post('/inscriptions/move', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'moveStudents'])->name('inscriptions.move');
    Route::post('/inscriptions/validate-bulk', [App\Http\Controllers\API\CLI\CLIStudentController::class, 'bulkValidate'])->name('inscriptions.validate-bulk');

    // Analytics diagnose (read-only) — couverture échéancier, snapshots, saturation risque
    Route::get('/analytics/diagnose', [App\Http\Controllers\API\CLI\CLIDataController::class, 'analyticsDiagnose'])->name('analytics.diagnose');

    // Comptabilité (read-only) — audit + réconciliation diagnose
    Route::prefix('comptabilite')->name('comptabilite.')->group(function () {
        Route::get('/dashboard-kpis', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'dashboardKpis'])->name('dashboard-kpis');
        Route::get('/cash-balance', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'cashBalance'])->name('cash-balance');
        Route::get('/payments-summary', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'paymentsSummary'])->name('payments-summary');
        Route::get('/period-locks', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'periodLocks'])->name('period-locks');
        Route::get('/reconciliation-candidates', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationCandidates'])->name('reconciliation-candidates');
        Route::get('/orphan-paiements-annee-drift', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'orphanPaiementsAnneeDrift'])->name('orphan-paiements-annee-drift');
        Route::post('/cleanup-orphan-paiements', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'cleanupOrphanPaiements'])->name('cleanup-orphan-paiements');
        // PR1 réconciliation
        Route::get('/reconciliation/sessions', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationSessions'])->name('reconciliation.sessions');
        Route::get('/reconciliation/sessions/{id}', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationSessionShow'])->name('reconciliation.sessions.show');
        // PR3 réconciliation
        Route::get('/reconciliation/health', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationHealth'])->name('reconciliation.health');
        // PR6 réconciliation
        Route::get('/reconciliation/metrics', [App\Http\Controllers\API\CLI\CLIComptabiliteController::class, 'reconciliationMetrics'])->name('reconciliation.metrics');
    });

    // Permissions supervision (read-only) — registry-driven
    Route::get('/permissions', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'permissions'])->name('permissions.list');
    Route::get('/permissions/audit', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'audit'])->name('permissions.audit');
    Route::get('/roles', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'roles'])->name('roles.list');
    Route::post('/roles', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'roleStore'])->name('roles.store');
    Route::get('/roles/{role}', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'roleShow'])->name('roles.show');
    Route::post('/roles/{role}/grant', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'roleGrant'])->name('roles.grant');
    Route::post('/roles/{role}/revoke', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'roleRevoke'])->name('roles.revoke');

    // LMD hierarchy (read)
    Route::get('/lmd/tree', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'tree'])->name('lmd.tree');

    // Admin endpoints — throttled at 60/min (matches outer group; auth:sanctum + tokenCan('cli:admin')
    // already gates access. Higher throughput needed for bulk operations like LMD import.)
    Route::middleware('throttle:60,1')->group(function () {
        // Maintenance
        Route::get('/logs', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'logs'])->name('logs');
        Route::post('/cache/clear', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'cacheClear'])->name('cache.clear');
        Route::post('/logs/prune', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'logsPrune'])->name('logs.prune');
        Route::post('/permissions/fix', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'permissionsFix'])->name('permissions.fix');
        Route::post('/maintenance/reparer-encodage', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'reparerEncodage'])->name('maintenance.reparer-encodage');

        // Secrets d'integration. Liste blanche stricte cote controleur : ce
        // n'est PAS un ecrivain de .env generique, qui equivaudrait a une prise
        // de controle de l'instance par jeton. La valeur ne revient jamais.
        // Le moteur de base : version, jeu de caracteres, reglages qui decident
        // si une migration passe. Sans SSH, rien n exposait cette information, et
        // elle a bloque une decision de schema.
        Route::get('/moteur', [App\Http\Controllers\API\CLI\CLIMoteurController::class, 'index'])->name('moteur.index');

        Route::get('/env', [App\Http\Controllers\API\CLI\CLIEnvController::class, 'index'])->name('env.index');
        Route::post('/env', [App\Http\Controllers\API\CLI\CLIEnvController::class, 'store'])->name('env.store');
        Route::post('/permissions/sync', [App\Http\Controllers\API\CLI\CLIPermissionController::class, 'sync'])->name('permissions.sync');

        // Correction en masse d'un montant de souscription saisi par erreur.
        // Ne touche a rien sans `apply` : on ne corrige pas des montants sans
        // avoir regarde quels etudiants sont concernes.
        Route::get('/frais/montants-souscriptions', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'releverMontants'])->name('frais.montants-souscriptions');
        Route::post('/frais/appliquer-tenue-nouveaux', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'appliquerTenueNouveaux'])->name('frais.appliquer-tenue-nouveaux');
        Route::post('/frais/souscriptions-manquantes', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'souscriptionsManquantes'])->name('frais.souscriptions-manquantes');

        // Reprise de l'annee ecoulee d'une ecole qui arrive avec un arriere.
        // La donnee source voyage dans le corps de la requete : c'est un etat de
        // compte d'eleves reels, il n'a rien a faire dans le depot.
        Route::post('/reprise/inscriptions-annee-ecoulee', [App\Http\Controllers\API\CLI\CLIRepriseController::class, 'inscriptionsAnneeEcoulee'])->name('reprise.inscriptions-annee-ecoulee');
        Route::post('/frais/corriger-souscriptions', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'corrigerSouscriptions'])->name('frais.corriger-souscriptions');
        Route::post('/frais/repartir-trop-percu', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'repartirTropPercu'])->name('frais.repartir-trop-percu');
        Route::post('/frais/depot-nature/annuler', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'annulerDepotNature'])->name('frais.depot-nature.annuler');
        Route::get('/inscriptions/types', [App\Http\Controllers\API\CLI\CLIInscriptionTypeController::class, 'recenser'])->name('inscriptions.types');
        Route::get('/settings', [App\Http\Controllers\API\CLI\CLISettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [App\Http\Controllers\API\CLI\CLISettingsController::class, 'update'])->name('settings.update');
        Route::post('/inscriptions/normaliser-type', [App\Http\Controllers\API\CLI\CLIInscriptionTypeController::class, 'normaliser'])->name('inscriptions.normaliser-type');
        Route::post('/rendez-vous/generer', [App\Http\Controllers\API\CLI\CLIRendezVousController::class, 'generer'])->name('rendez-vous.generer');
        Route::post('/rendez-vous/placer', [App\Http\Controllers\API\CLI\CLIRendezVousController::class, 'placer'])->name('rendez-vous.placer');
        // L'ordre des categories est l'ordre dans lequel un versement solde les
        // frais. Le changer est une decision de l'ecole, pas du code.
        Route::post('/frais/ordonner-categories', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'ordonnerCategories'])->name('frais.ordonner-categories');
        Route::post('/frais/poser-bareme', [App\Http\Controllers\API\CLI\CLIFraisController::class, 'poserBareme'])->name('frais.poser-bareme');
        Route::post('/db/fix-duplicates', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'fixDuplicates'])->name('db.fix-duplicates');
        Route::post('/migrate', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'migrate'])->name('migrate');
        Route::post('/composer/install', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'composerInstall'])->name('composer.install');
        Route::post('/pull', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'pull'])->name('pull');
        Route::post('/seed-demo', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'seedDemo'])->name('seed-demo');
        Route::post('/evaluations/sync-notes', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'evaluationsSyncNotes'])->name('evaluations.sync-notes');
        // Deplacement de semestre decide par l'ecole, pas devine par le code :
        // la liste d'evaluations voyage dans la requete. Simulation par defaut.
        Route::post('/evaluations/deplacer-periode', [App\Http\Controllers\API\CLI\CLIEvaluationDeplacementController::class, 'deplacer'])
            ->name('evaluations.deplacer-periode');
        // Poser la note manquante aux eleves qu'une evaluation deja notee a
        // oublies, pour que la matiere cesse de disparaitre de leur bulletin.
        Route::post('/evaluations/noter-les-non-notes', [App\Http\Controllers\API\CLI\CLINotesZeroController::class, 'noter'])
            ->name('evaluations.noter-les-non-notes');
        Route::post('/academic-pilotage/backfill', [App\Http\Controllers\API\CLI\CLIAcademicPilotageController::class, 'backfill'])
            ->name('academic-pilotage.backfill');
        Route::post('/academic-pilotage/refresh', [App\Http\Controllers\API\CLI\CLIAcademicPilotageController::class, 'refresh'])
            ->name('academic-pilotage.refresh');
        // Snapshots d'echeancier : regeneration par lots (couverture analytics).
        // Meme moteur que `php artisan echeanciers:recompute`. dry_run=true par defaut.
        Route::post('/echeanciers/recompute', [App\Http\Controllers\API\CLI\CLIEcheancierController::class, 'recompute'])
            ->name('echeanciers.recompute');
        Route::get('/matieres/{matiere}/coefficient', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'matiereCoefficientLookup'])->name('matieres.coefficient');
        Route::get('/etudiants/{id}/inscriptions-diag', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'etudiantInscriptionsDiag'])->name('etudiants.inscriptions-diag');
        Route::get('/etudiants/{id}/inscriptions-repair-diagnostic', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'etudiantInscriptionRepairDiagnostic'])->name('etudiants.inscriptions-repair-diagnostic');
        Route::post('/etudiants/{id}/inscriptions-repair', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'repairEtudiantInscriptions'])->name('etudiants.inscriptions-repair');
        Route::get('/reinscription/eligible-diag', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'reinscriptionEligibleDiag'])->name('reinscription.eligible-diag');
        Route::get('/reinscription/batches', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'reinscriptionBatches'])->name('reinscription.batches');

        // LMD hierarchy (write — bulk setup of Domaine + Mention + Parcours + optional Filiere)
        Route::post('/lmd/setup', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'setup'])->name('lmd.setup');

        // LMD UE linking (idempotent — append by default, sync mode opt-in)
        Route::post('/lmd/parcours/{parcours}/link-ues', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'linkUes'])->name('lmd.link-ues');

        // LMD bulk import (Domaine + Mention + Parcours + Filière + UEs + ECUEs + Planifications)
        Route::post('/lmd/import', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'import'])->name('lmd.import');

        // LMD 360 E2E harness (presentation only, prepares data; business actions remain on web routes)
        Route::post('/lmd/jury-e2e/prepare', [App\Http\Controllers\API\CLI\CLILMDJuryE2EController::class, 'prepare'])->name('lmd.jury-e2e.prepare');
        Route::get('/lmd/jury-e2e/{jury}/status', [App\Http\Controllers\API\CLI\CLILMDJuryE2EController::class, 'status'])->name('lmd.jury-e2e.status');

        // LMD cleanup — soft-delete UE/ECUE/planifs d'un parcours pour ré-import propre
        // (dry_run par défaut SAFE, garde-fou évaluations). Idempotent.
        Route::post('/lmd/cleanup', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'cleanup'])->name('lmd.cleanup');

        // LMD link-classes — rattache des classes LMD à un parcours (parcours_id +
        // filiere_id dérivé + systeme=LMD). Domaine/Mention via parcours. Dry-run par défaut.
        Route::post('/lmd/link-classes', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'linkClasses'])->name('lmd.link-classes');

        // LMD bulk import — enseignants UEMOA (W1.3) — assigne Users + planifications
        // depuis JSONs `database/seeds-data/lmd-enseignants/*.json`. Throttle hérité
        // 60/min suffit (1 appel par filière ou 1 appel `all` par tenant).
        Route::post('/lmd/import-enseignants', [App\Http\Controllers\API\CLI\CLILMDSetupController::class, 'importEnseignants'])->name('lmd.import-enseignants');

        // Settings
        Route::put('/settings/{key}', [App\Http\Controllers\API\CLI\CLIDataController::class, 'settingsUpdate'])->name('settings.update');
        Route::post('/settings/{key}/image', [App\Http\Controllers\API\CLI\CLIDataController::class, 'settingsUploadImage'])->name('settings.upload-image');

        // Academic years
        Route::post('/annee/set/{id}', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'anneeSet'])->name('annee.set');
        Route::post('/annee/create', [App\Http\Controllers\API\CLI\CLIAcademicController::class, 'anneeCreate'])->name('annee.create');

        // Users
        Route::post('/user/{id}/reset-password-expiry', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userResetPasswordExpiry'])->name('user.reset-password-expiry');
        Route::post('/user/{id}/reset-password', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userResetPassword'])->name('user.reset-password');
        Route::get('/user/{id}/credentials', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userCredentials'])->name('user.credentials');
        Route::post('/user/create', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userCreate'])->name('user.create');
        Route::post('/user/{id}/delete', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userDelete'])->name('user.delete');
        // Changer le role d'un compte existant : le CLI ne savait l'attribuer
        // qu'a la creation, corriger imposait un acces web superAdmin.
        Route::get('/user/{id}/permissions', [App\Http\Controllers\API\CLI\CLIUserPermissionController::class, 'index'])->name('user.permissions.index');
        Route::post('/user/{id}/permissions', [App\Http\Controllers\API\CLI\CLIUserPermissionController::class, 'update'])->name('user.permissions.update');
        Route::post('/user/{id}/role', [App\Http\Controllers\API\CLI\CLIUserController::class, 'userSetRole'])->name('user.set-role');

        // Filieres — ouverture d'un nouveau tenant sans passer par l'interface.
        Route::get('/filieres', [App\Http\Controllers\API\CLI\CLIFiliereController::class, 'index'])->name('filieres.index');
        Route::post('/filieres', [App\Http\Controllers\API\CLI\CLIFiliereController::class, 'store'])->name('filieres.store');

        // Niveaux d'etudes — pendant des filieres pour l'ouverture d'un tenant.
        Route::get('/niveaux', [App\Http\Controllers\API\CLI\CLINiveauEtudeController::class, 'index'])->name('niveaux.index');
        Route::post('/niveaux', [App\Http\Controllers\API\CLI\CLINiveauEtudeController::class, 'store'])->name('niveaux.store');
        Route::post('/classes', [App\Http\Controllers\API\CLI\CLIClasseController::class, 'store'])->name('classes.store');

        // Diagnostic en lecture seule : evaluations dont la nature de la
        // matiere ne suit pas le systeme academique de la classe.
        Route::get('/diagnostics/evaluations-periode', [App\Http\Controllers\API\CLI\CLIEvaluationPeriodeController::class, 'index'])
            ->name('diagnostics.evaluations-periode');
        Route::post('/diagnostics/evaluations-periode/repair', [App\Http\Controllers\API\CLI\CLIEvaluationPeriodeController::class, 'repair'])
            ->name('diagnostics.evaluations-periode.repair');
        Route::get('/diagnostics/bulletins', [App\Http\Controllers\API\CLI\CLIBulletinDiagnosticController::class, 'index'])
            ->name('diagnostics.bulletins');
        Route::get('/diagnostics/bulletins/config', [App\Http\Controllers\API\CLI\CLIBulletinDiagnosticController::class, 'configCoverage'])
            ->name('diagnostics.bulletins.config');
        Route::get('/diagnostics/bulletin/{id}', [App\Http\Controllers\API\CLI\CLIBulletinDiagnosticController::class, 'show'])
            ->name('diagnostics.bulletin');
        Route::get('/diagnostics/settings-duplicates', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'settingsDuplicates'])
            ->name('diagnostics.settings-duplicates');
        Route::get('/diagnostics/evaluation-system-mismatch', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'evaluationSystemMismatch'])
            ->name('diagnostics.evaluation-system-mismatch');
        // Reparation ciblee : deplacer une evaluation vers la matiere du bon
        // systeme academique. N'accepte que les mouvements qui retablissent
        // la coherence, jamais ceux qui la rompent.
        Route::post('/evaluations/{id}/matiere', [App\Http\Controllers\API\CLI\CLIMaintenanceController::class, 'evaluationChangeMatiere'])
            ->name('evaluations.change-matiere');
    });
});
