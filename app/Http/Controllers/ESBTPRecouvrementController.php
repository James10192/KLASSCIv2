<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Cache\CachedPredictor;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\Predictors\DefaultRiskPredictor;
use App\Domain\Notifications\Channels\WhatsAppDeeplinkChannel;
use App\Domain\Notifications\EtudiantContact;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Services\RelanceActionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Page Recouvrement Optimizer : transforme la prédiction DefaultRisk en
 * liste actionnable du jour. Le comptable voit les étudiants prioritaires
 * et clique pour ouvrir WhatsApp / appel / email avec message pré-rempli.
 *
 * Architecture extensible : aujourd'hui canal `whatsapp_deeplink` (gratuit).
 * Quand adminKlassci paywall ready → bascule `whatsapp_business_api`
 * automatique via Strategy pattern (NotificationChannelInterface).
 */
class ESBTPRecouvrementController extends Controller
{
    private const TEMPLATE_DEFAULT = "Bonjour {prenom}, votre solde de scolarité de {solde} FCFA est en retard de {retard} jours. Merci de régulariser dès que possible. — {ecole}";

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('comptabilite.access');
        $this->middleware('can:comptabilite.recouvrement.access');
    }

    public function index(Request $request, DefaultRiskPredictor $predictor): View
    {
        $context = AnalyticsContext::fromRequest($request);
        $cached = new CachedPredictor($predictor, ttlSeconds: 1800);
        $prediction = $cached->predict($context);

        $topAtRisk = $prediction->metadata['top_at_risk'] ?? [];
        $contacts = $this->loadContacts(array_column($topAtRisk, 'inscription_id'));

        $intentLogger = app(\App\Services\RelanceActionLogger::class);
        $rows = collect($topAtRisk)
            ->map(function ($student) use ($contacts, $intentLogger) {
                $contact = $contacts[$student['inscription_id']] ?? null;
                if ($contact === null) {
                    Log::warning('Recouvrement: contact introuvable pour inscription at-risk', [
                        'inscription_id' => $student['inscription_id'],
                    ]);
                }
                return array_merge($student, [
                    'phone' => $contact?->phone,
                    'email' => $contact?->email,
                    'prenoms' => $contact?->prenoms ?? '',
                    'has_valid_phone' => $contact?->hasValidPhone() ?? false,
                    'relances_today' => $intentLogger->countIntentsToday($student['etudiant_id'] ?? 0),
                ]);
            })
            ->values()
            ->all();

        return view('esbtp.comptabilite.recouvrement.index', [
            'rows' => $rows,
            'totalActifs' => $prediction->metadata['total_actifs'] ?? 0,
            'buckets' => $prediction->metadata['buckets'] ?? [],
            'tauxRisque' => $prediction->metadata['taux_risque_pct'] ?? 0.0,
            'totalSoldeHaut' => $prediction->metadata['total_solde_haut_risque'] ?? 0.0,
            'context' => $context,
            'annees' => \App\Models\ESBTPAnneeUniversitaire::orderBy('name', 'desc')->get(),
            'filieres' => ESBTPFiliere::orderBy('name')->get(),
            'classes' => ESBTPClasse::orderBy('name')->get(),
            'whatsappTemplate' => $this->whatsappTemplate(),
            'schoolName' => SettingsHelper::get('school_name', config('app.name', 'KLASSCI')),
        ]);
    }

    /**
     * Enregistre une intention de relance via un canal donné. Retourne le
     * deeplink à ouvrir côté front (wa.me / mailto: / tel:).
     */
    public function logIntent(
        Request $request,
        WhatsAppDeeplinkChannel $whatsapp,
        RelanceActionLogger $logger,
    ): JsonResponse {
        $validated = $request->validate([
            'inscription_id' => 'required|integer|exists:esbtp_inscriptions,id',
            'channel' => 'required|in:whatsapp_deeplink,email,tel,manuel',
            'message' => 'required|string|max:2000',
        ]);

        $inscription = ESBTPInscription::with('etudiant:id,nom,prenoms,telephone,email')
            ->findOrFail($validated['inscription_id']);
        $etudiant = $inscription->etudiant;

        if (!$etudiant) {
            return response()->json(['success' => false, 'error' => 'Étudiant introuvable'], 404);
        }

        $contact = new EtudiantContact(
            etudiantId: (int) $etudiant->id,
            nomComplet: trim(($etudiant->prenoms ?? '') . ' ' . ($etudiant->nom ?? '')),
            phone: $etudiant->telephone,
            email: $etudiant->email,
            prenoms: $etudiant->prenoms,
            nom: $etudiant->nom,
        );

        $dispatch = match ($validated['channel']) {
            'whatsapp_deeplink' => $whatsapp->dispatch($contact, $validated['message']),
            'email' => $this->buildEmailDispatch($contact, $validated['message']),
            'tel' => $this->buildTelDispatch($contact),
            'manuel' => \App\Domain\Notifications\ChannelDispatch::manual('manuel', '#'),
        };

        try {
            $relance = $logger->logIntent($contact, (int) $inscription->id, $dispatch, $validated['message']);
        } catch (\Throwable $e) {
            Log::error('Recouvrement logIntent failed', [
                'error' => $e->getMessage(),
                'inscription_id' => $validated['inscription_id'],
            ]);
            return response()->json(['success' => false, 'error' => 'Erreur technique'], 500);
        }

        return response()->json([
            'success' => $dispatch->success,
            'channel' => $dispatch->channel,
            'deeplink_url' => $dispatch->deeplinkUrl,
            'error_reason' => $dispatch->errorReason,
            'relance_id' => $relance->id,
        ]);
    }

    /**
     * Confirme qu'une relance précédemment loggée en `intent` a effectivement
     * été envoyée. Utilisé par le bouton "Marqué relancé".
     */
    public function confirmSent(Request $request, RelanceActionLogger $logger): JsonResponse
    {
        $validated = $request->validate([
            'relance_id' => 'required|integer|exists:esbtp_relances,id',
        ]);

        try {
            $relance = $logger->confirmSent((int) $validated['relance_id']);
        } catch (\Throwable $e) {
            Log::error('Recouvrement confirmSent failed', [
                'error' => $e->getMessage(),
                'relance_id' => $validated['relance_id'],
            ]);
            return response()->json(['success' => false, 'error' => 'Erreur technique'], 500);
        }

        return response()->json([
            'success' => true,
            'confirmed_at' => $relance->confirmee_a?->toISOString(),
        ]);
    }

    /**
     * Crée une relance "marquée relancée" en un seul clic (sans intent log
     * canal). Utile quand le comptable a relancé hors-app (appel, en personne).
     */
    public function markDone(
        Request $request,
        RelanceActionLogger $logger,
    ): JsonResponse {
        $validated = $request->validate([
            'inscription_id' => 'required|integer|exists:esbtp_inscriptions,id',
            'note' => 'nullable|string|max:500',
        ]);

        $inscription = ESBTPInscription::with('etudiant:id,nom,prenoms,telephone,email')
            ->findOrFail($validated['inscription_id']);
        $etudiant = $inscription->etudiant;

        $contact = new EtudiantContact(
            etudiantId: (int) ($etudiant?->id ?? 0),
            nomComplet: trim(($etudiant?->prenoms ?? '') . ' ' . ($etudiant?->nom ?? '')),
            phone: $etudiant?->telephone,
            email: $etudiant?->email,
            prenoms: $etudiant?->prenoms,
            nom: $etudiant?->nom,
        );

        $relance = $logger->logSent(
            $contact,
            (int) $inscription->id,
            $validated['note'] ?? 'Relancé manuellement',
        );

        return response()->json([
            'success' => true,
            'relance_id' => $relance->id,
        ]);
    }

    /**
     * @return array<int, EtudiantContact>  keyed by inscription_id
     */
    private function loadContacts(array $inscriptionIds): array
    {
        if (empty($inscriptionIds)) {
            return [];
        }

        return ESBTPInscription::with('etudiant:id,nom,prenoms,telephone,email')
            ->whereIn('id', $inscriptionIds)
            ->get()
            ->mapWithKeys(function (ESBTPInscription $inscription) {
                $etudiant = $inscription->etudiant;
                if (!$etudiant) {
                    return [$inscription->id => null];
                }
                return [
                    $inscription->id => new EtudiantContact(
                        etudiantId: (int) $etudiant->id,
                        nomComplet: trim(($etudiant->prenoms ?? '') . ' ' . ($etudiant->nom ?? '')),
                        phone: $etudiant->telephone,
                        email: $etudiant->email,
                        prenoms: $etudiant->prenoms,
                        nom: $etudiant->nom,
                    ),
                ];
            })
            ->filter()
            ->all();
    }

    private function whatsappTemplate(): string
    {
        $template = SettingsHelper::get('analytics.recouvrement.whatsapp_template');
        return is_string($template) && trim($template) !== '' ? $template : self::TEMPLATE_DEFAULT;
    }

    private function buildEmailDispatch(EtudiantContact $contact, string $message): \App\Domain\Notifications\ChannelDispatch
    {
        if (!$contact->hasEmail()) {
            return \App\Domain\Notifications\ChannelDispatch::unavailable('email', 'Email étudiant manquant ou invalide');
        }
        $subject = rawurlencode('Solde de scolarité');
        $body = rawurlencode($message);
        return \App\Domain\Notifications\ChannelDispatch::manual(
            'email',
            "mailto:{$contact->email}?subject={$subject}&body={$body}",
        );
    }

    private function buildTelDispatch(EtudiantContact $contact): \App\Domain\Notifications\ChannelDispatch
    {
        $e164 = \App\Domain\Notifications\PhoneNormalizer::toE164($contact->phone);
        if ($e164 === null) {
            return \App\Domain\Notifications\ChannelDispatch::unavailable('tel', 'Numéro de téléphone invalide');
        }
        return \App\Domain\Notifications\ChannelDispatch::manual('tel', "tel:{$e164}");
    }
}
