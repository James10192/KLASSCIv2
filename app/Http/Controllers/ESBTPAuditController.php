<?php

namespace App\Http\Controllers;

use App\Domain\Audit\ChampsLisibles;
use App\Domain\Audit\FiltresDuJournal;
use App\Domain\Audit\JournalLisible;
use App\Domain\Audit\LigneDuJournal;
use App\Domain\Audit\ThemesDuJournal;
use App\Exports\AuditExport;
use App\Models\User;
use App\Services\Audit\AuditEntityResolver;
use App\Support\ListeInfinie;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use OwenIt\Auditing\Models\Audit;

/**
 * Le journal d'audit : qui a fait quoi, sur qui, et quand.
 *
 * Une seule page, avec des onglets (ThemesDuJournal), la ou il y en avait
 * trois sur la meme table. Chaque ligne se lit comme une phrase
 * (JournalLisible) ; le detail d'une action dit ce qui a ete touche, ce qui a
 * change et la vie de l'objet, les details techniques replies en bas.
 * Ce controleur ne fait qu'orchestrer.
 */
class ESBTPAuditController extends Controller
{
    private const PAR_TRANCHE = 50;

    private const EXPORT_EXCEL_MAX = 5000;

    private const EXPORT_PDF_MAX = 300;

    private const VIE_DE_CHAQUE_COTE = 15;

    public function __construct(
        private readonly JournalLisible $journal,
        private readonly AuditEntityResolver $liens,
    ) {
        $this->middleware('auth');
        $this->middleware('permission:security.audit.view|comptabilite.audit.view')->only(['index', 'show']);
        $this->middleware('permission:security.audit.export')->only(['exportPdf', 'exportExcel']);
        $this->middleware('permission:security.users.monitor')->only(['userActivity']);
    }

