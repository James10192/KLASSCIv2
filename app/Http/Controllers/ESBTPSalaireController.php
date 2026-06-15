<?php

namespace App\Http\Controllers;

use App\Domain\Comptabilite\Paie\PayrollComputationService;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPSalaire;
use App\Models\ESBTPSalaireDetail;
use App\Models\ESBTPTeacher;
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
    public function __construct(private PayrollComputationService $payroll)
    {
    }

    private const MOIS_FR = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
        7 => 'Juillet', 8 => 'Août', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    public function index(Request $request)
    {
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        $filtres = [
            'mois'    => (int) $request->get('mois', now()->month),
            'annee'   => (int) $request->get('annee', now()->year),
            'statut'  => $request->get('statut'),
        ];

        $bulletins = $this->bulletinsQuery($filtres)->get();
        $kpis = $this->kpis($filtres);

        $teachers = ESBTPTeacher::with('user:id,name')->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->user->name ?? $t->name ?? 'Enseignant'])
            ->sortBy('name')->values();

        return view('esbtp.comptabilite.salaires.index', [
            'bulletins'   => $bulletins,
            'kpis'        => $kpis,
            'filtres'     => $filtres,
            'annee'       => $annee,
            'teachers'    => $teachers,
            'moisOptions' => self::MOIS_FR,
            'statutLabels'=> ESBTPSalaire::statutLabels(),
            'canCreate'   => auth()->user()->can('comptabilite.salaires.create'),
            'canConfigure'=> auth()->user()->can('comptabilite.salaires.configure'),
            'cnpsTaux'    => $this->payroll->tauxCnps(),
            'bareme'      => $this->payroll->baremeIts(),
        ]);
    }

    /** AJAX : liste filtrée + KPIs (no-reload). */
    public function data(Request $request)
    {
        $filtres = [
            'mois'   => (int) $request->get('mois', now()->month),
            'annee'  => (int) $request->get('annee', now()->year),
            'statut' => $request->get('statut'),
        ];

        $bulletins = $this->bulletinsQuery($filtres)->get();
        $kpis = $this->kpis($filtres);

        return response()->json([
            'list_html' => view('esbtp.comptabilite.salaires.partials._list', [
                'bulletins'    => $bulletins,
                'statutLabels' => ESBTPSalaire::statutLabels(),
            ])->render(),
            'kpis_html' => view('esbtp.comptabilite.salaires.partials._kpis', ['kpis' => $kpis])->render(),
        ]);
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
    private function bulletinsQuery(array $filtres)
    {
        $q = ESBTPSalaire::with('teacher.user:id,name')
            ->where('mois', $filtres['mois'])
            ->where('annee', $filtres['annee']);

        if (!empty($filtres['statut'])) {
            $q->where('workflow_status', $filtres['statut']);
        }

        return $q->orderByDesc('updated_at');
    }

    private function kpis(array $filtres): array
    {
        $base = ESBTPSalaire::where('mois', $filtres['mois'])->where('annee', $filtres['annee']);

        $byStatut = (clone $base)->select('workflow_status', DB::raw('count(*) as n'), DB::raw('sum(net_a_payer) as net'))
            ->groupBy('workflow_status')->get()->keyBy('workflow_status');

        return [
            'total_net'   => (float) (clone $base)->sum('net_a_payer'),
            'nb_total'    => (clone $base)->count(),
            'nb_brouillon'=> (int) ($byStatut[ESBTPSalaire::ST_BROUILLON]->n ?? 0),
            'nb_valide'   => (int) ($byStatut[ESBTPSalaire::ST_VALIDE]->n ?? 0),
            'nb_paye'     => (int) ($byStatut[ESBTPSalaire::ST_PAYE]->n ?? 0),
            'net_paye'    => (float) ($byStatut[ESBTPSalaire::ST_PAYE]->net ?? 0),
        ];
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function moisRange(int $mois, int $annee): array
    {
        $from = Carbon::create($annee, $mois, 1)->startOfMonth();
        return [$from, (clone $from)->endOfMonth()];
    }
}
