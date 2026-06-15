<?php

namespace App\Http\Controllers;

use App\Domain\Comptabilite\Paie\PayrollComputationService;
use App\Domain\Exports\Reports\PaiePayrollReport;
use App\Enums\TypeSeance;
use App\Services\ExportRenderer;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPSalaire;
use App\Models\ESBTPSalaireDetail;
use App\Models\ESBTPTeacher;
use App\Services\TeacherHoursService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Paie enseignants (comptabilité). Page dédiée, séparée de la pédagogie.
 *
 * Workflow OHADA : préparer (create) → valider (validate, séparation des devoirs)
 * → payer (pay). Retenues itemisées dont impôt ITS auto (barème configurable).
 */
class ESBTPSalaireController extends Controller
{
    public function __construct(
        private PayrollComputationService $payroll,
        private TeacherHoursService $hours,
    ) {
    }

    private const MOIS_FR = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function index(Request $request)
    {
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        $filtres = $this->filtres($request);

        $recap = $this->buildRecap($filtres);
        $kpis = $this->recapKpis($recap);

        $teachers = ESBTPTeacher::with('user:id,name')->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->user->name ?? $t->name ?? 'Enseignant'])
            ->sortBy('name')->values();

        return view('esbtp.comptabilite.salaires.index', [
            'recap'       => $recap,
            'kpis'        => $kpis,
            'filtres'     => $filtres,
            'annee'       => $annee,
            'teachers'    => $teachers,
            'moisOptions' => self::MOIS_FR,
            'statutLabels'=> $this->statutLabels(),
            'canCreate'   => auth()->user()->can('comptabilite.salaires.create'),
            'canConfigure'=> auth()->user()->can('comptabilite.salaires.configure'),
            'canExport'   => auth()->user()->can('comptabilite.salaires.export'),
            'cnpsTaux'    => $this->payroll->tauxCnps(),
            'bareme'      => $this->payroll->baremeIts(),
        ]);
    }

    /** AJAX : récap filtré + KPIs (no-reload). */
    public function data(Request $request)
    {
        $filtres = $this->filtres($request);
        $recap = $this->buildRecap($filtres);
        $kpis = $this->recapKpis($recap);

        return response()->json([
            'list_html' => view('esbtp.comptabilite.salaires.partials._recap', [
                'recap'        => $recap,
                'statutLabels' => $this->statutLabels(),
                'canCreate'    => auth()->user()->can('comptabilite.salaires.create'),
            ])->render(),
            'kpis_html' => view('esbtp.comptabilite.salaires.partials._kpis', ['kpis' => $kpis])->render(),
            'period_label' => (self::MOIS_FR[$filtres['mois']] ?? '') . ' ' . $filtres['annee'],
        ]);
    }

    private function filtres(Request $request): array
    {
        return [
            'mois'   => (int) $request->get('mois', now()->month),
            'annee'  => (int) $request->get('annee', now()->year),
            'statut' => $request->get('statut'),
            'q'      => trim((string) $request->get('q', '')),
        ];
    }

    /** Libellés statut incluant le pseudo-statut « à préparer ». */
    private function statutLabels(): array
    {
        return ['a_preparer' => 'À préparer'] + ESBTPSalaire::statutLabels();
    }

    /**
     * Récapitulatif paie : TOUS les enseignants avec heures facturables ce mois,
     * leur montant estimé (heures × taux − ITS/CNPS), fusionné avec les bulletins
     * déjà préparés (statut + net réel). C'est la vue « ce qu'on doit verser ».
     *
     * @return array<int, array<string,mixed>>
     */
    private function buildRecap(array $filtres): array
    {
        [$from, $to] = $this->moisRange($filtres['mois'], $filtres['annee']);
        $report = $this->hours->report($from, $to);

        $teachers = ESBTPTeacher::with(['tauxSeances', 'user:id,name'])->get()->keyBy('id');
        $bulletins = ESBTPSalaire::where('mois', $filtres['mois'])
            ->where('annee', $filtres['annee'])->get()->keyBy('teacher_id');

        $cnpsTaux = $this->payroll->tauxCnps();
        $rows = [];
        $seen = [];

        foreach ($report['enseignants'] as $ens) {
            $teacher = $teachers->get($ens['teacher_id']);
            if (!$teacher) {
                continue;
            }
            $base = 0.0;
            $heures = 0.0;
            $types = [];
            foreach ($ens['par_type'] as $pt) {
                if (!$pt['facturable'] || $pt['heures_realisees'] <= 0) {
                    continue;
                }
                $taux = $teacher->tauxPour($pt['type']);
                $base += $pt['heures_realisees'] * $taux;
                $heures += $pt['heures_realisees'];
                $types[] = [
                    'type' => $pt['type'], 'icon' => $pt['icon'], 'style' => $pt['style'],
                    'heures' => $pt['heures_realisees'], 'taux' => $taux,
                ];
            }
            $base = round($base, 2);
            $bulletin = $bulletins->get($ens['teacher_id']);
            if ($base <= 0 && !$bulletin) {
                continue;
            }
            $its = $this->payroll->computeIts($base);
            $cnps = round($base * $cnpsTaux / 100, 2);

            $rows[] = $this->recapRow($ens['teacher_id'], $ens['name'], $heures, $types, $base, $its, $cnps, $bulletin);
            $seen[$ens['teacher_id']] = true;
        }

        // Bulletins dont l'enseignant n'a pas d'heures dans le report (ex: saisie manuelle).
        foreach ($bulletins as $tid => $bulletin) {
            if (isset($seen[$tid])) {
                continue;
            }
            $teacher = $teachers->get($tid);
            $name = $teacher?->user?->name ?? $teacher?->name ?? 'Enseignant';
            $rows[] = $this->recapRow($tid, $name, (float) $bulletin->heures_total, [], (float) $bulletin->salaire_base, (float) $bulletin->impot_its, (float) $bulletin->cnps, $bulletin);
        }

        // Filtres statut + recherche.
        if (!empty($filtres['statut'])) {
            $rows = array_values(array_filter($rows, fn ($r) => $r['statut'] === $filtres['statut']));
        }
        if ($filtres['q'] !== '') {
            $q = mb_strtolower($filtres['q']);
            $rows = array_values(array_filter($rows, fn ($r) => str_contains(mb_strtolower($r['name']), $q)));
        }

        usort($rows, fn ($a, $b) => $b['net'] <=> $a['net']);

        return $rows;
    }

    /** Construit une ligne de récap (statut = bulletin ou « à préparer »). */
    private function recapRow($teacherId, string $name, float $heures, array $types, float $base, float $its, float $cnps, ?ESBTPSalaire $bulletin): array
    {
        $netEstime = round($base - $its - $cnps, 2);

        return [
            'teacher_id'  => (int) $teacherId,
            'name'        => $name,
            'heures'      => round($heures, 2),
            'types'       => $types,
            'base'        => $base,
            'retenues'    => $bulletin ? (float) $bulletin->retenues : round($its + $cnps, 2),
            'net'         => $bulletin ? (float) $bulletin->net_a_payer : $netEstime,
            'estimation'  => !$bulletin,
            'has_bulletin'=> (bool) $bulletin,
            'bulletin_id' => $bulletin?->id,
            'statut'      => $bulletin ? $bulletin->workflow_status : 'a_preparer',
        ];
    }

    /**
     * KPIs depuis le récap.
     *
     * @param  array<int, array<string,mixed>>  $recap
     * @return array<string, float|int>
     */
    private function recapKpis(array $recap): array
    {
        $sum = fn ($pred) => array_sum(array_map(fn ($r) => $r['net'], array_filter($recap, $pred)));
        $count = fn ($pred) => count(array_filter($recap, $pred));

        return [
            'total_net'    => round(array_sum(array_column($recap, 'net')), 2),
            'nb_total'     => count($recap),
            'nb_a_preparer'=> $count(fn ($r) => $r['statut'] === 'a_preparer'),
            'nb_brouillon' => $count(fn ($r) => $r['statut'] === ESBTPSalaire::ST_BROUILLON),
            'nb_valide'    => $count(fn ($r) => $r['statut'] === ESBTPSalaire::ST_VALIDE),
            'nb_paye'      => $count(fn ($r) => $r['statut'] === ESBTPSalaire::ST_PAYE),
            'net_paye'     => round($sum(fn ($r) => $r['statut'] === ESBTPSalaire::ST_PAYE), 2),
            'net_a_preparer' => round($sum(fn ($r) => $r['statut'] === 'a_preparer'), 2),
        ];
    }

    // ── Exports (PDF / Excel) ───────────────────────────────
    private function buildReport(Request $request): PaiePayrollReport
    {
        $filtres = $this->filtres($request);
        $recap = $this->buildRecap($filtres);
        $kpis = $this->recapKpis($recap);
        $periodLabel = (self::MOIS_FR[$filtres['mois']] ?? '') . ' ' . $filtres['annee'];

        return new PaiePayrollReport($recap, $kpis, [
            'Période' => $periodLabel,
            'Statut'  => $filtres['statut'] ? ($this->statutLabels()[$filtres['statut']] ?? $filtres['statut']) : 'Tous',
        ], $periodLabel, $this->statutLabels());
    }

    public function previewPdf(Request $request, ExportRenderer $renderer)
    {
        return $renderer->pdfPreview($this->buildReport($request));
    }

    public function exportPdf(Request $request, ExportRenderer $renderer)
    {
        return $renderer->pdfDownload($this->buildReport($request));
    }

    public function exportExcel(Request $request, ExportRenderer $renderer)
    {
        return $renderer->excelDownload($this->buildReport($request));
    }

    /** AJAX : calcule un aperçu de bulletin (sans persistance). */
    public function prepare(Request $request)
    {
        $data = $request->validate([
            'teacher_id'         => 'required|exists:esbtp_teachers,id',
            'mois'               => 'required|integer|min:1|max:12',
            'annee'              => 'required|integer|min:2000|max:2100',
            'impot_its'          => 'nullable|numeric|min:0',
            'cnps'               => 'nullable|numeric|min:0',
            'primes'             => 'nullable|array',
            'primes.*.libelle'   => 'required_with:primes|string|max:120',
            'primes.*.montant'   => 'required_with:primes|numeric|min:0',
            'retenues'           => 'nullable|array',
            'retenues.*.type'    => 'nullable|string|max:20',
            'retenues.*.libelle' => 'required_with:retenues|string|max:120',
            'retenues.*.montant' => 'required_with:retenues|numeric|min:0',
        ]);

        $teacher = ESBTPTeacher::findOrFail($data['teacher_id']);
        [$from, $to] = $this->moisRange($data['mois'], $data['annee']);

        $preview = $this->payroll->computePreview($teacher, $from, $to, [
            'impot_its' => $data['impot_its'] ?? null,
            'cnps'      => $data['cnps'] ?? null,
            'primes'    => $data['primes'] ?? [],
            'retenues'  => $data['retenues'] ?? [],
        ]);

        $existing = ESBTPSalaire::where('teacher_id', $teacher->id)
            ->where('mois', $data['mois'])->where('annee', $data['annee'])->first();

        return response()->json([
            'preview'   => $preview,
            'teacher'   => ['id' => $teacher->id, 'name' => $teacher->user->name ?? $teacher->name],
            'exists'    => (bool) $existing,
            'locked'    => $existing ? $existing->isLocked() : false,
        ]);
    }

    /** Persiste / met à jour un bulletin (statut brouillon). Recalcul serveur. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id'         => 'required|exists:esbtp_teachers,id',
            'mois'               => 'required|integer|min:1|max:12',
            'annee'              => 'required|integer|min:2000|max:2100',
            'impot_its'          => 'nullable|numeric|min:0',
            'cnps'               => 'nullable|numeric|min:0',
            'primes'             => 'nullable|array',
            'retenues'           => 'nullable|array',
            'commentaires'       => 'nullable|string|max:1000',
        ]);

        $teacher = ESBTPTeacher::findOrFail($data['teacher_id']);
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->firstOrFail();
        [$from, $to] = $this->moisRange($data['mois'], $data['annee']);

        // Recalcul serveur (ne jamais faire confiance aux montants du client).
        $preview = $this->payroll->computePreview($teacher, $from, $to, [
            'impot_its' => $data['impot_its'] ?? null,
            'cnps'      => $data['cnps'] ?? null,
            'primes'    => $data['primes'] ?? [],
            'retenues'  => $data['retenues'] ?? [],
        ]);

        $salaire = ESBTPSalaire::where('teacher_id', $teacher->id)
            ->where('mois', $data['mois'])->where('annee', $data['annee'])->first();

        if ($salaire && $salaire->isLocked()) {
            return response()->json(['message' => 'Ce bulletin est verrouillé (payé/annulé) et ne peut être modifié.'], 422);
        }

        $salaire = DB::transaction(function () use ($salaire, $teacher, $annee, $data, $preview, $from, $to) {
            $payload = [
                'user_id'                => $teacher->user_id,
                'teacher_id'             => $teacher->id,
                'annee_universitaire_id' => $annee->id,
                'mois'                   => $data['mois'],
                'annee'                  => $data['annee'],
                'period_start'           => $from->toDateString(),
                'period_end'             => $to->toDateString(),
                'salaire_base'           => $preview['base'],
                'heures_total'           => $preview['heures_total'],
                'primes'                 => $preview['primes'],
                'retenues'               => $preview['total_retenues'],
                'impot_its'              => $preview['impot_its'],
                'cnps'                   => $preview['cnps'],
                'net_a_payer'            => $preview['net'],
                'statut'                 => 'en attente',
                'workflow_status'        => ESBTPSalaire::ST_BROUILLON,
                'commentaires'           => $data['commentaires'] ?? null,
            ];

            if ($salaire) {
                $salaire->update($payload);
            } else {
                $payload['createur_id'] = auth()->id();
                $salaire = ESBTPSalaire::create($payload);
            }

            $salaire->prepared_by = auth()->id();
            $salaire->prepared_at = now();
            $salaire->workflow_status = ESBTPSalaire::ST_BROUILLON;
            // Reset validation si on re-prépare.
            $salaire->validateur_id = null;
            $salaire->date_validation = null;
            $salaire->save();

            // Lignes de détail : on remplace tout.
            $salaire->details()->delete();
            foreach ($preview['lignes'] as $ligne) {
                $salaire->details()->create($ligne);
            }

            return $salaire;
        });

        return response()->json([
            'success'  => true,
            'message'  => 'Bulletin de paie enregistré.',
            'redirect' => route('esbtp.comptabilite.salaires.show', $salaire->id),
        ]);
    }

    public function show(ESBTPSalaire $salaire)
    {
        $salaire->load(['details', 'teacher.user', 'preparePar', 'validePar', 'payePar', 'anneeUniversitaire']);
        $user = auth()->user();

        return view('esbtp.comptabilite.salaires.show', [
            'salaire'      => $salaire,
            'gains'        => $salaire->details->where('categorie', 'gain')->values(),
            'retenues'     => $salaire->details->where('categorie', 'retenue')->values(),
            'moisLabel'    => self::MOIS_FR[$salaire->mois] ?? $salaire->mois,
            'canValidate'  => $user->can('comptabilite.salaires.validate') || $user->can('comptabilite.salaires.validate_own'),
            'canPay'       => $user->can('comptabilite.salaires.pay'),
        ]);
    }

    /**
     * Validation (2e niveau OHADA, séparation des devoirs sauf validate_own).
     * Nommée approve() : `validate` est réservé par Illuminate\Routing\Controller.
     */
    public function approve(ESBTPSalaire $salaire)
    {
        $user = auth()->user();
        abort_unless(
            $user->can('comptabilite.salaires.validate') || $user->can('comptabilite.salaires.validate_own'),
            403
        );

        if (!$salaire->isBrouillon()) {
            return back()->with('error', 'Seul un bulletin en brouillon peut être validé.');
        }

        $estPreparateur = in_array($user->id, [$salaire->prepared_by, $salaire->createur_id], true);
        if ($estPreparateur && !$user->can('comptabilite.salaires.validate_own')) {
            return back()->with('error', 'Séparation des devoirs : la validation doit être faite par une autre personne.');
        }
        if (!$estPreparateur && !$user->can('comptabilite.salaires.validate')) {
            abort(403);
        }

        $salaire->update([
            'workflow_status' => ESBTPSalaire::ST_VALIDE,
            'validateur_id'   => $user->id,
            'date_validation' => now(),
        ]);

        return back()->with('success', 'Bulletin validé.');
    }

    /** Règlement effectif d'un bulletin validé. */
    public function pay(Request $request, ESBTPSalaire $salaire)
    {
        abort_unless(auth()->user()->can('comptabilite.salaires.pay'), 403);

        if (!$salaire->isValide()) {
            return back()->with('error', 'Seul un bulletin validé peut être marqué payé.');
        }

        $data = $request->validate([
            'mode_paiement'      => 'required|string|max:50',
            'reference_paiement' => 'nullable|string|max:100',
            'date_paiement'      => 'nullable|date',
        ]);

        $salaire->update([
            'workflow_status'    => ESBTPSalaire::ST_PAYE,
            'statut'             => 'payé',
            'mode_paiement'      => $data['mode_paiement'],
            'reference_paiement' => $data['reference_paiement'] ?? null,
            'date_paiement'      => $data['date_paiement'] ?? now()->toDateString(),
            'paid_by'            => auth()->id(),
            'paid_at'            => now(),
        ]);

        return back()->with('success', 'Bulletin marqué comme payé.');
    }

    /** Configuration fiscale (barème ITS + CNPS). */
    public function updateConfig(Request $request)
    {
        abort_unless(auth()->user()->can('comptabilite.salaires.configure'), 403);

        $data = $request->validate([
            'cnps_taux'         => 'required|numeric|min:0|max:100',
            'bareme'            => 'required|array|min:1',
            'bareme.*.from'     => 'required|numeric|min:0',
            'bareme.*.to'       => 'nullable|numeric|min:0',
            'bareme.*.taux'     => 'required|numeric|min:0|max:100',
        ]);

        SettingsHelper::setOrCreate('paie.cnps_taux', (string) $data['cnps_taux'], 'paie', 'string');
        SettingsHelper::setOrCreate('paie.its_bareme', json_encode(array_values($data['bareme'])), 'paie', 'string');

        return response()->json(['success' => true, 'message' => 'Paramètres de paie enregistrés.']);
    }

    // ── Helpers ─────────────────────────────────────────────

    /** @return array{0:Carbon,1:Carbon} */
    private function moisRange(int $mois, int $annee): array
    {
        $from = Carbon::create($annee, $mois, 1)->startOfMonth();
        return [$from, (clone $from)->endOfMonth()];
    }
}
