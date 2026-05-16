<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tpe\RejectTpeDeclarationRequest;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPlanificationAcademique;
use App\Models\ESBTPTpeDeclaration;
use App\Models\User;
use App\Notifications\TpeDeclarationStatusChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * Validation TPE — vue enseignant.
 *
 * DORMANT par défaut : tant que Setting `tpe.validation.enabled = false`,
 * AutoValidateStrategy marque les déclarations VALIDE direct → aucune
 * déclaration n'apparaît ici (statut EN_ATTENTE inexistant). C'est attendu.
 *
 * Quand `tpe.validation.enabled = true` :
 *  - Les nouvelles déclarations arrivent EN_ATTENTE
 *  - Le prof voit la liste filtrée à SES ECUEs (scope `pourEnseignant`)
 *  - Bouton Valider / Rejeter inline avec modal commentaire
 */
class ESBTPTpeValidationController extends Controller
{
    /**
     * Liste les déclarations en_attente filtrées par enseignant principal.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $declarationsEnAttente = ESBTPTpeDeclaration::query()
            ->enAttente()
            ->pourEnseignant($user)
            ->with([
                'etudiant:id,nom,prenoms,user_id,photo_url',
                'matiere:id,name,unite_enseignement_id',
                'matiere.uniteEnseignement:id,name',
            ])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // KPIs : compteurs cours/semaine/mois pour le hero
        $kpiBase = ESBTPTpeDeclaration::query()->pourEnseignant($user);
        $kpis = [
            'en_attente' => (clone $kpiBase)->enAttente()->count(),
            'validees_semaine' => (clone $kpiBase)
                ->valide()
                ->where('validated_at', '>=', now()->startOfWeek())
                ->count(),
            'rejetees_mois' => (clone $kpiBase)
                ->rejete()
                ->where('validated_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        // Liste des ECUEs dont l'enseignant est responsable (pour filtre éventuel)
        $myEcueIds = ESBTPPlanificationAcademique::query()
            ->where('enseignant_principal_id', $user->id)
            ->where('is_active', true)
            ->pluck('matiere_id')
            ->unique();

        $myEcues = ESBTPMatiere::query()
            ->whereIn('id', $myEcueIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('esbtp.tpe-validation.index', [
            'declarations' => $declarationsEnAttente,
            'kpis' => $kpis,
            'myEcues' => $myEcues,
        ]);
    }

    /**
     * Valide une déclaration. Notifie l'étudiant.
     */
    public function approve(Request $request, ESBTPTpeDeclaration $declaration): RedirectResponse
    {
        $user = $request->user();

        if (! $declaration->canBeValidatedBy($user)) {
            abort(403, 'Vous ne pouvez pas valider cette déclaration.');
        }

        $declaration->markValidatedBy($user);

        $this->notifyStudent($declaration);

        return back()->with('success', 'Déclaration validée.');
    }

    /**
     * Rejette une déclaration avec commentaire obligatoire. Notifie l'étudiant.
     */
    public function reject(RejectTpeDeclarationRequest $request, ESBTPTpeDeclaration $declaration): RedirectResponse
    {
        $declaration->markRejectedBy(
            $request->user(),
            $request->validated('commentaire_rejet'),
        );

        $this->notifyStudent($declaration);

        return back()->with('success', 'Déclaration rejetée. L\'étudiant a été notifié.');
    }

    /**
     * Notifie l'étudiant du changement de statut (via User attaché au profil étudiant).
     */
    private function notifyStudent(ESBTPTpeDeclaration $declaration): void
    {
        $declaration->loadMissing('etudiant');
        $userId = $declaration->etudiant?->user_id;
        if (! $userId) {
            return;
        }
        $user = User::find($userId);
        if (! $user) {
            return;
        }
        Notification::send(
            $user,
            new TpeDeclarationStatusChangedNotification($declaration->fresh(), $declaration->statut),
        );
    }
}