    public function index(Request $request): View|JsonResponse
    {
        $themes = ThemesDuJournal::visibles($request->user());
        $filtres = FiltresDuJournal::depuis($request, $themes);
        $tranche = $filtres->requete()->with('user.roles:id,name')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->simplePaginate(self::PAR_TRANCHE)->withQueryString();

        if (ListeInfinie::demandee($request)) {
            return $this->suite($tranche);
        }

        // Les deux comptes balaient toute la periode. Pendant une recherche
        // (une requete par frappe) on ne les refait pas : la page garde le
        // dernier compte de l'onglet, et la banniere des taches automatiques
        // se tait. Avec une personne choisie, il n'y a pas de tache automatique.
        $compter = $filtres->recherche === '';
        $donnees = [
            'lignes' => $this->journal->lignes($tranche->items()),
            'tranche' => $tranche,
            'filtres' => $filtres,
            'automatiques' => $filtres->automatiques || $filtres->personne || $filtres->idObjet || ! $compter ? null
                : $this->compte($filtres, 'auto', fn () => $this->automatiques($filtres)),
        ];
        $aRegarder = ! in_array(ThemesDuJournal::A_REGARDER, $themes, true) ? 0
            : ($compter ? $this->compte($filtres, 'regarder', fn () => ThemesDuJournal::aRegarder($this->sansTheme($filtres))->count()) : null);

        // Un filtre change : la liste seule, sans recharger la page.
        if ($request->boolean('fragment')) {
            return response()->json(['liste' => view('esbtp.audit._liste', $donnees)->render(), 'aRegarder' => $aRegarder]);
        }

        return view('esbtp.audit.index', $donnees + [
            'themes' => $themes,
            'aRegarder' => $aRegarder ?? $this->compte($filtres, 'regarder', fn () => ThemesDuJournal::aRegarder($this->sansTheme($filtres))->count()),
            'personnes' => User::query()->select('id', 'name', 'email', 'username')->with('roles:id,name')->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, int $id): View
    {
        $audit = Audit::with('user.roles:id,name')->findOrFail($id);

        // La meme regle que les liens de la liste (ThemesDuJournal::peutOuvrir) :
        // le droit comptable seul n'ouvre que les finances, et l'argent demande
        // en plus l'acces aux donnees sensibles.
        abort_unless(ThemesDuJournal::peutOuvrir($request->user(), (string) $audit->auditable_type), 403);

        // La vie de l'objet autour de cette action : les quinze d'avant, celle-ci
        // et les quinze d'apres. Au-dela, « Voir toute son histoire » ouvre le
        // journal filtre sur l'objet.
        $memeObjet = fn () => Audit::with('user.roles:id,name')
            ->where('auditable_type', $audit->auditable_type)->where('auditable_id', $audit->auditable_id);
        $avant = $memeObjet()->where('id', '<', $audit->id)->orderByDesc('id')->limit(self::VIE_DE_CHAQUE_COTE)->get();
        $apres = $memeObjet()->where('id', '>', $audit->id)->orderBy('id')->limit(self::VIE_DE_CHAQUE_COTE)->get();
        $vie = $avant->reverse()->push($audit)->concat($apres)->values();

        return view('esbtp.audit.show', [
            'audit' => $audit,
            'ligne' => $this->journal->ligne($audit),
            'changements' => (new ChampsLisibles([$audit]))->changements($audit),
            'vie' => $this->journal->lignes($vie),
            'vieTronquee' => $avant->count() === self::VIE_DE_CHAQUE_COTE || $apres->count() === self::VIE_DE_CHAQUE_COTE,
            'touches' => $this->liens->resolve($audit),
        ]);
    }

    /** L'ancien « audit comptable » : c'est l'onglet Finances du journal. */
    public function comptabiliteAudits(): RedirectResponse
    {
        return redirect()->route('esbtp.audit.index', ['theme' => ThemesDuJournal::FINANCES], 301);
    }

    public function userActivity(Request $request): View|JsonResponse
    {
        $userId = $request->integer('user_id') ?: null;
        $du = FiltresDuJournal::date($request->query('date_from'))?->startOfDay() ?? now()->subDays(30)->startOfDay();
        $au = FiltresDuJournal::date($request->query('date_to'))?->endOfDay() ?? now();

        $portee = fn () => Audit::whereBetween('created_at', [$du, $au])->when($userId, fn ($q) => $q->where('user_id', $userId));
        $tranche = $portee()->with('user.roles:id,name')->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(self::PAR_TRANCHE)->withQueryString();

        if (ListeInfinie::demandee($request)) {
            return $this->suite($tranche);
        }

        $parHeure = $portee()->selectRaw('HOUR(created_at) as heure, COUNT(*) as total')->groupBy(DB::raw('HOUR(created_at)'))->pluck('total', 'heure');
        $repartition = collect(range(0, 23))->mapWithKeys(fn ($h) => [$h => (int) ($parHeure[$h] ?? 0)])->all();
        $pointe = max($repartition) > 0 ? array_search(max($repartition), $repartition, true) : null;

        return view('esbtp.audit.user-activity', [
            'lignes' => $this->journal->lignes($tranche->items()),
            'tranche' => $tranche,
            'stats' => [
                'total_actions' => $tranche->total(),
                'unique_users' => Audit::whereBetween('created_at', [$du, $au])->distinct('user_id')->count('user_id'),
                'unique_ips' => $portee()->whereNotNull('ip_address')->distinct('ip_address')->count('ip_address'),
                'peak_hour' => $pointe !== null ? sprintf('%02dh', $pointe) : '—',
                'a_regarder' => ThemesDuJournal::aRegarder($portee())->count(),
            ],
            'users' => User::select('id', 'name', 'email', 'username')->with('roles:id,name')->orderBy('name')->get(),
            'selectedUser' => $userId ? User::find($userId) : null,
            'topModels' => $portee()->select('auditable_type', DB::raw('COUNT(*) as total'))->groupBy('auditable_type')
                ->orderByDesc('total')->limit(5)->get()
                ->map(fn ($r) => ['label' => \App\Helpers\EntityLabelHelper::plural($r->auditable_type), 'count' => $r->total]),
            'topIps' => $portee()->select('ip_address', DB::raw('COUNT(*) as total'))->whereNotNull('ip_address')
                ->groupBy('ip_address')->orderByDesc('total')->limit(5)->get(),
            'hourlyDistribution' => $repartition,
            'dateFrom' => $du,
            'dateTo' => $au,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $lignes = $this->pourExport($request, self::EXPORT_EXCEL_MAX);

        return Excel::download(new AuditExport($lignes), 'journal-audit_'.now()->format('Y-m-d_H-i').'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $filtres = FiltresDuJournal::depuis($request, ThemesDuJournal::visibles($request->user()));
        $lignes = $this->pourExport($request, self::EXPORT_PDF_MAX);

        $pdf = Pdf::loadView('esbtp.audit.export-pdf', [
            'lignes' => $lignes,
            'filtres' => [
                'Onglet' => ThemesDuJournal::LIBELLES[$filtres->theme],
                'Période' => $filtres->plage() ? \Illuminate\Support\Str::ucfirst($filtres->plage()) : FiltresDuJournal::PERIODES[$filtres->periode],
                'Recherche' => $filtres->recherche ?: null,
            ],
        ])->setPaper('a4', 'landscape');
        $nom = 'journal-audit_'.now()->format('Y-m-d_H-i').'.pdf';

        // L'apercu s'ouvre dans un onglet ; le telechargement s'enregistre.
        return $request->boolean('apercu') ? $pdf->stream($nom) : $pdf->download($nom);
    }

    /** La suite de la liste : chaque ligne, precedee de son jour quand il change. */
    private function suite($tranche): JsonResponse
    {
        $lignes = collect($this->journal->lignes($tranche->items()))->keyBy('id');
        $jour = null;

        return ListeInfinie::reponse($tranche, function (Audit $a) use ($lignes, &$jour) {
            $afficherJour = $a->created_at->format('Y-m-d') !== $jour;
            $jour = $a->created_at->format('Y-m-d');

            return view('esbtp.audit._phrase', ['l' => $lignes[$a->id], 'afficherJour' => $afficherJour])->render();
        });
    }

    /**
     * Ce que le masquage cache, dit en une phrase au lieu de trois cents
     * lignes : combien, et surtout quoi.
     *
     * @return array{nombre: int, surtout: ?string}
     */
    private function automatiques(FiltresDuJournal $filtres): array
    {
        return JournalLisible::resumeAutomatique($filtres->base()->whereNull('user_id')
            ->selectRaw('auditable_type, COUNT(*) as total')->groupBy('auditable_type')->pluck('total', 'auditable_type'));
    }

    /**
     * Les deux comptes de la page balaient toute la periode : sur « depuis le
     * debut », et sur une grosse instance, c'est la table entiere. Une minute
     * de cache suffit a ne pas les refaire a chaque frappe dans la recherche.
     */
    private function compte(FiltresDuJournal $filtres, string $quoi, \Closure $calcul): mixed
    {
        $cle = 'audit.journal.'.$quoi.'.'.md5(json_encode($filtres->enParametres()));

        return \Illuminate\Support\Facades\Cache::remember($cle, 60, $calcul);
    }

    /** Les memes filtres, tous onglets confondus : le compte de l'onglet « À regarder ». */
    private function sansTheme(FiltresDuJournal $filtres)
    {
        $params = $filtres->enParametres();
        $params['theme'] = ThemesDuJournal::TOUT;

        return FiltresDuJournal::depuis(new Request($params), [ThemesDuJournal::TOUT])->base();
    }

    /** @return list<LigneDuJournal> */
    private function pourExport(Request $request, int $maximum): array
    {
        $filtres = FiltresDuJournal::depuis($request, ThemesDuJournal::visibles($request->user()));
        $lignes = [];
        $filtres->requete()->with('user.roles:id,name')->orderByDesc('created_at')->orderByDesc('id')
            ->limit($maximum)->get()->chunk(500)
            ->each(function ($lot) use (&$lignes) {
                array_push($lignes, ...$this->journal->lignes($lot));
            });

        return $lignes;
    }
}
