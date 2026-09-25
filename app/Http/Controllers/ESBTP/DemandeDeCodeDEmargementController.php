<?php

declare(strict_types=1);

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPSeanceCours;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * « Demander le code » depuis l'écran d'émargement de l'enseignant.
 *
 * L'écran disait « demandez le code au coordinateur » sans rien offrir pour le
 * faire : l'enseignant appelait, ou n'émargeait pas. La demande prévient, dans
 * leur cloche, les personnes qui peuvent générer les codes du jour.
 */
final class DemandeDeCodeDEmargementController extends Controller
{
    /** Une demande par cours et par jour suffit : au-delà, c'est du bruit. */
    private const DELAI_ENTRE_DEMANDES_MINUTES = 10;

    public function __invoke(Request $request, NotificationService $notifications)
    {
        $data = $request->validate(['course_id' => 'required|integer|exists:esbtp_seance_cours,id']);

        $user = $request->user();
        $seance = ESBTPSeanceCours::with(['matiere', 'emploiTemps.classe'])->findOrFail($data['course_id']);
        abort_unless($user->teacherProfile && (int) $seance->teacher_id === (int) $user->teacherProfile->id, 403);

        $cle = 'demande-code-emargement:'.$user->id.':'.$seance->id.':'.now()->toDateString();
        if (Cache::has($cle)) {
            return back()->with('info', 'Votre demande a déjà été envoyée à la coordination. Elle vous communiquera le code du jour.');
        }

        $destinataires = User::permission('attendances.generate_codes')
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->get();

        if ($destinataires->isEmpty()) {
            return back()->with('error', 'Personne n’a le droit de générer les codes d’émargement dans cet établissement. Contactez l’administration.');
        }

        $heure = \App\Domain\EmploiTemps\HeureDeSeance::hi($seance->getAttributes()['heure_debut'] ?? null);
        $classe = $seance->emploiTemps->classe->name ?? null;
        $message = $user->name.' demande le code d’émargement du jour pour '
            .($seance->matiere->name ?? 'son cours')
            .($classe ? ' ('.$classe.')' : '')
            .($heure ? ' à '.$heure : '').'.';

        $envoyees = 0;
        foreach ($destinataires as $destinataire) {
            try {
                $notifications->createNotification(
                    $destinataire,
                    'Code d’émargement demandé',
                    $message,
                    'warning',
                    route('esbtp.attendance-codes.index'),
                    $user
                );
                $envoyees++;
            } catch (\Throwable $e) {
                Log::warning('Demande de code d’émargement : notification non envoyée', [
                    'destinataire' => $destinataire->id,
                    'seance' => $seance->id,
                    'erreur' => $e->getMessage(),
                ]);
            }
        }

        if ($envoyees === 0) {
            return back()->with('error', 'La demande n’a pas pu être envoyée. Réessayez dans un instant.');
        }

        Cache::put($cle, true, now()->addMinutes(self::DELAI_ENTRE_DEMANDES_MINUTES));

        return back()->with('success', 'Demande envoyée à la coordination ('.$envoyees.' personne'.($envoyees > 1 ? 's' : '').'). Vous serez prévenu dans votre cloche.');
    }
}
