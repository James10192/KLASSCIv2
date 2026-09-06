<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Grade;
use App\Models\Message;
use App\Models\Student;
use App\Models\Timetable;
use App\Models\User;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPEmploiTemps;
use App\Models\ESBTPAnnonce;
use App\Models\ESBTPSeanceCours;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPNote;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPMessage;
use App\Models\ESBTPStudent;
use App\Models\ESBTPAcademicYear;
use App\Models\ESBTPExam;
use App\Models\ESBTPGrade;
use App\Models\ESBTPSchedule;
use App\Models\ESBTPInscription;
use App\Models\ESBTPTeacher;
use App\Models\ESBTPSystemSetting;
use App\Models\ESBTPEtablissement;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPReliquatDetail;
use App\Models\ESBTPInscriptionEcheancierSnapshot;
use App\Models\ESBTPLMDResultatUE;
use App\Enums\JustificationStatus;
use App\Helpers\SettingsHelper;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use App\Domain\Students\StudentCountService;
use App\Services\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Constructeur qui applique le middleware auth.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Affiche le tableau de bord principal en fonction du rôle de l'utilisateur.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        // Lot 9 — Si l'utilisateur n'a QUE des rôles custom (créés via UI Lot 8),
        // pas de dashboard hard-codé : router vers le dashboard widget-based.
        // Les rôles système (superAdmin, secretaire...) gardent leur dashboard
        // dédié pour préserver l'UX existante.
        if ($this->userHasOnlyCustomRoles($user)) {
            return redirect()->route('dashboard.widgets.index');
        }

        // SuperAdmin/Admin en premier — ils ont TOUTES les permissions,
        // donc tout check permission-based matcherait. On utilise hasRole()
        // car c'est du routing UI par rôle (pattern validé post-overhaul).
        if ($user->hasRole(['superAdmin', 'admin'])) {
            return $this->superAdminDashboard();
        }

        // Service technique — a aussi toutes les permissions, donc hasRole requis
        if ($user->hasRole('serviceTechnique')) {
            return $this->serviceTechniqueDashboard();
        }

        // Responsable scolarite before secretaire: dedicated dashboard, no amounts.
        if ($user->can('identity.registrar')) {
            return redirect()->route('dashboard.responsable-scolarite');
        }

        // Service scolarite before secretaire: print queue + notes window.
        if ($user->can('identity.registrar_clerk')) {
            return redirect()->route('dashboard.service-scolarite');
        }

        // Agent d'inscription before secretaire: dedicated file, no amounts.
        if ($user->can('identity.enrollment_officer')) {
            return redirect()->route('dashboard.agent-inscription');
        }

        if ($user->can('identity.communicate')) {
            return redirect()->route('dashboard.communication');
        }

        // Secrétaire
        if ($user->can('identity.school_manager')) {
            return $this->secretaireDashboard();
        }

        // Caissier
        if ($user->can('module.caisse.access')) {
            return $this->caissierDashboard();
        }

        // Comptable
        if ($user->can('comptabilite.access')) {
            return $this->comptableDashboard();
        }

        // Directeur des études — before coordinateur because the role also
        // carries identity.coordinate to reuse academic gates.
        if ($user->can('identity.direct_studies')) {
            return redirect()->route('dashboard.directeur-etudes');
        }

        // Coordinateur
        if ($user->can('identity.coordinate')) {
            return $this->coordinateurDashboard();
        }

        // Enseignant
        if ($user->can('identity.teach')) {
            return redirect()->route('teacher.dashboard');
        }

        // Étudiant
        if ($user->can('identity.student')) {
            return $this->etudiantDashboard();
        }

        // Fallback — tableau de bord générique
        return view('dashboard.index', compact('user'));
    }

    /**
     * Lot 9 — Détecte si l'utilisateur n'a que des rôles custom (créés via UI Lot 8).
     *
     * Si oui, on n'a pas de dashboard hard-codé pour eux : router vers le
     * dashboard widget-based (config/dashboard_widgets.php).
     *
     * Retourne false si l'utilisateur a au moins un rôle système (gestion legacy)
     * ou aucun rôle (fallback générique).
     */
    private function userHasOnlyCustomRoles($user): bool
    {
        $roleNames = $user->roles->pluck('name')->all();
        if (empty($roleNames)) {
            return false;
        }

        $registry = app(PermissionRegistry::class);
        // Si la méthode roleIsCustom n'existe pas (Lot 8 pas encore mergé),
        // on revient au comportement legacy (false → continue le routing standard).
        if (! method_exists($registry, 'roleIsCustom')) {
            return false;
        }

        foreach ($roleNames as $name) {
            if (! $registry->roleIsCustom($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tableau de bord pour les super administrateurs avec toutes les permissions.
     */
    private function superAdminDashboard()
    {
        $user = Auth::user();
        // La vue superadmin ne lit que douze variables. Tout ce qui est calculé ici
        // sans être affiché est payé à chaque ouverture du tableau de bord, sur des
        // bases qui dépassent 2000 inscrits — d'où le tri strict de cette méthode.
        $data = [
            'user' => $user,
        ];

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $data['anneeEnCours'] = $anneeEnCours;

        // Inscriptions en attente - inclut les actives dont le workflow n'est pas finalisé (etudiant_cree)
        $pendingQuery = \App\Models\ESBTPInscription::where(function ($q) {
            $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                $subQ->where('status', 'active')
                    ->where(function ($wq) {
                        $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                            ->orWhereNull('workflow_step');
                    });
            });
        });
        if ($anneeEnCours) {
            $pendingQuery->where('annee_universitaire_id', $anneeEnCours->id);
        }
        $data['pendingInscriptionsCount'] = $pendingQuery->count();

        // Le rappel « inscriptions en attente » est rendu par layouts/app.blade.php, dont
        // le bloc @php réassigne lui-même pendingCurrentYearInscriptionsCount et
        // ...ByStep avant de les afficher. Les quatre comptages posés ici étaient donc
        // écrasés à chaque rendu : ils n'atteignaient jamais l'écran.

        // Étudiants — Service centralisé (distinct etudiant_id, inscriptions actives+validées année courante)
        $studentCounts = app(StudentCountService::class)->counts();
        $data['totalStudents'] = $studentCounts['inscrits_annee_courante'];
        $data['totalStudentsBase'] = $studentCounts['total_base'];
        $data['anneeLabel'] = $studentCounts['annee_courante_label'];

        // Filières
        try {
            $data['totalFilieres'] = ESBTPFiliere::count();
        } catch (\Exception $e) {
            $data['totalFilieres'] = 0;
        }

        // Classes (pas de filtrage par année)
        try {
            $data['totalClasses'] = ESBTPClasse::count();
        } catch (\Exception $e) {
            $data['totalClasses'] = 0;
        }

        // Matières
        try {
            $data['totalMatieres'] = ESBTPMatiere::count();
        } catch (\Exception $e) {
            $data['totalMatieres'] = 0;
        }

        // Enseignants
        try {
            $data['totalTeachers'] = ESBTPTeacher::count();
        } catch (\Exception $e) {
            $data['totalTeachers'] = 0;
        }

        // Ici vivaient les compteurs d'examens, bulletins, notes, présences, emplois du
        // temps, séances, ainsi que les messages et notifications récents. Aucun n'est lu
        // par la vue superadmin ni par le gabarit : c'était du calcul jeté. Les présences
        // coûtaient à elles seules deux sous-requêtes corrélées sur une relation imbriquée.

        // Inscriptions récentes (vraies données) (filtré par année en cours)
        try {
            if ($anneeEnCours) {
                $data['recentInscriptions'] = ESBTPInscription::with([
                    'etudiant',
                    'classe.filiere',
                    'etudiant.classe.filiere'
                ])
                    ->where('annee_universitaire_id', $anneeEnCours->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            } else {
                $data['recentInscriptions'] = ESBTPInscription::with([
                    'etudiant',
                    'classe.filiere',
                    'etudiant.classe.filiere'
                ])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }
        } catch (\Exception $e) {
            $data['recentInscriptions'] = collect();
        }

        // Les examens à venir et les annonces récentes étaient chargés ici sans qu'aucune
        // section de la vue ne les rende.

        // Statistiques par filière avec couleurs pour le graphique (filtré par année en cours)
        if ($anneeEnCours) {
            $filiereStatsRaw = ESBTPFiliere::withCount(['inscriptions' => function($query) use ($anneeEnCours) {
                $query->where('annee_universitaire_id', $anneeEnCours->id);
            }])->get();
        } else {
            $filiereStatsRaw = ESBTPFiliere::withCount('inscriptions')->get();
        }
        $colors = ['#0453cb', '#ec4899', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#5e91de', '#f97316', '#06b6d4', '#84cc16', '#f43f5e', '#0453cb'];

        $data['filiereStats'] = $filiereStatsRaw->map(function($filiere, $index) use ($colors) {
            return [
                'id' => $filiere->id,
                'name' => $filiere->name,
                'students' => $filiere->inscriptions_count,
                'color' => $colors[$index % count($colors)]
            ];
        });

        // Données mensuelles pour les graphiques (filtré par année en cours)
        $data['monthlyStats'] = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            if ($anneeEnCours) {
                // Courbe verte: inscriptions VALIDÉES dans le mois (date_validation)
                $studentsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('workflow_step', 'etudiant_cree')
                    ->whereNotNull('date_validation')
                    ->whereYear('date_validation', $date->year)
                    ->whereMonth('date_validation', $date->month)
                    ->count();
                // Courbe bleue: toutes les inscriptions CRÉÉES dans le mois (created_at)
                $inscriptionsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                // Courbe orange: STOCK d'inscriptions en attente de paiement à la fin du mois
                // = Toutes les inscriptions créées AVANT fin du mois qui n'ont toujours pas de paiement validé
                $endOfMonth = (clone $date)->endOfMonth();
                $pendingPaymentsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('created_at', '<=', $endOfMonth)  // Créées avant ou pendant ce mois
                    ->where(function($query) {
                        // Cas 1: Aucun paiement existe
                        $query->whereDoesntHave('paiements')
                            // Cas 2: A des paiements mais tous en attente (aucun validé)
                            ->orWhereHas('paiements', function($q) {
                                $q->where('status', 'en_attente');
                            }, '>', 0)
                            ->whereDoesntHave('paiements', function($q) {
                                $q->whereIn('status', ['validé', 'validated', 'payé', 'paid']);
                            });
                    })
                    ->count();
            } else {
                // Sans année en cours: courbe verte = inscriptions VALIDÉES (date_validation)
                $studentsCount = ESBTPInscription::where('workflow_step', 'etudiant_cree')
                    ->whereNotNull('date_validation')
                    ->whereYear('date_validation', $date->year)
                    ->whereMonth('date_validation', $date->month)
                    ->count();
                // Courbe bleue: toutes les inscriptions CRÉÉES
                $inscriptionsCount = ESBTPInscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                // Courbe orange: STOCK d'inscriptions en attente de paiement à la fin du mois
                // = Toutes les inscriptions créées AVANT fin du mois qui n'ont toujours pas de paiement validé
                $endOfMonth = (clone $date)->endOfMonth();
                $pendingPaymentsCount = ESBTPInscription::where('created_at', '<=', $endOfMonth)
                    ->where(function($query) {
                        // Cas 1: Aucun paiement existe
                        $query->whereDoesntHave('paiements')
                            // Cas 2: A des paiements mais tous en attente (aucun validé)
                            ->orWhereHas('paiements', function($q) {
                                $q->where('status', 'en_attente');
                            }, '>', 0)
                            ->whereDoesntHave('paiements', function($q) {
                                $q->whereIn('status', ['validé', 'validated', 'payé', 'paid']);
                            });
                    })
                    ->count();
            }

            $data['monthlyStats'][] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'students' => $studentsCount,
                'inscriptions' => $inscriptionsCount,
                'pending_payments' => $pendingPaymentsCount,
            ];
        }

        // Le graphique de la vue est alimenté par monthlyStats seul. La série
        // inscriptionsByMonth et les statistiques d'assiduité du jour, elles, ne sont
        // rendues nulle part sur ce tableau de bord.

        return view('dashboard.superadmin', $data);
    }

    /**
     * Tableau de bord pour les secrétaires avec les permissions limitées.
     */
    private function secretaireDashboard()
    {
        $user = Auth::user();
        $data = [
            'user' => $user
        ];

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $data['anneeEnCours'] = $anneeEnCours;

        // Inscriptions en attente - inclut les actives dont le workflow n'est pas finalisé
        $pendingSecQuery = \App\Models\ESBTPInscription::where(function ($q) {
            $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                $subQ->where('status', 'active')
                    ->where(function ($wq) {
                        $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                            ->orWhereNull('workflow_step');
                    });
            });
        });
        if ($anneeEnCours) {
            $pendingSecQuery->where('annee_universitaire_id', $anneeEnCours->id);
        }
        $data['pendingInscriptionsCount'] = $pendingSecQuery->count();

        $data['pendingCurrentYearInscriptionsCount'] = 0;
        $data['pendingCurrentYearInscriptionsByStep'] = [];
        if ($anneeEnCours) {
            $pendingCurrentYearQuery = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                ->where(function($query) {
                    $query->whereIn('status', ['en_attente', 'pending'])
                        ->orWhere(function($subQuery) {
                            $subQuery->where('status', 'active')
                                ->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation']);
                        });
                });

            $data['pendingCurrentYearInscriptionsCount'] = (clone $pendingCurrentYearQuery)->count();
            $data['pendingCurrentYearInscriptionsByStep'] = [
                'prospect' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'prospect')->count(),
                'documents_complets' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'documents_complets')->count(),
                'en_validation' => (clone $pendingCurrentYearQuery)->where('workflow_step', 'en_validation')->count(),
            ];
        }

        // Étudiants - Les secrétaires peuvent voir et créer des étudiants
        try {
            $studentCountsSec = app(StudentCountService::class)->counts();
            $data['totalStudents'] = $studentCountsSec['inscrits_annee_courante'];
            $data['totalStudentsBase'] = $studentCountsSec['total_base'];
            $data['anneeLabel'] = $studentCountsSec['annee_courante_label'];
            $data['recentStudents'] = ESBTPEtudiant::with(['inscriptions' => function($q) {
                    $q->with(['classe', 'anneeUniversitaire'])
                        ->orderBy('created_at', 'desc');
                }])
                ->whereHas('inscriptions')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['totalStudents'] = 0;
            $data['totalStudentsBase'] = 0;
            $data['anneeLabel'] = null;
            $data['recentStudents'] = collect();
        }

        // Présences - Les secrétaires peuvent gérer les présences
        try {
            $data['todayAttendances'] = ESBTPAttendance::finalOnly()->whereDate('date', today())->count();
            // « Justifications en attente » = justificatifs déposés par les étudiants
            // que personne n'a encore traités (cycle de vie : App\Enums\JustificationStatus).
            $data['pendingJustifications'] = ESBTPAttendance::where('statut', 'absent')
                ->where('justification_status', \App\Enums\JustificationStatus::PENDING->value)
                ->count();
        } catch (\Exception $e) {
            \Log::warning('[dashboard secrétaire] présences indisponibles : ' . $e->getMessage());
            $data['todayAttendances'] = null;
            $data['pendingJustifications'] = null;
        }

        // Emplois du temps - Les secrétaires peuvent créer et consulter les emplois du temps
        try {
            $data['totalTimetables'] = ESBTPEmploiTemps::count();
            $today = Carbon::now()->format('Y-m-d');
            $data['todayClasses'] = ESBTPSeanceCours::whereDate('date', $today)->count();
        } catch (\Exception $e) {
            $data['totalTimetables'] = 0;
            $data['todayClasses'] = 0;
        }

        // Bulletins - Les secrétaires peuvent générer et consulter les bulletins
        try {
            $data['totalBulletins'] = ESBTPBulletin::count();
        } catch (\Exception $e) {
            $data['totalBulletins'] = 0;
        }

        // Messages - Les secrétaires peuvent envoyer et recevoir des messages
        try {
            $data['recentMessages'] = Message::where(function($query) {
                    $query->where('recipient_type', 'secretaires')
                        ->whereNull('recipient_group');
                })
                ->orWhere(function($query) {
                    $query->where('recipient_type', 'all')
                        ->whereNull('recipient_group');
                })
                ->orWhere('recipient_id', Auth::id())
                ->whereNull('parent_id')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentMessages'] = collect();
        }

        return view('dashboard.secretaire', $data);
    }

    /**
     * Tableau de bord pour les comptables avec les données financières.
     */
    private function comptableDashboard()
    {
        $user = Auth::user();
        $data = ['user' => $user];

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $data['anneeEnCours'] = $anneeEnCours;
        $data['validatedInscriptionsCount'] = 0;
        try {
            $validatedQuery = ESBTPInscription::query()->where('status', 'active');
            if ($anneeEnCours) {
                $validatedQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['validatedInscriptionsCount'] = $validatedQuery->count();
        } catch (\Exception $e) {
            $data['validatedInscriptionsCount'] = 0;
        }

        // --- KPIs financiers ---
        try {
            $paiementsQuery = \App\Models\ESBTPPaiement::query()->whereNull('deleted_at');
            if ($anneeEnCours) {
                // Filtrer via la relation inscription pour garantir la cohérence
                $paiementsQuery->whereHas('inscription', function ($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id);
                });
            }

            // Montants par statut
            $data['totalEncaisse'] = \App\Models\ESBTPPaiement::netCashSum((clone $paiementsQuery)->where('status', 'validé'));
            $data['totalEnAttente'] = (clone $paiementsQuery)->where('status', 'en_attente')->encaissements()->sum('montant');
            $data['paiementsEnAttenteCount'] = (clone $paiementsQuery)->where('status', 'en_attente')->count();

            // Total frais dus (souscriptions actives de l'année courante)
            $subscriptionsQuery = \App\Models\ESBTPFraisSubscription::query()->charged();
            if ($anneeEnCours) {
                $subscriptionsQuery->whereHas('inscription', function ($q) use ($anneeEnCours) {
                    $q->where('annee_universitaire_id', $anneeEnCours->id);
                });
            }
            $data['totalFraisDus'] = $subscriptionsQuery->sum('amount');

            // Taux de recouvrement (plafonné à 100%)
            $data['tauxRecouvrement'] = $data['totalFraisDus'] > 0
                ? min(round(($data['totalEncaisse'] / $data['totalFraisDus']) * 100, 1), 100)
                : 0;

            $data['montantRestant'] = max(0, $data['totalFraisDus'] - $data['totalEncaisse']);

            // Paiements du mois en cours
            $data['encaisseMois'] = \App\Models\ESBTPPaiement::netCashSum(
                (clone $paiementsQuery)
                    ->where('status', 'validé')
                    ->whereMonth('date_paiement', now()->month)
                    ->whereYear('date_paiement', now()->year)
            );

        } catch (\Exception $e) {
            $data['totalEncaisse'] = 0;
            $data['totalEnAttente'] = 0;
            $data['paiementsEnAttenteCount'] = 0;
            $data['totalFraisDus'] = 0;
            $data['tauxRecouvrement'] = 0;
            $data['montantRestant'] = 0;
            $data['encaisseMois'] = 0;
        }

        // --- Paiements récents ---
        try {
            $recentQuery = \App\Models\ESBTPPaiement::with(['etudiant', 'inscription.classe'])
                ->whereNull('deleted_at')
                ->orderBy('created_at', 'desc');
            if ($anneeEnCours) {
                $recentQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['recentPaiements'] = $recentQuery->take(8)->get();
        } catch (\Exception $e) {
            $data['recentPaiements'] = collect();
        }

        // --- Étudiants avec impayés (top 5 plus gros soldes) ---
        try {
            if ($anneeEnCours) {
                $data['topImpayes'] = DB::table('esbtp_frais_subscriptions as fs')
                    ->join('esbtp_inscriptions as i', 'fs.inscription_id', '=', 'i.id')
                    ->join('esbtp_etudiants as e', 'i.etudiant_id', '=', 'e.id')
                    ->leftJoin(DB::raw('(SELECT inscription_id, frais_category_id, SUM('.\App\Models\ESBTPPaiement::sqlStudentPaidCase().') as total_paye FROM esbtp_paiements WHERE status = \'validé\' AND deleted_at IS NULL GROUP BY inscription_id, frais_category_id) as p'), function ($join) {
                        $join->on('p.inscription_id', '=', 'i.id')
                             ->on('p.frais_category_id', '=', 'fs.frais_category_id');
                    })
                    ->where('i.annee_universitaire_id', $anneeEnCours->id)
                    ->where('i.status', 'active')
                    ->whereNull('e.deleted_at')
                    ->select(
                        'e.id as etudiant_id',
                        'e.nom',
                        'e.prenoms',
                        'e.matricule',
                        DB::raw('SUM(fs.amount) as total_du'),
                        DB::raw('SUM(COALESCE(p.total_paye, 0)) as total_paye'),
                        DB::raw('SUM(fs.amount) - SUM(COALESCE(p.total_paye, 0)) as solde_restant')
                    )
                    ->groupBy('e.id', 'e.nom', 'e.prenoms', 'e.matricule')
                    ->havingRaw('solde_restant > 0')
                    ->orderByDesc('solde_restant')
                    ->limit(5)
                    ->get();
            } else {
                $data['topImpayes'] = collect();
            }
        } catch (\Exception $e) {
            $data['topImpayes'] = collect();
        }

        // --- Répartition par mode de paiement (pour le mois) ---
        try {
            $modesQuery = \App\Models\ESBTPPaiement::query()
                ->whereNull('deleted_at')
                ->where('status', 'validé')
                ->whereMonth('date_paiement', now()->month)
                ->whereYear('date_paiement', now()->year);
            if ($anneeEnCours) {
                $modesQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['paiementsParMode'] = $modesQuery
                ->select('mode_paiement', DB::raw('COUNT(*) as count'), DB::raw('SUM('.\App\Models\ESBTPPaiement::sqlCashCase().') as total'))
                ->groupBy('mode_paiement')
                ->get();
        } catch (\Exception $e) {
            $data['paiementsParMode'] = collect();
        }

        return view('dashboard.comptable', $data);
    }

    /**
     * Tableau de bord pour les caissiers.
     */
    private function caissierDashboard()
    {
        $user = Auth::user();
        $today = now()->startOfDay();
        $anneeEnCours = \App\Models\ESBTPAnneeUniversitaire::where('is_current', true)->first();

        try {
            // KPIs — scoped to current caissier
            $paiementsAujourdhuiCount = \App\Models\ESBTPPaiement::whereDate('created_at', $today)
                ->where('created_by', $user->id)
                ->count();

            $montantEncaisseAujourdhui = \App\Models\ESBTPPaiement::netCashSum(
                \App\Models\ESBTPPaiement::whereDate('created_at', $today)
                    ->where('created_by', $user->id)
                    ->where('status', 'validé')
            );

            $preInscriptionsAujourdhui = \App\Models\ESBTPInscription::whereDate('created_at', $today)
                ->where('workflow_step', 'prospect')
                ->where('created_by', $user->id)
                ->count();

            $preInscriptionsEnAttente = \App\Models\ESBTPInscription::where('workflow_step', 'prospect')
                ->where('status', 'en_attente')
                ->count();

            // Recent payments (last 10 by this caissier). L'ecran mobile en
            // montre cinq avec la classe et le frais : on les charge d'un coup.
            $paiementsRecents = \App\Models\ESBTPPaiement::with(['etudiant', 'inscription.classe', 'fraisCategory'])
                ->where('created_by', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $caisseMobile = $this->caisseDuJourPourMobile($user, $today);
        } catch (\Exception $e) {
            $paiementsAujourdhuiCount = 0;
            $montantEncaisseAujourdhui = 0;
            $preInscriptionsAujourdhui = 0;
            $preInscriptionsEnAttente = 0;
            $paiementsRecents = collect();
            $caisseMobile = $this->caisseDuJourVide();
        }

        return view('dashboard.caissier', compact(
            'user',
            'anneeEnCours',
            'paiementsAujourdhuiCount',
            'montantEncaisseAujourdhui',
            'preInscriptionsAujourdhui',
            'preInscriptionsEnAttente',
            'paiementsRecents',
            'caisseMobile'
        ));
    }

    /**
     * Ce que l'accueil mobile du caissier ajoute au bureau : la session de
     * caisse du jour, l'encaisse par famille de mode, ce qui attend encore une
     * validation et ce que le guichet peut encore annuler lui-meme.
     *
     * La session est LUE, jamais creee : c'est le premier encaissement en
     * especes (ou « Ma caisse ») qui l'ouvre, pas le fait de regarder l'accueil.
     *
     * @return array{
     *   session: array{statut: string|null, ouverte_a: string|null, fermee_a: string|null},
     *   especes: array{count: int, total: float},
     *   mobile: array{count: int, total: float},
     *   autres: array{count: int, total: float},
     *   a_valider: int,
     *   annulables: int,
     *   fenetre_annulation_minutes: int,
     *   peut_annuler: bool
     * }
     */
    private function caisseDuJourPourMobile(User $user, Carbon $today): array
    {
        $donnees = $this->caisseDuJourVide();

        $session = \App\Models\ESBTPCashSession::query()
            ->where('cashier_user_id', $user->id)
            ->whereDate('business_date', $today->toDateString())
            ->first();
        if ($session) {
            $donnees['session'] = [
                'statut' => $session->status?->value,
                'ouverte_a' => $session->opened_at?->format('H:i'),
                'fermee_a' => $session->closed_at?->format('H:i'),
            ];
        }

        // Un seul passage sur les versements du jour : les KPI par mode ne
        // comptent que les encaissements valides (les avoirs se lisent a part,
        // via netCashSum sur le total).
        $paiementsJour = \App\Models\ESBTPPaiement::query()
            ->ownedBy($user->id)
            ->whereDate('created_at', $today)
            ->get();

        foreach ($paiementsJour as $paiement) {
            if ($paiement->status === 'en_attente' && ! $paiement->isAvoir()) {
                $donnees['a_valider']++;
                if ($donnees['peut_annuler'] && $user->can('cancelOwnRecent', $paiement)) {
                    $donnees['annulables']++;
                }
                continue;
            }
            if ($paiement->status !== 'validé' || $paiement->isAvoir()) {
                continue;
            }
            $famille = $this->familleDeMode((string) $paiement->mode_paiement);
            $donnees[$famille]['count']++;
            $donnees[$famille]['total'] += (float) $paiement->montant;
        }

        foreach (['especes', 'mobile', 'autres'] as $famille) {
            $donnees[$famille]['total'] = round($donnees[$famille]['total'], 2);
        }

        return $donnees;
    }

    private function caisseDuJourVide(): array
    {
        $user = Auth::user();

        return [
            'session' => ['statut' => null, 'ouverte_a' => null, 'fermee_a' => null],
            'especes' => ['count' => 0, 'total' => 0.0],
            'mobile' => ['count' => 0, 'total' => 0.0],
            'autres' => ['count' => 0, 'total' => 0.0],
            'a_valider' => 0,
            'annulables' => 0,
            'fenetre_annulation_minutes' => (int) SettingsHelper::get('comptabilite.cancel_own_window_minutes', 5),
            'peut_annuler' => $user ? $user->can('paiements.cancel_own') : false,
        ];
    }

    /**
     * Especes au tiroir ; portefeuilles mobiles ensemble ; le reste (virement,
     * cheque, valeur inconnue) a part, pour ne pas le faire passer pour du
     * mobile money.
     */
    private function familleDeMode(string $mode): string
    {
        $canon = \App\Enums\ModePaiement::fromLegacy($mode);
        if ($canon === null) {
            return 'autres';
        }
        if ($canon->isDrawer()) {
            return 'especes';
        }

        return in_array($canon, [
            \App\Enums\ModePaiement::MOBILE_MONEY,
            \App\Enums\ModePaiement::WAVE,
            \App\Enums\ModePaiement::ORANGE_MONEY,
            \App\Enums\ModePaiement::MTN_MONEY,
            \App\Enums\ModePaiement::MOOV_MONEY,
        ], true) ? 'mobile' : 'autres';
    }

    /**
     * Tableau de bord pour les coordinateurs avec permissions de coordination.
     */
    private function coordinateurDashboard()
    {
        $user = Auth::user();
        $data = [
            'user' => $user
        ];

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $data['anneeEnCours'] = $anneeEnCours;

        // Statistiques accessibles aux coordinateurs
        try {
            // Étudiants — Service centralisé (distinct etudiant_id, inscriptions actives+validées année courante)
            $studentCounts = app(StudentCountService::class)->counts();
            $data['totalStudents'] = $studentCounts['inscrits_annee_courante'];
            $data['totalStudentsBase'] = $studentCounts['total_base'];
            $data['anneeLabel'] = $studentCounts['annee_courante_label'];

            if ($anneeEnCours) {
                $data['recentStudents'] = ESBTPInscription::with(['etudiant', 'classe.filiere'])
                    ->where('annee_universitaire_id', $anneeEnCours->id)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function($inscription) {
                        return $inscription->etudiant;
                    });
            } else {
                $data['recentStudents'] = ESBTPEtudiant::with(['classe.filiere'])
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get();
            }
        } catch (\Exception $e) {
            $data['totalStudents'] = 0;
            $data['totalStudentsBase'] = 0;
            $data['anneeLabel'] = null;
            $data['recentStudents'] = collect();
        }

        // Classes - Coordinateurs supervisent les classes (pas de filtrage par année)
        try {
            $data['totalClasses'] = ESBTPClasse::count();
        } catch (\Exception $e) {
            $data['totalClasses'] = 0;
        }

        // Enseignants - Coordinateurs supervisent les enseignants
        try {
            $data['totalTeachers'] = ESBTPTeacher::count();
        } catch (\Exception $e) {
            $data['totalTeachers'] = 0;
        }

        // Évaluations - Coordinateurs peuvent voir les évaluations (filtré par année en cours)
        try {
            $evalQuery = ESBTPEvaluation::query();
            if ($anneeEnCours) {
                $evalQuery->whereHas('classe', fn($q) => $q->where('annee_universitaire_id', $anneeEnCours->id));
            }
            $data['totalExamens'] = (clone $evalQuery)->count();
            $data['recentExamens'] = (clone $evalQuery)
                ->with(['classe', 'matiere'])
                ->withCount('notes')
                ->orderBy('date_evaluation', 'desc')
                ->take(5)
                ->get();

            // Évaluations passées sans notes (alerte pour le coordinateur)
            $data['evaluationsSansNotes'] = (clone $evalQuery)
                ->with(['classe', 'matiere'])
                ->whereDate('date_evaluation', '<', today())
                ->whereDoesntHave('notes')
                ->orderBy('date_evaluation', 'desc')
                ->take(5)
                ->get();
            $data['evaluationsSansNotesCount'] = (clone $evalQuery)
                ->whereDate('date_evaluation', '<', today())
                ->whereDoesntHave('notes')
                ->count();
        } catch (\Exception $e) {
            $data['totalExamens'] = 0;
            $data['recentExamens'] = collect();
            $data['evaluationsSansNotes'] = collect();
            $data['evaluationsSansNotesCount'] = 0;
        }

        // Emplois du temps - Coordinateurs gèrent la planification (filtré par année en cours)
        try {
            $edtQuery = ESBTPEmploiTemps::query();
            if ($anneeEnCours) {
                $edtQuery->where('annee_universitaire_id', $anneeEnCours->id);
            }
            $data['totalEmploiTemps'] = (clone $edtQuery)->count();
            $data['activeEmploiTemps'] = (clone $edtQuery)
                ->where('is_active', true)
                ->whereDate('date_debut', '<=', today())
                ->whereDate('date_fin', '>=', today())
                ->count();
            $data['expiredEmploiTemps'] = (clone $edtQuery)
                ->whereDate('date_fin', '<', today())
                ->count();

            // Classes sans emploi du temps pour l'année courante
            $classesAvecEdt = (clone $edtQuery)->pluck('classe_id')->unique();
            $data['classesWithoutTimetable'] = ESBTPClasse::where('is_active', true)
                ->whereNotIn('id', $classesAvecEdt)
                ->count();
        } catch (\Exception $e) {
            $data['totalEmploiTemps'] = 0;
            $data['activeEmploiTemps'] = 0;
            $data['expiredEmploiTemps'] = 0;
            $data['classesWithoutTimetable'] = 0;
        }

        // Présences - Coordinateurs suivent les présences du jour (filtré par année en cours)
        try {
            $data['attendanceStats'] = $this->presencesDuJour($anneeEnCours);
            $data['todayAttendances'] = $data['attendanceStats']['total'];
        } catch (\Exception $e) {
            \Log::warning('[dashboard coordinateur] présences du jour indisponibles : ' . $e->getMessage());
            $data['todayAttendances'] = null;
            $data['attendanceStats'] = $this->presencesIndisponibles();
        }

        // Messages pour coordinateurs (fix: wrapper parent pour les OR)
        try {
            $data['recentMessages'] = Message::with('sender')
                ->whereNull('parent_id')
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->where('recipient_type', 'coordinateurs')
                          ->whereNull('recipient_group');
                    })
                    ->orWhere(function($q) {
                        $q->where('recipient_type', 'all')
                          ->whereNull('recipient_group');
                    })
                    ->orWhere('recipient_id', Auth::id());
                })
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentMessages'] = collect();
        }

        // Inscriptions en attente (filtré par année en cours) - inclut workflow non finalisé
        try {
            $pendingCoordQuery = ESBTPInscription::where(function ($q) {
                $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                    $subQ->where('status', 'active')
                        ->where(function ($wq) {
                            $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                                ->orWhereNull('workflow_step');
                        });
                });
            });
            if ($anneeEnCours) {
                $data['pendingInscriptionsCount'] = (clone $pendingCoordQuery)
                    ->where('annee_universitaire_id', $anneeEnCours->id)->count();
                $data['recentInscriptions'] = ESBTPInscription::with([
                    'etudiant',
                    'classe.filiere'
                ])
                    ->where('annee_universitaire_id', $anneeEnCours->id)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            } else {
                $data['pendingInscriptionsCount'] = $pendingCoordQuery->count();
                $data['recentInscriptions'] = ESBTPInscription::with([
                    'etudiant',
                    'classe.filiere'
                ])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }
        } catch (\Exception $e) {
            $data['pendingInscriptionsCount'] = 0;
            $data['recentInscriptions'] = collect();
        }

        // Annonces récentes
        try {
            $data['recentAnnouncements'] = ESBTPAnnonce::orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            $data['recentAnnouncements'] = collect();
        }

        return view('dashboard.coordinateur', $data);
    }

    /**
     * Données JSON du dashboard coordinateur (AJAX refresh)
     */
    public function coordinateurDashboardData()
    {
        $user = Auth::user();
        if (!$user || !$user->can('identity.coordinate')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Réutiliser la même logique que coordinateurDashboard
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Service centralisé : 2 valeurs distinctes (cf. memory_studentcount)
        $studentCounts = app(StudentCountService::class)->counts();
        $totalStudents = $studentCounts['inscrits_annee_courante'];
        $totalStudentsBase = $studentCounts['total_base'];
        $anneeLabel = $studentCounts['annee_courante_label'];

        $pendingInscriptionsCount = 0;
        $totalClasses = 0;
        $totalTeachers = 0;
        $totalExamens = 0;
        $evaluationsSansNotesCount = 0;
        $totalEmploiTemps = 0;
        $activeEmploiTemps = 0;
        $expiredEmploiTemps = 0;
        $classesWithoutTimetable = 0;
        $attendanceStats = $this->presencesIndisponibles();

        try {
            $totalClasses = ESBTPClasse::count();
            $totalTeachers = ESBTPTeacher::count();
        } catch (\Exception $e) {
            // Keep defaults
        }

        try {
            if ($anneeEnCours) {
                // $totalStudents déjà calculé par StudentCountService ci-dessus
                $pendingInscriptionsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where(function ($q) {
                        $q->whereIn('status', ['en_attente', 'pending'])->orWhere(function ($subQ) {
                            $subQ->where('status', 'active')
                                ->where(function ($wq) {
                                    $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                                        ->orWhereNull('workflow_step');
                                });
                        });
                    })->count();

                $evalQuery = ESBTPEvaluation::whereHas('classe', fn($q) => $q->where('annee_universitaire_id', $anneeEnCours->id));
                $totalExamens = (clone $evalQuery)->count();
                $evaluationsSansNotesCount = (clone $evalQuery)
                    ->whereDate('date_evaluation', '<', today())
                    ->whereDoesntHave('notes')->count();

                $edtQuery = ESBTPEmploiTemps::where('annee_universitaire_id', $anneeEnCours->id);
                $totalEmploiTemps = (clone $edtQuery)->count();
                $activeEmploiTemps = (clone $edtQuery)->where('is_active', true)
                    ->whereDate('date_debut', '<=', today())
                    ->whereDate('date_fin', '>=', today())->count();
                $expiredEmploiTemps = (clone $edtQuery)->whereDate('date_fin', '<', today())->count();
                $classesAvecEdt = (clone $edtQuery)->pluck('classe_id')->unique();
                $classesWithoutTimetable = ESBTPClasse::where('is_active', true)
                    ->whereNotIn('id', $classesAvecEdt)->count();

                $attendanceStats = $this->presencesDuJour($anneeEnCours);
            }
        } catch (\Exception $e) {
            \Log::warning('[dashboard coordinateur/data] indicateurs indisponibles : ' . $e->getMessage());
            $attendanceStats = $this->presencesIndisponibles();
        }

        return response()->json([
            'totalStudents' => $totalStudents,
            'totalStudentsBase' => $totalStudentsBase,
            'anneeLabel' => $anneeLabel,
            'pendingInscriptionsCount' => $pendingInscriptionsCount,
            'totalClasses' => $totalClasses,
            'totalTeachers' => $totalTeachers,
            'totalExamens' => $totalExamens,
            'evaluationsSansNotesCount' => $evaluationsSansNotesCount,
            'totalEmploiTemps' => $totalEmploiTemps,
            'activeEmploiTemps' => $activeEmploiTemps,
            'expiredEmploiTemps' => $expiredEmploiTemps,
            'classesWithoutTimetable' => $classesWithoutTimetable,
            'attendanceStats' => $attendanceStats,
        ]);
    }

    /**
     * Tableau de bord pour les étudiants avec vue uniquement sur leurs propres données.
     */
    private function etudiantDashboard()
    {
        $user = Auth::user();
        $student = ESBTPEtudiant::where('user_id', $user->id)->first();

        if (!$student) {
            // Au lieu de rediriger, afficher une vue spéciale pour les étudiants sans profil
            return view('dashboard.etudiant_setup', [
                'user' => $user
            ]);
        }

        $data = [
            'user' => $user,
            'student' => $student
        ];

        $data['anneeEnCours'] = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Inscription active de l'année courante : c'est elle qui porte la classe
        // (l'étudiant peut avoir changé de classe d'une année à l'autre).
        $inscription = null;
        if ($data['anneeEnCours']) {
            $inscription = ESBTPInscription::query()
                ->where('etudiant_id', $student->id)
                ->where('annee_universitaire_id', $data['anneeEnCours']->id)
                ->where('status', 'active')
                ->with(['classe.filiere', 'classe.niveau', 'classe.parcours'])
                ->first();
        }
        $classeId = $inscription->classe_id ?? $student->classe_id;

        // Récupérer l'emploi du temps d'aujourd'hui pour l'étudiant
        try {
            // Les séances stockent le jour en français (« lundi », …) : le nom
            // anglais de date('l') ne trouvait jamais rien.
            $today = mb_strtolower(now()->locale('fr')->dayName, 'UTF-8');
            $data['todayTimetable'] = ESBTPSeanceCours::whereHas('emploiTemps', function($query) use ($classeId) {
                    $query->where('classe_id', $classeId)->where('is_active', true);
                })
                ->where('jour', $today)
                ->orderBy('heure_debut')
                ->with(['matiere', 'emploiTemps.classe', 'enseignant.user'])
                ->get();
        } catch (\Exception $e) {
            $data['todayTimetable'] = collect();
        }

        // Récupérer les notifications récentes pour l'étudiant
        try {
            $data['notifications'] = ESBTPAnnonce::where(function($query) use ($student) {
                    $query->where('recipient_type', 'etudiant')
                        ->whereNull('recipient_id');
                })
                ->orWhere(function($query) use ($student) {
                    $query->where('recipient_type', 'specific_user')
                        ->where('recipient_id', $user->id);
                })
                ->orWhere(function($query) use ($student) {
                    $query->where('recipient_type', 'specific_class')
                        ->where('recipient_group', $student->classe_id);
                })
                ->orWhere('recipient_type', 'all')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['notifications'] = collect();
        }

        // Récupérer les notes récentes de l'étudiant
        try {
            $data['recentGrades'] = ESBTPNote::with(['evaluation.matiere', 'matiere'])
                ->where('etudiant_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {
            $data['recentGrades'] = collect();
        }

        // Récupérer les statistiques de présence de l'étudiant
        try {
            if (! $data['anneeEnCours']) {
                // Sans année courante on ne sait pas quelle période afficher :
                // mieux vaut « — » qu'un cumul de toutes les années.
                throw new \RuntimeException('Aucune année universitaire courante.');
            }

            $comptes = $this->comptesParStatut(
                ESBTPAttendance::query()
                    ->where('etudiant_id', $student->id)
                    ->where('annee_universitaire_id', $data['anneeEnCours']->id)
            );

            $data['attendanceStats'] = [
                'total' => $comptes['total'],
                'present' => $comptes['present'],
                'retard' => $comptes['retard'],
                'absent' => $comptes['absent'],
                'excuse' => $comptes['excuse'],
                'rate' => $comptes['taux'],
            ];
        } catch (\Exception $e) {
            \Log::warning('[dashboard étudiant] assiduité indisponible : ' . $e->getMessage(), [
                'etudiant_id' => $student->id,
            ]);
            $data['attendanceStats'] = [
                'total' => null,
                'present' => null,
                'retard' => null,
                'absent' => null,
                'excuse' => null,
                'rate' => null,
            ];
        }

        // Accueil mobile (shell mobile, profil « etudiant ») : tout ce que
        // l'écran affiche en plus du bureau, calculé une seule fois ici.
        $data['mobileAccueil'] = $this->accueilMobileEtudiant($student, $inscription, $data);

        return view('dashboard.etudiant', $data);
    }

    /**
     * Données de l'accueil mobile de l'étudiant.
     *
     * Valeurs brutes (nombres, dates Carbon) : la vue met en forme. `null` veut
     * dire « indisponible » et s'affiche « — », jamais un faux zéro.
     */
    private function accueilMobileEtudiant(ESBTPEtudiant $student, ?ESBTPInscription $inscription, array $data): array
    {
        $annee = $data['anneeEnCours'] ?? null;
        $classe = $inscription?->classe;
        $estLmd = $classe ? $classe->isLMD() : false;
        $ecole = SettingsHelper::getSchoolInfo();

        $accueil = [
            'ecole' => (string) (($ecole['name'] ?? '') ?: (($ecole['acronym'] ?? '') ?: config('app.name'))), // meme libelle que la navbar (partials.navbar-etablissement)
            'aujourdhui' => now(),
            'prenom' => trim((string) ($student->prenoms ?? '')) ?: (string) $student->nom,
            'classe' => $classe?->name,
            'est_lmd' => $estLmd,
            'prochain_cours' => $this->prochainCoursDuJour($data['todayTimetable'] ?? collect()),
            'moyenne' => null,
            'credits' => null,
            'assiduite' => $data['attendanceStats']['rate'] ?? null,
            'absences' => $data['attendanceStats']['absent'] ?? null,
            'a_justifier' => ['total' => 0, 'derniere' => null],
            'finances' => null,
            'notes' => $this->dernieresNotes($data['recentGrades'] ?? collect()),
        ];

        if (! $annee) {
            return $accueil;
        }

        try {
            $accueil['a_justifier'] = $this->absencesAJustifier($student->id, $annee->id);
        } catch (\Throwable $e) {
            \Log::warning('[accueil mobile étudiant] absences à justifier indisponibles : ' . $e->getMessage());
        }

        if ($inscription) {
            try {
                $accueil['finances'] = $this->financesInscription($inscription);
            } catch (\Throwable $e) {
                \Log::warning('[accueil mobile étudiant] situation financière indisponible : ' . $e->getMessage());
            }

            try {
                if ($estLmd) {
                    // LMD : les crédits acquis priment sur une moyenne ; on ne
                    // passe jamais par le calcul de bulletin BTS.
                    $accueil['credits'] = $this->creditsLmdAcquis($student->id, $annee->id);
                }
                if (! $accueil['credits']) {
                    $accueil['moyenne'] = $this->moyenneCourante($student->id, $inscription->classe_id, $annee->id, $estLmd);
                }
            } catch (\Throwable $e) {
                \Log::warning('[accueil mobile étudiant] résultats indisponibles : ' . $e->getMessage());
            }
        }

        return $accueil;
    }

    /**
     * Première séance du jour qui n'est pas encore terminée (celle en cours comprise).
     */
    private function prochainCoursDuJour($seances): ?array
    {
        $maintenant = now();

        foreach (collect($seances) as $seance) {
            $debut = $seance->heure_debut ? Carbon::parse($seance->heure_debut) : null;
            $fin = $seance->heure_fin ? Carbon::parse($seance->heure_fin) : null;
            if (! $debut || ! $fin) {
                continue;
            }
            $finDuJour = $maintenant->copy()->setTimeFrom($fin);
            if ($finDuJour->lte($maintenant)) {
                continue;
            }

            return [
                'heure' => $debut->format('H:i'),
                'fin' => $fin->format('H:i'),
                'en_cours' => $maintenant->copy()->setTimeFrom($debut)->lte($maintenant),
                'matiere' => $seance->matiere->name ?? null,
                'salle' => $seance->salle ?: null,
                'enseignant' => $seance->enseignant?->full_name,
            ];
        }

        return null;
    }

    /**
     * Absences finales de l'année sans justification recevable (aucune, ou rejetée).
     */
    private function absencesAJustifier(int $etudiantId, int $anneeId): array
    {
        $query = ESBTPAttendance::query()
            ->finalOnly()
            ->where('etudiant_id', $etudiantId)
            ->where('annee_universitaire_id', $anneeId)
            ->where('statut', 'absent')
            ->where(function ($q) {
                $q->whereNull('justification_status')
                    ->orWhere('justification_status', JustificationStatus::REJECTED->value);
            });

        $total = (clone $query)->count();
        $derniere = null;

        if ($total > 0) {
            $absence = (clone $query)
                ->with(['matiere', 'seanceCours.matiere'])
                ->orderByDesc('date')
                ->orderByDesc('heure_debut')
                ->first();

            if ($absence) {
                $derniere = [
                    'id' => $absence->id,
                    'date' => $absence->date ? Carbon::parse($absence->date) : null,
                    'matiere' => $absence->matiere->name ?? $absence->seanceCours?->matiere?->name,
                    'heure_debut' => $absence->heure_debut ? substr((string) $absence->heure_debut, 0, 5) : null,
                    'heure_fin' => $absence->heure_fin ? substr((string) $absence->heure_fin, 0, 5) : null,
                ];
            }
        }

        return ['total' => $total, 'derniere' => $derniere];
    }

    /**
     * Reste dû de l'inscription et prochaine tranche à régler.
     *
     * Même arithmétique que « Mes paiements » (frais dus + reliquats entrants
     * − net encaissé). La tranche vient de l'échéancier figé de l'inscription
     * quand il existe ; on ne le recalcule pas depuis un tableau de bord.
     */
    private function financesInscription(ESBTPInscription $inscription): array
    {
        $totalFrais = ESBTPFraisSubscription::dueAmountForInscription($inscription->id);
        $totalReliquats = (float) ESBTPReliquatDetail::where('inscription_destination_id', $inscription->id)
            ->actifs()
            ->sum('solde_restant');
        $totalAttendu = $totalFrais + $totalReliquats;
        $totalPaye = ESBTPPaiement::netPaidForInscription((int) $inscription->id);
        $resteDu = max(0, $totalAttendu - $totalPaye);

        $prochaine = null;
        if ($resteDu > 0) {
            // Un instantane d'echeancier n'existe que si une regle couvre
            // l'inscription : la plupart des dossiers n'en ont pas, et lire
            // ->payload sur null faisait echouer tout le bloc financier — le
            // reste du s'affichait « indisponible » alors qu'il est connu.
            $snapshot = ESBTPInscriptionEcheancierSnapshot::where('inscription_id', $inscription->id)->first();
            $ligne = collect($snapshot?->payload['due_lines'] ?? [])
                ->filter(fn ($l) => (float) ($l['remaining_amount'] ?? 0) > 0 && ! empty($l['due_date']))
                ->sortBy('due_date')
                ->first();

            if ($ligne) {
                $echeance = Carbon::parse($ligne['due_date'])->addDays((int) ($ligne['grace_days'] ?? 0))->startOfDay();
                $prochaine = [
                    'label' => (string) ($ligne['label'] ?? 'Prochaine tranche'),
                    'date' => $echeance,
                    'montant' => (float) $ligne['remaining_amount'],
                    'en_retard' => $echeance->lt(now()->startOfDay()),
                ];
            }
        }

        return [
            'total_attendu' => round($totalAttendu, 2),
            'total_paye' => round($totalPaye, 2),
            'reste_du' => round($resteDu, 2),
            'prochaine_echeance' => $prochaine,
        ];
    }

    /**
     * Crédits acquis / attendus sur les bulletins LMD publiés de l'année.
     * `null` tant qu'aucun résultat d'UE n'a été délibéré.
     */
    private function creditsLmdAcquis(int $etudiantId, int $anneeId): ?array
    {
        $resultats = ESBTPLMDResultatUE::query()
            ->where('etudiant_id', $etudiantId)
            ->whereHas('bulletin', function ($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId)->where('is_published', true);
            })
            ->get(['id', 'statut', 'credit']);

        if ($resultats->isEmpty()) {
            return null;
        }

        $acquis = $resultats
            ->whereIn('statut', [ESBTPLMDResultatUE::STATUT_AQ, ESBTPLMDResultatUE::STATUT_APC])
            ->sum('credit');

        return [
            'acquis' => (int) $acquis,
            'total' => (int) $resultats->sum('credit'),
        ];
    }

    /**
     * Moyenne courante sur 20.
     *
     * BTS : la projection annuelle officielle si elle est calculable, sinon la
     * moyenne simple des notes de l'année. LMD : moyenne simple uniquement
     * (les bulletins LMD ont leur propre service, jamais celui du BTS).
     */
    private function moyenneCourante(int $etudiantId, int $classeId, int $anneeId, bool $estLmd): ?float
    {
        if (! $estLmd) {
            try {
                $snapshot = app(BtsCurrentResultSnapshotService::class)->getAnnualSnapshot($etudiantId, $classeId, $anneeId);
                if (isset($snapshot['effective_total']) && $snapshot['effective_total'] !== null) {
                    return round((float) $snapshot['effective_total'], 2);
                }
            } catch (\Throwable $e) {
                // Configuration de bulletin absente : on retombe sur les notes brutes.
            }
        }

        $notes = ESBTPNote::query()
            ->where('etudiant_id', $etudiantId)
            ->where(function ($q) {
                $q->whereNull('is_absent')->orWhere('is_absent', false);
            })
            ->whereHas('evaluation', function ($q) use ($anneeId) {
                $q->where('annee_universitaire_id', $anneeId)
                    ->where('status', '!=', ESBTPEvaluation::STATUS_CANCELLED);
            })
            ->with('evaluation:id,bareme')
            ->get();

        $sur20 = $notes
            ->map(function ($note) {
                $valeur = is_numeric($note->note) ? (float) $note->note : (is_numeric($note->valeur) ? (float) $note->valeur : null);
                if ($valeur === null) {
                    return null;
                }
                $bareme = (float) ($note->evaluation->bareme ?? 20);

                return $bareme > 0 ? $valeur * 20 / $bareme : $valeur;
            })
            ->filter(fn ($v) => $v !== null);

        return $sur20->isEmpty() ? null : round($sur20->avg(), 2);
    }

    /**
     * Dernières notes, prêtes pour les cartes « m-grade » de l'accueil mobile.
     */
    private function dernieresNotes($recentGrades): array
    {
        return collect($recentGrades)->take(3)->map(function ($note) {
            $evaluation = $note->evaluation;
            $matiere = $note->matiere ?? $evaluation?->matiere;
            $valeur = is_numeric($note->note) ? (float) $note->note : (is_numeric($note->valeur) ? (float) $note->valeur : null);

            return [
                'matiere' => $matiere->name ?? 'Matière',
                'code' => $matiere->code ?? null,
                'titre' => $evaluation?->titre ?: ($evaluation?->type ? ucfirst((string) $evaluation->type) : null),
                'coefficient' => $evaluation?->coefficient,
                'date' => $evaluation?->date_evaluation ? Carbon::parse($evaluation->date_evaluation) : ($note->created_at ? Carbon::parse($note->created_at) : null),
                'note' => $valeur,
                'bareme' => (float) ($evaluation->bareme ?? 20) ?: 20,
                'absent' => (bool) ($note->is_absent ?? false),
            ];
        })->values()->all();
    }

    /**
     * Tableau de bord générique pour les utilisateurs sans rôle spécifique.
     */
    public function genericDashboard()
    {
        $user = Auth::user();

        return view('dashboard.index', [
            'user' => $user
        ]);
    }

    /**
     * Dashboard Super Admin
     */
    public function superadmin()
    {
        $user = Auth::user();

        // Vérifier que l'utilisateur est bien super admin
        if (!$user->can('admin.access')) {
            abort(403, 'Accès non autorisé');
        }

        // Statistiques principales — Service centralisé pour distinguer année courante vs base totale
        $studentCounts = app(StudentCountService::class)->counts();
        $totalStudents = $studentCounts['inscrits_annee_courante'];
        $totalStudentsBase = $studentCounts['total_base'];
        $anneeLabel = $studentCounts['annee_courante_label'];
        $totalFilieres = ESBTPFiliere::count();
        $totalMatieres = ESBTPMatiere::count();
        $totalClasses = ESBTPClasse::count();
        $totalTeachers = ESBTPTeacher::count();
        $totalUsers = User::count();

        // Inscriptions récentes (vraies données)
        $recentInscriptions = ESBTPInscription::with(['etudiant', 'classe.filiere'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Examens à venir (vraies données)
        $upcomingExams = ESBTPEvaluation::with(['matiere', 'classe'])
            ->where('date_evaluation', '>=', now())
            ->orderBy('date_evaluation', 'asc')
            ->limit(5)
            ->get();

        // Annonces récentes
        $recentAnnouncements = ESBTPAnnonce::orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        // Récupérer l'année universitaire en cours
        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Statistiques par filière avec couleurs pour le graphique (filtré par année en cours)
        if ($anneeEnCours) {
            $filiereStatsRaw = ESBTPFiliere::withCount(['inscriptions' => function($query) use ($anneeEnCours) {
                $query->where('annee_universitaire_id', $anneeEnCours->id);
            }])->get();
        } else {
            $filiereStatsRaw = ESBTPFiliere::withCount('inscriptions')->get();
        }
        $colors = ['#0453cb', '#ec4899', '#22c55e', '#f59e0b', '#ef4444', '#0ea5e9', '#5e91de', '#f97316', '#06b6d4', '#84cc16', '#f43f5e', '#0453cb'];

        $filiereStats = $filiereStatsRaw->map(function($filiere, $index) use ($colors) {
            return [
                'id' => $filiere->id,
                'name' => $filiere->name,
                'students' => $filiere->inscriptions_count,
                'color' => $colors[$index % count($colors)]
            ];
        });

        // Données mensuelles pour les graphiques (filtré par année en cours)
        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            if ($anneeEnCours) {
                // Courbe verte: inscriptions VALIDÉES dans le mois (date_validation)
                $studentsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('workflow_step', 'etudiant_cree')
                    ->whereNotNull('date_validation')
                    ->whereYear('date_validation', $date->year)
                    ->whereMonth('date_validation', $date->month)
                    ->count();
                // Courbe bleue: toutes les inscriptions CRÉÉES dans le mois (created_at)
                $inscriptionsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                // Courbe orange: STOCK d'inscriptions en attente de paiement à la fin du mois
                // = Toutes les inscriptions créées AVANT fin du mois qui n'ont toujours pas de paiement validé
                $endOfMonth = (clone $date)->endOfMonth();
                $pendingPaymentsCount = ESBTPInscription::where('annee_universitaire_id', $anneeEnCours->id)
                    ->where('created_at', '<=', $endOfMonth)  // Créées avant ou pendant ce mois
                    ->where(function($query) {
                        // Cas 1: Aucun paiement existe
                        $query->whereDoesntHave('paiements')
                            // Cas 2: A des paiements mais tous en attente (aucun validé)
                            ->orWhereHas('paiements', function($q) {
                                $q->where('status', 'en_attente');
                            }, '>', 0)
                            ->whereDoesntHave('paiements', function($q) {
                                $q->whereIn('status', ['validé', 'validated', 'payé', 'paid']);
                            });
                    })
                    ->count();
            } else {
                // Sans année en cours: courbe verte = inscriptions VALIDÉES (date_validation)
                $studentsCount = ESBTPInscription::where('workflow_step', 'etudiant_cree')
                    ->whereNotNull('date_validation')
                    ->whereYear('date_validation', $date->year)
                    ->whereMonth('date_validation', $date->month)
                    ->count();
                // Courbe bleue: toutes les inscriptions CRÉÉES
                $inscriptionsCount = ESBTPInscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                // Courbe orange: STOCK d'inscriptions en attente de paiement à la fin du mois
                // = Toutes les inscriptions créées AVANT fin du mois qui n'ont toujours pas de paiement validé
                $endOfMonth = (clone $date)->endOfMonth();
                $pendingPaymentsCount = ESBTPInscription::where('created_at', '<=', $endOfMonth)
                    ->where(function($query) {
                        // Cas 1: Aucun paiement existe
                        $query->whereDoesntHave('paiements')
                            // Cas 2: A des paiements mais tous en attente (aucun validé)
                            ->orWhereHas('paiements', function($q) {
                                $q->where('status', 'en_attente');
                            }, '>', 0)
                            ->whereDoesntHave('paiements', function($q) {
                                $q->whereIn('status', ['validé', 'validated', 'payé', 'paid']);
                            });
                    })
                    ->count();
            }

            $monthlyStats[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'students' => $studentsCount,
                'inscriptions' => $inscriptionsCount,
                'pending_payments' => $pendingPaymentsCount,
            ];
        }

        // Inscriptions par mois pour le graphique (filtré par année en cours)
        if ($anneeEnCours) {
            $inscriptionsByMonth = ESBTPInscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('annee_universitaire_id', $anneeEnCours->id)
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        } else {
            $inscriptionsByMonth = ESBTPInscription::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('year', 'month')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        }

        return view('dashboard.superadmin', compact(
            'totalStudents',
            'totalStudentsBase',
            'anneeLabel',
            'totalFilieres',
            'totalMatieres',
            'totalClasses',
            'totalTeachers',
            'totalUsers',
            'recentInscriptions',
            'upcomingExams',
            'recentAnnouncements',
            'filiereStats',
            'monthlyStats',
            'inscriptionsByMonth'
        ));
    }

    /**
     * Dashboard Secrétaire
     */
    public function secretaire()
    {
        $user = Auth::user();

        if (!$user->can('identity.school_manager')) {
            abort(403, 'Accès non autorisé');
        }

        return $this->secretaireDashboard();
    }

    /**
     * Dashboard Étudiant
     */
    public function etudiant()
    {
        $user = Auth::user();

        if (!$user->can('identity.student')) {
            abort(403, 'Accès non autorisé');
        }
        return $this->etudiantDashboard();
    }

    /**
     * Obtenir les statistiques mensuelles
     */
    private function getMonthlyStats()
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'students' => ESBTPEtudiant::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'inscriptions' => ESBTPInscription::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }
        return $months;
    }

    /**
     * Obtenir les statistiques de présence
     */
    private function getAttendanceStats()
    {
        // Vérifier si le modèle de présence existe
        if (!class_exists('App\Models\ESBTPPresence')) {
            return [
                'total_present' => 0,
                'total_absent' => 0,
                'attendance_rate' => 0
            ];
        }

        $totalPresent = DB::table('esbtp_presences')
            ->where('statut', 'present')
            ->whereDate('date', today())
            ->count();

        $totalAbsent = DB::table('esbtp_presences')
            ->where('statut', 'absent')
            ->whereDate('date', today())
            ->count();

        $attendanceRate = $totalPresent + $totalAbsent > 0
            ? round(($totalPresent / ($totalPresent + $totalAbsent)) * 100, 1)
            : 0;

        return [
            'total_present' => $totalPresent,
            'total_absent' => $totalAbsent,
            'attendance_rate' => $attendanceRate
        ];
    }

    /**
     * Obtenir les données financières (simulées)
     */
    private function getFinancialData()
    {
        // Pour l'instant, données simulées
        // À remplacer par de vraies données quand le module comptabilité sera implémenté
        return [
            'total_paid' => 45070000,
            'total_due' => 32400000,
            'monthly_revenue' => [
                'Jan' => 3500000,
                'Fév' => 4200000,
                'Mar' => 3800000,
                'Avr' => 4100000,
                'Mai' => 3900000,
                'Jun' => 4300000,
            ]
        ];
    }

    /**
     * Dashboard Service Technique
     */
    private function serviceTechniqueDashboard()
    {
        $user = Auth::user();

        // Vérifier l'accès
        if (!$user->can('module.technical_support.access')) {
            abort(403, 'Accès refusé : Cette section est réservée au Service Technique d\'African Digit Consulting');
        }

        // Récupérer tous les établissements (simule multi-tenant via git branches)
        $etablissements = collect([
            (object)[
                'id' => 1,
                'nom' => 'École Actuelle',
                'branch' => 'presentation', // Current branch
                'status' => 'active',
                'created_at' => Carbon::now()->subMonths(6)
            ]
        ]);

        // Statistiques globales de l'établissement actuel
        $studentCountsST = app(StudentCountService::class)->counts();
        $stats = [
            'total_users' => User::count(),
            'total_students' => $studentCountsST['inscrits_annee_courante'], // = inscrits année courante
            'total_students_base' => $studentCountsST['total_base'],          // = total base DB
            'total_teachers' => User::whereHas('roles', function($query) {
                $query->whereIn('name', ['enseignant', 'teacher']);
            })->count(),
            'total_classes' => ESBTPClasse::count(),
            'total_inscriptions_year' => ESBTPInscription::whereHas('anneeUniversitaire', function($query) {
                $query->where('is_current', true);
            })->count(),
        ];

        // Configuration paywall actuelle
        $paywallConfig = [
            'is_active' => ESBTPSystemSetting::getValue('paywall_active', false),
            'subscription_end' => ESBTPSystemSetting::getValue('subscription_end_date', null),
            'max_users' => ESBTPSystemSetting::getValue('paywall_max_users', 50),
            'max_inscriptions_per_year' => ESBTPSystemSetting::getValue('paywall_max_inscriptions_per_year', 500),
            'plan_name' => ESBTPSystemSetting::getValue('paywall_plan_name', 'Non configuré'),
            'plan_price' => ESBTPSystemSetting::getValue('paywall_plan_price', 0),
        ];

        // Statut paywall
        $paywallStatus = $this->checkPaywallStatusForDashboard($paywallConfig, $stats);

        // Activité récente
        $recentActivity = [
            'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            'new_students_this_month' => ESBTPEtudiant::whereMonth('created_at', now()->month)->count(),
            'total_active_users' => User::where('created_at', '>=', now()->subDays(30))->count()
        ];

        // Codes d'urgence actifs
        $activeCodes = collect();
        $allSettings = ESBTPSystemSetting::where('key', 'LIKE', 'emergency_code_%')->get();
        foreach ($allSettings as $setting) {
            $codeData = json_decode($setting->value, true);
            if ($codeData && !$codeData['used'] && time() <= $codeData['expires_at']) {
                $activeCodes->push((object)[
                    'code' => str_replace('emergency_code_', '', $setting->key),
                    'expires_at' => Carbon::createFromTimestamp($codeData['expires_at']),
                    'created_by' => $codeData['created_by']
                ]);
            }
        }

        return view('dashboard.service-technique', compact(
            'etablissements',
            'stats',
            'paywallConfig',
            'paywallStatus',
            'recentActivity',
            'activeCodes'
        ));
    }

    /**
     * Vérifier le statut paywall pour le dashboard
     */
    private function checkPaywallStatusForDashboard($config, $stats)
    {
        $status = [
            'is_blocked' => false,
            'is_warning' => false,
            'message' => 'Système opérationnel',
            'level' => 'success'
        ];

        if (!$config['is_active']) {
            return $status;
        }

        // Vérifier expiration
        if ($config['subscription_end']) {
            $endDate = Carbon::parse($config['subscription_end']);
            $now = Carbon::now();

            if ($now->gt($endDate)) {
                $status['is_blocked'] = true;
                $status['message'] = 'Abonnement expiré';
                $status['level'] = 'danger';
            } elseif ($now->diffInDays($endDate) <= 7) {
                $status['is_warning'] = true;
                $status['message'] = 'Expiration proche (' . $now->diffInDays($endDate) . ' jours)';
                $status['level'] = 'warning';
            }
        }

        // Vérifier limites
        if ($stats['total_users'] >= $config['max_users'] * 0.9) {
            $status['is_warning'] = true;
            $status['message'] = 'Limite utilisateurs bientôt atteinte';
            $status['level'] = 'warning';
        }

        if ($stats['total_inscriptions_year'] >= $config['max_inscriptions_per_year'] * 0.9) {
            $status['is_warning'] = true;
            $status['message'] = 'Limite inscriptions bientôt atteinte';
            $status['level'] = 'warning';
        }

        return $status;
    }

    /**
     * Générer une couleur aléatoire pour les graphiques
     */
    private function getRandomColor()
    {
        $colors = [
            '#0453cb', '#5e91de', '#06b6d4', '#10b981',
            '#f59e0b', '#ef4444', '#ec4899', '#84cc16'
        ];
        return $colors[array_rand($colors)];
    }

    /**
     * Compte les présences FINALES d'une requête, ventilées par `statut`.
     *
     * Seule `esbtp_attendances.statut` fait foi (present / absent / retard / excuse).
     * L'ancienne colonne `status` valait 'present' sur chaque ligne par défaut et
     * n'était plus renseignée par personne : la lire donnait 100 % de présence.
     *
     * Règle de calcul du taux : un retard est une présence (l'étudiant était au
     * cours), une absence excusée reste une absence (il n'y était pas, elle est
     * seulement justifiée). Taux = (présents + retards) / total des appels.
     *
     * `finalOnly()` évite de compter trois fois la même séance (appel de début,
     * appel de fin, fusion).
     *
     * @return array{present:int, retard:int, absent:int, excuse:int, total:int, taux:float|null}
     */
    private function comptesParStatut(\Illuminate\Database\Eloquent\Builder $query): array
    {
        $parStatut = $query->finalOnly()
            ->selectRaw('statut, COUNT(*) AS total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $present = (int) ($parStatut['present'] ?? 0);
        $retard = (int) ($parStatut['retard'] ?? 0);
        $absent = (int) ($parStatut['absent'] ?? 0);
        $excuse = (int) ($parStatut['excuse'] ?? 0);
        $total = $present + $retard + $absent + $excuse;

        return [
            'present' => $present,
            'retard' => $retard,
            'absent' => $absent,
            'excuse' => $excuse,
            'total' => $total,
            'taux' => $total > 0 ? round(($present + $retard) / $total * 100, 1) : null,
        ];
    }

    /**
     * Présences du jour pour les tableaux de bord de pilotage (coordinateur).
     *
     * Les clés `total_present` / `total_absent` / `attendance_rate` sont celles
     * que lisent les vues ; `total_present` inclut les retards et `total_absent`
     * les absences excusées (cf. comptesParStatut()).
     */
    private function presencesDuJour(?ESBTPAnneeUniversitaire $anneeEnCours): array
    {
        $query = ESBTPAttendance::query()->whereDate('date', today());
        if ($anneeEnCours) {
            $query->where('annee_universitaire_id', $anneeEnCours->id);
        }

        $comptes = $this->comptesParStatut($query);

        return [
            'total' => $comptes['total'],
            'total_present' => $comptes['present'] + $comptes['retard'],
            'total_retard' => $comptes['retard'],
            'total_absent' => $comptes['absent'] + $comptes['excuse'],
            'total_excuse' => $comptes['excuse'],
            // Aucun appel fait aujourd'hui : pas de taux (« — »), pas un faux 0 %.
            'attendance_rate' => $comptes['taux'],
        ];
    }

    /**
     * Valeurs nulles (affichées « — ») quand le calcul des présences a échoué :
     * on ne montre jamais un 0 qui ressemblerait à une vraie mesure.
     */
    private function presencesIndisponibles(): array
    {
        return [
            'total' => null,
            'total_present' => null,
            'total_retard' => null,
            'total_absent' => null,
            'total_excuse' => null,
            'attendance_rate' => null,
        ];
    }
}
