<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Notification; // Notre modèle personnalisé
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Services\TimetableShortcutService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ESBTPNotificationController extends Controller
{
    // Maximum number of notifications to keep per user
    protected const MAX_NOTIFICATIONS_PER_USER = 50;

    // Number of days to keep read notifications before pruning
    protected const DAYS_TO_KEEP_READ_NOTIFICATIONS = 7;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $timetableShortcut = $this->getTimetableShortcutForUser($user);

        // Si la requête est AJAX (pour le dropdown), retourner une vue partielle
        if (request()->ajax()) {
            $notifications = Notification::where('user_id', $user->id)
                ->latest()
                ->take(5)
                ->get();
            return view('notifications.partials.dropdown-items', compact('notifications', 'timetableShortcut'));
        }

        // Sinon, retourner la vue complète avec pagination
        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        $notifications->setCollection(
            $notifications->getCollection()->map(function ($notification) {
                return $this->decorateNotification($notification);
            })
        );

        return view('notifications.index', compact('notifications', 'timetableShortcut'));
    }

    private function getTimetableShortcutForUser(User $user): array
    {
        if (!$this->userCanSeeTimetableShortcut($user)) {
            return ['show' => false];
        }

        $anneeEnCours = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$anneeEnCours) {
            return ['show' => false];
        }

        return app(TimetableShortcutService::class)->getShortcutSummary($anneeEnCours);
    }

    private function userCanSeeTimetableShortcut(User $user): bool
    {
        return $user->hasRole('superAdmin')
            || $user->hasRole('secretaire')
            || $user->hasRole('coordinateur')
            || $user->can('view_timetables')
            || $user->can('view-all-timetables');
    }

    private function decorateNotification(Notification $notification): Notification
    {
        $notification->display_primary = null;
        $notification->display_labels = [];
        $notification->display_cta = null;

        if ($notification->link && preg_match('/inscriptions\\/(\\d+)/', $notification->link, $matches)) {
            $inscriptionId = (int) $matches[1];
            $inscription = ESBTPInscription::with(['etudiant', 'classe.filiere', 'paiements'])
                ->find($inscriptionId);

            if ($inscription) {
                $studentName = trim(($inscription->etudiant->nom ?? '') . ' ' . ($inscription->etudiant->prenoms ?? ''));
                $filiereName = $inscription->classe?->filiere?->name;
                $primary = "L'étudiant {$studentName} s'est inscrit";
                if ($filiereName) {
                    $primary .= " en {$filiereName}";
                }
                $primary .= '.';

                $labels = [];
                if ($inscription->classe?->name) {
                    $labels[] = "Classe: {$inscription->classe->name}";
                }
                $labels[] = 'Statut: ' . ($inscription->status ?? 'Non défini');
                $labels[] = 'Étape: ' . ($inscription->workflow_step_label ?? $inscription->workflow_step ?? 'Non définie');

                $paymentStatus = 'Non renseigné';
                $latestPayment = $inscription->paiements?->sortByDesc('created_at')->first();
                if ($latestPayment && $latestPayment->status) {
                    $paymentStatus = Str::ucfirst(str_replace('_', ' ', $latestPayment->status));
                }
                $labels[] = "Paiement: {$paymentStatus}";

                $notification->display_primary = $primary;
                $notification->display_labels = $labels;
                $notification->display_cta = 'Cliquez pour consulter le dossier complet.';
            }
        }

        return $notification;
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
                'id' => $id
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

        if (!$notification) {
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
                if ($excess <= 0) break;
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
