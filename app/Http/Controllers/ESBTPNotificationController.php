<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\Notification;
use App\Models\User;
use App\Services\EvaluationGradingShortcutService;
use App\Services\EvaluationPublishShortcutService;
use App\Services\Notifications\NotificationPresenter;
use App\Services\TimetableShortcutService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ESBTPNotificationController extends Controller
{
    // Maximum number of notifications to keep per user
    protected const MAX_NOTIFICATIONS_PER_USER = 50;

    // Number of days to keep read notifications before pruning
    protected const DAYS_TO_KEEP_READ_NOTIFICATIONS = 7;

    // Lignes par page sur /notifications (« Charger plus » prend la suite).
    private const PER_PAGE = 20;

    // Filtres par sujet proposes aux coordinateurs (titre de la notification).
    private const SUJETS = ['émargement', 'appel', 'retard'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, NotificationPresenter $presenter)
    {
        $user = Auth::user();

        // Lignes et compteurs pour la page premium, sans rechargement. Teste
        // avant request()->ajax() : le menu de la barre du haut passe aussi en
        // AJAX et attend, lui, son propre fragment.
        if ($request->boolean('fragment')) {
            return $this->fragment($request, $user, $presenter);
        }

        $timetableShortcut = $this->getTimetableShortcutForUser($user);
        $evaluationShortcut = $this->getEvaluationShortcutForUser($user);
        $evaluationGradingShortcut = $this->getEvaluationGradingShortcutForUser($user);

        // Si la requête est AJAX (pour le dropdown), retourner une vue partielle
        if (request()->ajax()) {
            $notifications = Notification::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            return view('notifications.partials.dropdown-items', compact('notifications', 'timetableShortcut', 'evaluationShortcut', 'evaluationGradingShortcut'));
        }

        $filters = $this->filters($request);
        $notifications = $this->listing($user, $filters, $presenter);
        $counts = $this->counts($user);

        return view('notifications.index', compact(
            'notifications', 'counts', 'filters',
            'timetableShortcut', 'evaluationShortcut', 'evaluationGradingShortcut'
        ));
    }

    /**
     * Fragment JSON : les lignes d'une page (pour « Charger plus » et les
     * filtres), ou les compteurs seuls apres une action.
     */
    private function fragment(Request $request, User $user, NotificationPresenter $presenter): JsonResponse
    {
        $counts = $this->counts($user);
        if ($request->boolean('counts_only')) {
            return response()->json(['counts' => $counts]);
        }

        $filters = $this->filters($request);
        $notifications = $this->listing($user, $filters, $presenter);
        $after = (string) $request->query('after_group', '');

        return response()->json([
            'html' => view('notifications.partials.rows', [
                'notifications' => $notifications,
                'previousGroup' => array_key_exists($after, NotificationPresenter::GROUPES) ? $after : null,
            ])->render(),
            'next_url' => $this->nextUrl($notifications),
            'last_group' => optional($notifications->getCollection()->last())->display_group,
            'counts' => $counts,
            'total' => $notifications->total(),
        ]);
    }

    /**
     * @return array{filtre: string, type: string|null, periode: string|null, sujet: string|null}
     */
    private function filters(Request $request): array
    {
        $type = $request->query('type');
        $periode = $request->query('periode');
        $sujet = $request->query('sujet');

        return [
            'filtre' => $request->query('filtre') === 'non_lues' ? 'non_lues' : 'toutes',
            'type' => in_array($type, ['info', 'success', 'warning', 'alerte'], true) ? $type : null,
            'periode' => in_array($periode, ['aujourdhui', 'semaine'], true) ? $periode : null,
            'sujet' => in_array($sujet, self::SUJETS, true) ? $sujet : null,
        ];
    }

    private function listing(User $user, array $filters, NotificationPresenter $presenter): LengthAwarePaginator
    {
        $query = Notification::where('user_id', $user->id)->with('sender:id,name');

        if ($filters['filtre'] === 'non_lues') {
            $query->where(fn ($q) => $q->where('is_read', false)->orWhereNull('is_read'));
        }
        if ($filters['type'] === 'alerte') {
            $query->whereIn('type', ['error', 'danger']);
        } elseif ($filters['type'] === 'info') {
            $query->where(fn ($q) => $q->whereNotIn('type', ['success', 'warning', 'error', 'danger'])->orWhereNull('type'));
        } elseif ($filters['type']) {
            $query->where('type', $filters['type']);
        }
        if ($filters['periode'] === 'aujourdhui') {
            $query->where('created_at', '>=', now()->startOfDay());
        } elseif ($filters['periode'] === 'semaine') {
            $query->where('created_at', '>=', now()->startOfWeek());
        }
        if ($filters['sujet']) {
            $query->where('title', 'like', '%'.$filters['sujet'].'%');
        }

        $page = $query->latest()->latest('id')->paginate(self::PER_PAGE)->withQueryString();
        $page->setCollection($presenter->decorateAll($page->getCollection(), $user));

        return $page;
    }

    /**
     * @return array{total: int, unread: int, today: int, week: int, types: array<string, int>}
     */
    private function counts(User $user): array
    {
        $base = Notification::where('user_id', $user->id);

        $types = ['info' => 0, 'success' => 0, 'warning' => 0, 'alerte' => 0];
        foreach ((clone $base)->selectRaw('type, COUNT(*) as n')->groupBy('type')->pluck('n', 'type') as $type => $n) {
            $types[NotificationPresenter::typeOf($type)['key']] += (int) $n;
        }

        return [
            'total' => array_sum($types),
            'unread' => (clone $base)->where(fn ($q) => $q->where('is_read', false)->orWhereNull('is_read'))->count(),
            'today' => (clone $base)->where('created_at', '>=', now()->startOfDay())->count(),
            'week' => (clone $base)->where('created_at', '>=', now()->startOfWeek())->count(),
            'types' => $types,
        ];
    }

    private function nextUrl(LengthAwarePaginator $page): ?string
    {
        if (! $page->hasMorePages()) {
            return null;
        }

        return $page->appends([
            'fragment' => 1,
            'after_group' => optional($page->getCollection()->last())->display_group,
        ])->nextPageUrl();
    }

    private function getTimetableShortcutForUser(User $user): array
    {
        if (! $this->userCanSeeTimetableShortcut($user)) {
            return ['show' => false];
        }

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return ['show' => false];
        }

        return app(TimetableShortcutService::class)->getShortcutSummary($anneeEnCours);
    }

    private function getEvaluationShortcutForUser(User $user): array
    {
        if (! $this->userCanSeeEvaluationShortcut($user)) {
            return ['show' => false];
        }

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return ['show' => false];
        }

        return app(EvaluationPublishShortcutService::class)->getShortcutSummary($anneeEnCours);
    }

    private function getEvaluationGradingShortcutForUser(User $user): array
    {
        if (! $this->userCanSeeEvaluationGradingShortcut($user)) {
            return ['show' => false];
        }

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $anneeEnCours) {
            return ['show' => false];
        }

        return app(EvaluationGradingShortcutService::class)->getShortcutSummary($anneeEnCours, $user);
    }

    private function userCanSeeTimetableShortcut(User $user): bool
    {
        return $user->can('timetables.view') || $user->can('timetables.view_all');
    }

    private function userCanSeeEvaluationShortcut(User $user): bool
    {
        return $this->userCanAccessEvaluationsPage($user);
    }

    private function userCanSeeEvaluationGradingShortcut(User $user): bool
    {
        return $this->userCanAccessEvaluationsPage($user)
            || $this->userCanAccessNotesPage($user);
    }

    private function userCanAccessEvaluationsPage(User $user): bool
    {
        return $user->can('exams.view') || $user->can('evaluations.view');
    }

    private function userCanAccessNotesPage(User $user): bool
    {
        return $user->can('notes.view')
            || $user->can('notes.create')
            || $user->can('notes.edit')
            || $user->can('notes.manage_own');
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        $notification->update(['is_read' => true]);

        // Prune old read notifications
        $this->pruneOldReadNotifications($user);

        // Ensure we don't exceed the maximum notifications per user
        $this->limitUserNotifications($user);

        // Si la requête est AJAX et qu'elle vient du dropdown, supprimer la notification du DOM
        if (request()->ajax() && request()->header('X-Source') === 'dropdown') {
            return response()->json([
                'success' => true,
                'hide' => true,
                'id' => $id,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        Notification::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('is_read', false)
                    ->orWhereNull('is_read');
            })
            ->update(['is_read' => true]);

        // Prune old read notifications
        $this->pruneOldReadNotifications($user);

        return response()->json(['success' => true]);
    }

    public function getUnreadCount()
    {
        $user = Auth::user();
        $count = Notification::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('is_read', false)
                    ->orWhereNull('is_read');
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function delete($id)
    {
        $user = Auth::user();
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'Notification non trouvée'], 404);
        }

        $notification->delete();

        return response()->json(['success' => true, 'message' => 'Notification supprimée']);
    }

    /**
     * Remove old read notifications after a certain time period
     */
    protected function pruneOldReadNotifications($user)
    {
        // Supprimer les notifications lues il y a plus de 7 jours
        Notification::where('user_id', $user->id)
            ->where('is_read', true)
            ->where('updated_at', '<=', Carbon::now()->subDays(self::DAYS_TO_KEEP_READ_NOTIFICATIONS))
            ->delete();
    }

    /**
     * Ensure user doesn't have too many notifications by removing oldest ones
     */
    protected function limitUserNotifications($user)
    {
        $count = Notification::where('user_id', $user->id)->count();

        if ($count > self::MAX_NOTIFICATIONS_PER_USER) {
            $excess = $count - self::MAX_NOTIFICATIONS_PER_USER;

            // D'abord, supprimons les notifications lues les plus anciennes
            $readNotifications = Notification::where('user_id', $user->id)
                ->where('is_read', true)
                ->orderBy('created_at')
                ->limit($excess)
                ->get();

            foreach ($readNotifications as $notification) {
                $notification->delete();
                $excess--;
                if ($excess <= 0) {
                    break;
                }
            }

            // Si on a toujours des notifications en excès, supprimer les plus anciennes non lues
            if ($excess > 0) {
                $oldNotifications = Notification::where('user_id', $user->id)
                    ->orderBy('created_at')
                    ->limit($excess)
                    ->get();

                foreach ($oldNotifications as $notification) {
                    $notification->delete();
                }
            }
        }
    }
}
