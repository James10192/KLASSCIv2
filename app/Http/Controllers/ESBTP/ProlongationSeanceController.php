<?php

namespace App\Http\Controllers\ESBTP;

use App\Domain\EmploiTemps\ProlongationDeSeance;
use App\Http\Controllers\Controller;
use App\Models\ESBTPProlongationSeance;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Prolonger un cours : l'enseignant demande, la coordination décide.
 * Toute la règle vit dans `ProlongationDeSeance` ; ici on orchestre.
 */
class ProlongationSeanceController extends Controller
{
    public function __construct(private readonly ProlongationDeSeance $prolongations) {}

    /** Demande de l'enseignant, depuis l'écran de gestion de la séance. */
    public function demander(Request $request, ESBTPSeanceCours $seance)
    {
        $data = $request->validate([
            'minutes' => 'required|integer|min:5|max:'.ProlongationDeSeance::MINUTES_MAX,
            'motif' => 'required|string|min:5|max:500',
        ]);

        $user = $request->user();
        abort_unless($user->teacherProfile && (int) $seance->teacher_id === (int) $user->teacherProfile->id, 403);

        try {
            $prolongation = $this->prolongations->demander($seance, $user, (int) $data['minutes'], $data['motif']);
        } catch (ValidationException $e) {
            return $this->repondre($request, false, collect($e->errors())->flatten()->first(), null, 422);
        }

        // Les décideurs voient la demande dans leur cloche ; la liste des
        // demandes reste la source de vérité si l'envoi échoue.
        try {
            $decideurs = User::permission('emargement.prolongation.decide')->where('id', '!=', $user->id)->get();
            $notifications = app(NotificationService::class);
            foreach ($decideurs as $decideur) {
                $notifications->createNotification(
                    $decideur,
                    'Demande de prolongation de cours',
                    $user->name.' demande '.$prolongation->minutes.' min pour '.($seance->matiere->name ?? 'un cours').' : '.$prolongation->motif,
                    'warning',
                    route('esbtp.prolongations.index'),
                    $user
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Prolongation : notification non envoyée', ['prolongation' => $prolongation->id, 'erreur' => $e->getMessage()]);
        }

        return $this->repondre($request, true, 'Demande envoyée à la coordination. Vous serez prévenu de la décision.', $prolongation);
    }

    /** Liste des demandes, pour la personne habilitée. */
    public function index(Request $request)
    {
        $prolongations = ESBTPProlongationSeance::with(['seance.matiere', 'seance.emploiTemps.classe', 'demandeur', 'decideur'])
            ->orderByRaw("statut = 'en_attente' desc")
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $conflits = $prolongations
            ->where('statut', ESBTPProlongationSeance::EN_ATTENTE)
            ->mapWithKeys(fn ($p) => [$p->id => $this->prolongations->conflits($p)]);

        return view('esbtp.prolongations.index', compact('prolongations', 'conflits'));
    }

    public function accorder(Request $request, ESBTPProlongationSeance $prolongation)
    {
        try {
            $this->prolongations->accorder($prolongation, $request->user());
        } catch (ValidationException $e) {
            $this->prevenir($prolongation->fresh());

            return $this->repondre($request, false, 'Prolongation impossible : '.implode(' ', collect($e->errors())->flatten()->all()), $prolongation->fresh(), 422);
        }
        $this->prevenir($prolongation->fresh());

        return $this->repondre($request, true, 'Prolongation accordée jusqu’à '.substr((string) $prolongation->heure_fin_demandee, 0, 5).'.', $prolongation->fresh());
    }

    public function refuser(Request $request, ESBTPProlongationSeance $prolongation)
    {
        $data = $request->validate(['motif' => 'nullable|string|max:500']);

        try {
            $this->prolongations->refuser($prolongation, $request->user(), $data['motif'] ?? null);
        } catch (ValidationException $e) {
            return $this->repondre($request, false, collect($e->errors())->flatten()->first(), $prolongation, 422);
        }
        $this->prevenir($prolongation->fresh());

        return $this->repondre($request, true, 'Demande refusée.', $prolongation->fresh());
    }

    private function prevenir(ESBTPProlongationSeance $prolongation): void
    {
        if (! $prolongation->demandeur) {
            return;
        }
        try {
            app(NotificationService::class)->createNotification(
                $prolongation->demandeur,
                'Prolongation '.mb_strtolower($prolongation->libelleStatut()),
                $prolongation->statut === ESBTPProlongationSeance::ACCORDEE
                    ? 'Votre cours est prolongé jusqu’à '.substr((string) $prolongation->heure_fin_demandee, 0, 5).'.'
                    : 'Votre demande n’a pas été accordée'.($prolongation->motif_decision ? ' : '.$prolongation->motif_decision : '.'),
                $prolongation->statut === ESBTPProlongationSeance::ACCORDEE ? 'success' : 'warning',
                route('teacher.select-call-type', $prolongation->seance_cours_id)
            );
        } catch (\Throwable $e) {
            Log::warning('Prolongation : décision non notifiée', ['prolongation' => $prolongation->id, 'erreur' => $e->getMessage()]);
        }
    }

    private function repondre(Request $request, bool $ok, ?string $message, ?ESBTPProlongationSeance $prolongation, int $code = 200)
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'success' => $ok,
                'message' => $message,
                'prolongation' => $prolongation ? [
                    'id' => $prolongation->id,
                    'statut' => $prolongation->statut,
                    'statut_libelle' => $prolongation->libelleStatut(),
                    'heure_fin' => substr((string) $prolongation->heure_fin_demandee, 0, 5),
                    'motif_decision' => $prolongation->motif_decision,
                ] : null,
            ], $code);
        }

        return back()->with($ok ? 'success' : 'error', $message);
    }
}
