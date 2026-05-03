<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Cache\CachedPredictor;
use App\Domain\Analytics\DTOs\AnalyticsContext;
use App\Domain\Analytics\Predictors\DefaultRiskPredictor;
use App\Domain\Exports\Reports\RecouvrementReport;
use App\Domain\Notifications\ChannelDispatch;
use App\Domain\Notifications\Channels\WhatsAppDeeplinkChannel;
use App\Domain\Notifications\EtudiantContact;
use App\Domain\Notifications\PhoneNormalizer;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Services\ExportRenderer;
use App\Services\RelanceActionLogger;
use Illuminate\Http\JsonResponse;
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

    public function previewPdf(Request $request, DefaultRiskPredictor $predictor, ExportRenderer $renderer): \Symfony\Component\HttpFoundation\Response
    {
        return $renderer->pdfPreview($this->buildReport($request, $predictor));
    }

    public function exportPdf(Request $request, DefaultRiskPredictor $predictor, ExportRenderer $renderer): \Symfony\Component\HttpFoundation\Response
    {
        return $renderer->pdfDownload($this->buildReport($request, $predictor));
    }

    public function exportExcel(Request $request, DefaultRiskPredictor $predictor, ExportRenderer $renderer): \Symfony\Component\HttpFoundation\Response
    {
        return $renderer->excelDownload($this->buildReport($request, $predictor));
    }

    public function emailPdf(Request $request, DefaultRiskPredictor $predictor, ExportRenderer $renderer): JsonResponse
    {
        $to = trim((string) $request->input('to', '')) ?: ($request->user()?->email);
        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['success' => false, 'message' => 'Adresse email invalide'], 422);
        }
        try {
            $renderer->emailPdf($this->buildReport($request, $predictor), $to, $request->user()?->name);
            return response()->json(['success' => true, 'message' => "Rapport en cours d'envoi à {$to}"]);
        } catch (\Throwable $e) {
            Log::error('Recouvrement emailPdf failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Erreur lors de l\'envoi'], 500);
        }
    }

    private function buildReport(Request $request, DefaultRiskPredictor $predictor): RecouvrementReport
    {
        [$context, $prediction, $topAtRisk, $contacts] = $this->loadPrediction($request, $predictor);

        $rows = collect($topAtRisk)->map(fn ($student) => $this->enrichStudentRow($student, $contacts, withMeta: false));

        // Filtres UI appliqués server-side pour cohérence preview/download avec la vue
        $level = $request->query('level');
        $retardMin = (int) $request->query('retard_min', 0);
        $search = trim((string) $request->query('search', ''));

        if ($level) {
            $rows = $rows->where('level', $level);
        }
        if ($retardMin > 0) {
            $rows = $rows->where('jours_retard', '>=', $retardMin);
        }
        if ($search !== '') {
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['etudiant_nom'] ?? ''), mb_strtolower($search)));
        }

        $appliedFilters = array_filter([
            'Niveau' => $level ? mb_strtoupper(mb_substr($level, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($level, 1, null, 'UTF-8') : null,
            'Retard min.' => $retardMin > 0 ? "{$retardMin} jours" : null,
            'Recherche' => $search !== '' ? $search : null,
            'Filière' => $context->filiereId ? optional(ESBTPFiliere::find($context->filiereId))->name : null,
            'Classe' => $context->classeId ? optional(ESBTPClasse::find($context->classeId))->name : null,
        ]);

        return new RecouvrementReport(
            rows: $rows->values()->all(),
            appliedFilters: $appliedFilters,
            kpis: $prediction->metadata ?? [],
        );
    }

    public function index(Request $request, DefaultRiskPredictor $predictor): View
    {
        [$context, $prediction, $topAtRisk, $contacts] = $this->loadPrediction($request, $predictor);

        $intentLogger = app(RelanceActionLogger::class);
        $rows = collect($topAtRisk)
            ->map(fn ($student) => $this->enrichStudentRow($student, $contacts, withMeta: true, intentLogger: $intentLogger))
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
     * Charge la prédiction (cachée 30 min) + les contacts associés. Évite la
     * duplication entre `index()` (vue HTML) et `buildReport()` (exports PDF/Excel).
     *
     * @return array{0: AnalyticsContext, 1: \App\Domain\Analytics\DTOs\PredictionResult, 2: array, 3: array<int, EtudiantContact>}
     */
    private function loadPrediction(Request $request, DefaultRiskPredictor $predictor): array
    {
        $context = AnalyticsContext::fromRequest($request);
        $cached = new CachedPredictor($predictor, ttlSeconds: 1800);
        $prediction = $cached->predict($context);

        $topAtRisk = $prediction->metadata['top_at_risk'] ?? [];
        $contacts = $this->loadContacts(array_column($topAtRisk, 'inscription_id'));

        return [$context, $prediction, $topAtRisk, $contacts];
    }

    /**
     * Enrichit une ligne `top_at_risk` avec contact (phone/email) et,
     * optionnellement, métadonnées UI (prénoms, validité phone, relances du jour).
     *
     * @param  array<int, EtudiantContact>  $contacts  Indexés par inscription_id.
     */
    private function enrichStudentRow(
        array $student,
        array $contacts,
        bool $withMeta,
        ?RelanceActionLogger $intentLogger = null,
    ): array {
        $contact = $contacts[$student['inscription_id']] ?? null;

        if ($withMeta && $contact === null) {
            Log::warning('Recouvrement: contact introuvable pour inscription at-risk', [
                'inscription_id' => $student['inscription_id'],
            ]);
        }

        $base = array_merge($student, [
            'phone' => $contact?->phone,
            'email' => $contact?->email,
        ]);

        if (!$withMeta) {
            return $base;
        }

        return array_merge($base, [
            'prenoms' => $contact?->prenoms ?? '',
            'has_valid_phone' => $contact?->hasValidPhone() ?? false,
            'relances_today' => $intentLogger?->countIntentsToday($student['etudiant_id'] ?? 0) ?? 0,
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
        $contact = EtudiantContact::fromEtudiant($inscription->etudiant);

        if ($contact === null) {
            return response()->json(['success' => false, 'error' => 'Étudiant introuvable'], 404);
        }

        $dispatch = match ($validated['channel']) {
            'whatsapp_deeplink' => $whatsapp->dispatch($contact, $validated['message']),
            'email' => $this->buildEmailDispatch($contact, $validated['message']),
            'tel' => $this->buildTelDispatch($contact),
            'manuel' => ChannelDispatch::manual('manuel', '#'),
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
        $contact = EtudiantContact::fromEtudiant($inscription->etudiant)
            ?? new EtudiantContact(0, '', null, null);

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
            ->mapWithKeys(fn (ESBTPInscription $inscription) => [
                $inscription->id => EtudiantContact::fromEtudiant($inscription->etudiant),
            ])
            ->filter()
            ->all();
    }

    private function whatsappTemplate(): string
    {
        $template = SettingsHelper::get('analytics.recouvrement.whatsapp_template');
        return is_string($template) && trim($template) !== '' ? $template : self::TEMPLATE_DEFAULT;
    }

    private function buildEmailDispatch(EtudiantContact $contact, string $message): ChannelDispatch
    {
        if (!$contact->hasEmail()) {
            return ChannelDispatch::unavailable('email', 'Email étudiant manquant ou invalide');
        }
        $subject = rawurlencode('Solde de scolarité');
        $body = rawurlencode($message);
        return ChannelDispatch::manual('email', "mailto:{$contact->email}?subject={$subject}&body={$body}");
    }

    private function buildTelDispatch(EtudiantContact $contact): ChannelDispatch
    {
        $e164 = PhoneNormalizer::toE164($contact->phone);
        if ($e164 === null) {
            return ChannelDispatch::unavailable('tel', 'Numéro de téléphone invalide');
        }
        return ChannelDispatch::manual('tel', "tel:{$e164}");
    }
}
