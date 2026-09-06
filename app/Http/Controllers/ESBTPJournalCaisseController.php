<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPPaiement;
use App\Services\Mobile\MobileProfileResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * S1.3 — Journal de caisse OHADA.
 *
 * Vue chronologique des encaissements (livre des recettes) conforme aux exigences
 * comptables OHADA. Affiche : date paiement, ref, étudiant, catégorie, mode,
 * montant, encaissé par, validé par, statut.
 *
 * Filtres : période, filière, classe, mode de paiement, statut.
 * Export PDF format officiel via x-pdf-document.
 *
 * Permission : `comptabilite.journal.view` (default comptable + superAdmin).
 */
class ESBTPJournalCaisseController extends Controller
{
    /**
     * Ecran mobile : la liste se lit par journee, pas par ligne. Une page =
     * ce nombre de jours ayant au moins un mouvement (constante technique,
     * comme le paginate(50) du bureau).
     */
    private const JOURS_PAR_PAGE = 5;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:comptabilite.access');
        $this->middleware('permission:comptabilite.journal.view');
    }

    /**
     * Modes proposés au filtre : ceux réellement présents dans les paiements.
     *
     * La liste était écrite en dur (Espèces / Chèque / Virement / Mobile Money /
     * Carte bancaire) alors que la colonne stocke aussi wave, orange_money,
     * mtn_money… : ces modes étaient donc infiltrables. La colonne est en texte
     * libre (legacy), une liste figée ne peut pas suivre — on lit la donnée.
     */
    private function modesPresents(): array
    {
        return ESBTPPaiement::query()
            ->whereNull('deleted_at')
            ->whereNotNull('mode_paiement')
            ->where('mode_paiement', '!=', '')
            ->distinct()
            ->orderBy('mode_paiement')
            ->pluck('mode_paiement')
            ->all();
    }

    public function index(Request $request)
    {
        [$dateDebut, $dateFin] = $this->resolvePeriod($request);

        $filters = [
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'filiere_id' => $request->input('filiere_id'),
            'classe_id' => $request->input('classe_id'),
            'mode_paiement' => $request->input('mode_paiement'),
            'statut' => $request->input('statut', 'validé'),
        ];

        // Ecran mobile (shell m-*) : des donnees groupees par jour, pas du HTML.
        // La vue mobile rend ses cartes-lignes et demande les jours precedents
        // page par page, avec les memes filtres que le bureau.
        if ($request->input('mode') === 'mobile') {
            return response()->json($this->journalMobile($filters, $request));
        }

        $paiements = $this->buildQuery($filters)->orderBy('date_paiement')->orderBy('id')->paginate(50)->withQueryString();

        $totals = $this->buildTotals($filters);

        $modes = $this->modesPresents();
        $filieres = ESBTPFiliere::orderBy('name')->get(['id', 'name']);
        $classes = ESBTPClasse::orderBy('name')->get(['id', 'name', 'filiere_id']);
        $anneeActive = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        // Premiere page du journal mobile, deja en donnees : l'ecran s'affiche
        // sans second aller-retour. Null quand le shell ne se rendra pas.
        $journalMobile = $this->shellMobileActif() ? $this->journalMobile($filters, $request) : null;

        return view('esbtp.comptabilite.journal-caisse.index', compact(
            'paiements', 'totals', 'filters', 'modes', 'filieres', 'classes', 'anneeActive', 'dateDebut', 'dateFin', 'journalMobile'
        ));
    }

    /**
     * Le shell mobile se rend quand le reglage d'instance est actif ET que la
     * personne connectee a un profil (meme regle que MobileShellComposer).
     */
    private function shellMobileActif(): bool
    {
        $resolver = app(MobileProfileResolver::class);

        return $resolver->actif() && auth()->check() && $resolver->resolve(auth()->user()) !== null;
    }

    /**
     * Journal mobile : les mouvements de caisse de la periode, groupes par
     * jour (du plus recent au plus ancien), pagines par jour.
     *
     * Chaque jour porte son solde net (encaissements moins remboursements) ;
     * chaque mouvement est une carte-ligne : initiales ou icone, titre,
     * sous-titre, montant signe, statut.
     */
    private function journalMobile(array $filters, Request $request): array
    {
        $page = max(1, (int) $request->input('page', 1));
        $base = $this->buildQuery($filters);

        $nbJours = (int) (clone $base)->toBase()
            ->selectRaw('COUNT(DISTINCT DATE(date_paiement)) AS nb')
            ->value('nb');

        $joursPage = (clone $base)->toBase()
            ->selectRaw('DATE(date_paiement) AS jour')
            ->groupBy('jour')
            ->orderByDesc('jour')
            ->offset(($page - 1) * self::JOURS_PAR_PAGE)
            ->limit(self::JOURS_PAR_PAGE)
            ->pluck('jour')
            ->map(fn ($j) => Carbon::parse($j)->format('Y-m-d'))
            ->values();

        $mouvements = collect();
        if ($joursPage->isNotEmpty()) {
            // Les jours retenus sont consecutifs dans la liste des jours ayant
            // un mouvement : entre le plus ancien et le plus recent, il n'y a
            // rien d'autre a exclure.
            $mouvements = (clone $base)
                ->whereDate('date_paiement', '>=', $joursPage->last())
                ->whereDate('date_paiement', '<=', $joursPage->first())
                ->orderByDesc('date_paiement')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy(fn (ESBTPPaiement $p) => optional($p->date_paiement)->format('Y-m-d'));
        }

        $peutOuvrir = Gate::allows('paiements.view') || Gate::allows('paiements.view_own');

        $jours = $joursPage->map(function (string $jour) use ($mouvements, $peutOuvrir) {
            $lignes = collect($mouvements->get($jour, []));
            $entrees = (float) $lignes->reject(fn (ESBTPPaiement $p) => $p->isAvoir())->sum('montant');
            $sorties = (float) $lignes->filter(fn (ESBTPPaiement $p) => $p->isAvoir())->sum('montant');
            $date = Carbon::parse($jour);

            return [
                'date' => $jour,
                'libelle' => Str::ucfirst($date->translatedFormat($date->isCurrentYear() ? 'l j F' : 'l j F Y')),
                'solde' => $entrees - $sorties,
                'nb' => $lignes->count(),
                'mouvements' => $lignes->map(fn (ESBTPPaiement $p) => $this->mouvementMobile($p, $peutOuvrir))->values()->all(),
            ];
        })->values()->all();

        $navUrl = route('esbtp.comptabilite.journal-caisse.index');
        $query = Arr::except($request->query(), ['mode', 'page']);
        if ($query !== []) {
            $navUrl .= '?' . http_build_query($query);
        }

        return [
            'jours' => $jours,
            'has_more' => $nbJours > $page * self::JOURS_PAR_PAGE,
            'next_page' => $page + 1,
            'nb_jours' => $nbJours,
            'mois' => $this->moisMobile($filters),
            'filtres' => $filters,
            'url' => $navUrl,
        ];
    }

    /**
     * Une carte-ligne du journal mobile. Dans le perimetre cashMovements(), un
     * avoir est toujours un remboursement : c'est la seule sortie de caisse.
     */
    private function mouvementMobile(ESBTPPaiement $p, bool $peutOuvrir): array
    {
        $etudiant = $p->inscription?->etudiant;
        $nom = trim(($etudiant?->prenoms ?? '') . ' ' . ($etudiant?->nom ?? ''));
        $frais = $p->fraisCategory?->name ?? $p->motif;
        $sortie = $p->isAvoir();

        // date_paiement est une date sans heure : l'heure vient de la saisie,
        // seulement quand elle a eu lieu le jour meme.
        $heure = $p->created_at && $p->date_paiement && $p->created_at->isSameDay($p->date_paiement)
            ? $p->created_at->format('H:i')
            : null;

        $reference = $sortie
            ? ($p->numero_avoir ? 'Avoir ' . $p->numero_avoir : null)
            : ($p->numero_recu ? 'Reçu ' . $p->numero_recu : null);

        $garder = fn ($v) => $v !== null && $v !== '';

        $titre = $sortie
            ? implode(' · ', array_filter(['Remboursement', $nom], $garder))
            : implode(' · ', array_filter([$nom, $frais], $garder));

        return [
            'id' => $p->id,
            'url' => $peutOuvrir ? route('esbtp.paiements.show', $p->id) : null,
            'initiales' => (! $sortie && $nom !== '') ? $this->initiales($nom) : null,
            'icone' => $sortie ? 'cash' : ($nom === '' ? 'user' : null),
            'titre' => $titre !== '' ? $titre : 'Mouvement #' . $p->id,
            'sous_titre' => implode(' · ', array_filter([
                $reference ?? '#' . $p->id,
                $sortie ? $frais : null,
                $p->mode_paiement,
                $heure,
                $this->nomCourt($p->createdBy?->name),
            ], $garder)),
            'montant' => (float) $p->montant,
            'sortie' => $sortie,
            'statut' => (string) $p->status,
        ];
    }

    /**
     * Segments de mois de l'ecran mobile : le mois courant et les deux
     * precedents ; si la periode demandee sort de ces trois mois, son mois
     * est ajoute en tete pour rester selectionnable.
     */
    private function moisMobile(array $filters): array
    {
        $debut = Carbon::parse($filters['date_debut']);
        $fin = Carbon::parse($filters['date_fin']);
        $courant = Carbon::now()->startOfMonth();

        $mois = collect([0, 1, 2])->map(fn (int $i) => $courant->copy()->subMonths($i));
        if (! $mois->contains(fn (Carbon $m) => $m->isSameMonth($debut, true))) {
            $mois->prepend($debut->copy()->startOfMonth());
        }

        return $mois->map(fn (Carbon $m) => [
            'cle' => $m->format('Y-m'),
            'libelle' => Str::ucfirst($m->translatedFormat($m->isCurrentYear() ? 'F' : 'M Y')),
            'date_debut' => $m->format('Y-m-d'),
            'date_fin' => $m->copy()->endOfMonth()->format('Y-m-d'),
            'on' => $debut->isSameMonth($m, true) && $fin->isSameMonth($m, true),
        ])->values()->all();
    }

    private function initiales(string $nom): string
    {
        return collect(preg_split('/\s+/u', trim($nom)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1, 'UTF-8'), 'UTF-8'))
            ->implode('');
    }

    /** « Koné Ibrahim » devient « Koné I. » : la ligne mobile n'a qu'une largeur d'ecran. */
    private function nomCourt(?string $nom): ?string
    {
        $mots = array_values(array_filter(preg_split('/\s+/u', trim((string) $nom)) ?: []));
        if ($mots === []) {
            return null;
        }
        if (count($mots) === 1) {
            return $mots[0];
        }

        return $mots[0] . ' ' . mb_strtoupper(mb_substr($mots[1], 0, 1, 'UTF-8'), 'UTF-8') . '.';
    }

    /**
     * Export PDF du journal — format officiel OHADA via x-pdf-document.
     */
    public function exportPdf(Request $request)
    {
        [$dateDebut, $dateFin] = $this->resolvePeriod($request);

        $filters = [
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'filiere_id' => $request->input('filiere_id'),
            'classe_id' => $request->input('classe_id'),
            'mode_paiement' => $request->input('mode_paiement'),
            'statut' => $request->input('statut', 'validé'),
        ];

        // Garde-fou volume (rule exports-pdf-excel.md)
        $count = $this->buildQuery($filters)->count();
        if ($count > 1000) {
            return back()->with('error', sprintf(
                'Trop de lignes (%d) pour l\'export PDF (limite 1000). Affinez la période ou les filtres.',
                $count,
            ));
        }

        $paiements = $this->buildQuery($filters)->orderBy('date_paiement')->orderBy('id')->get();
        $totals = $this->buildTotals($filters);

        $appliedFilters = $this->humanFilters($filters);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.comptabilite.journal-caisse.pdf', [
            'paiements' => $paiements,
            'totals' => $totals,
            'filters' => $filters,
            'appliedFilters' => $appliedFilters,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ])->setPaper('a4', 'landscape');

        $filename = sprintf(
            'journal-caisse_%s_au_%s.pdf',
            $dateDebut->format('Y-m-d'),
            $dateFin->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    /**
     * Aperçu PDF inline (rule exports-pdf-excel.md — toujours offrir preview).
     */
    public function exportPdfPreview(Request $request)
    {
        return $this->previewPdf($request);
    }

    public function previewPdf(Request $request)
    {
        [$dateDebut, $dateFin] = $this->resolvePeriod($request);

        $filters = [
            'date_debut' => $dateDebut->format('Y-m-d'),
            'date_fin' => $dateFin->format('Y-m-d'),
            'filiere_id' => $request->input('filiere_id'),
            'classe_id' => $request->input('classe_id'),
            'mode_paiement' => $request->input('mode_paiement'),
            'statut' => $request->input('statut', 'validé'),
        ];

        $paiements = $this->buildQuery($filters)->orderBy('date_paiement')->orderBy('id')->limit(1000)->get();
        $totals = $this->buildTotals($filters);
        $appliedFilters = $this->humanFilters($filters);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('esbtp.comptabilite.journal-caisse.pdf', [
            'paiements' => $paiements,
            'totals' => $totals,
            'filters' => $filters,
            'appliedFilters' => $appliedFilters,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ])->setPaper('a4', 'landscape');

        $filename = sprintf('apercu-journal-caisse_%s.pdf', now()->format('Y-m-d_His'));

        return new Response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    private function resolvePeriod(Request $request): array
    {
        $dateDebut = $request->filled('date_debut')
            ? Carbon::parse($request->input('date_debut'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $dateFin = $request->filled('date_fin')
            ? Carbon::parse($request->input('date_fin'))->endOfDay()
            : Carbon::now()->endOfDay();

        // Sécurité : si date_debut > date_fin, swap
        if ($dateDebut->gt($dateFin)) {
            [$dateDebut, $dateFin] = [$dateFin->copy()->startOfDay(), $dateDebut->copy()->endOfDay()];
        }

        return [$dateDebut, $dateFin];
    }

    private function buildQuery(array $filters)
    {
        $query = ESBTPPaiement::query()
            ->with([
                'inscription.etudiant:id,nom,prenoms,matricule',
                'inscription.classe:id,name,filiere_id',
                'fraisCategory:id,name',
                'createdBy:id,name',
                'validatedBy:id,name',
            ])
            ->whereNull('deleted_at')
            ->whereDate('date_paiement', '>=', $filters['date_debut'])
            ->whereDate('date_paiement', '<=', $filters['date_fin'])
            ->cashMovements();

        if (!empty($filters['statut'])) {
            $query->where('status', $filters['statut']);
        }

        if (!empty($filters['mode_paiement'])) {
            $query->where('mode_paiement', $filters['mode_paiement']);
        }

        if (!empty($filters['classe_id'])) {
            $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $filters['classe_id']));
        } elseif (!empty($filters['filiere_id'])) {
            $query->whereHas('inscription.classe', fn ($q) => $q->where('filiere_id', $filters['filiere_id']));
        }

        return $query;
    }

    private function buildTotals(array $filters): array
    {
        $base = $this->buildQuery($filters);

        $encaissements = (clone $base)->encaissements();
        $refunds = (clone $base)->avoires()->where('avoir_kind', 'refund');

        $statsByMode = (clone $encaissements)
            ->selectRaw('mode_paiement, COUNT(*) as nb, COALESCE(SUM(montant), 0) as total')
            ->groupBy('mode_paiement')
            ->get()
            ->keyBy('mode_paiement');

        $refundsByMode = (clone $refunds)
            ->selectRaw('mode_paiement, COUNT(*) as nb, COALESCE(SUM(montant), 0) as total')
            ->groupBy('mode_paiement')
            ->get()
            ->keyBy('mode_paiement');

        $byMode = [];
        foreach ($statsByMode as $mode => $row) {
            $refund = $refundsByMode[$mode] ?? null;
            $byMode[$mode] = [
                'count' => (int) $row->nb + (int) ($refund->nb ?? 0),
                'total' => (float) $row->total - (float) ($refund->total ?? 0),
            ];
        }
        foreach ($refundsByMode as $mode => $row) {
            if (! isset($byMode[$mode])) {
                $byMode[$mode] = [
                    'count' => (int) $row->nb,
                    'total' => 0 - (float) $row->total,
                ];
            }
        }

        return [
            'count' => (clone $encaissements)->count() + (clone $refunds)->count(),
            'total' => ESBTPPaiement::netCashSum($base),
            'by_mode' => $byMode,
        ];
    }

    private function humanFilters(array $filters): array
    {
        $human = [
            'Période' => sprintf(
                'du %s au %s',
                Carbon::parse($filters['date_debut'])->format('d/m/Y'),
                Carbon::parse($filters['date_fin'])->format('d/m/Y'),
            ),
            'Statut' => ucfirst($filters['statut'] ?? 'validé'),
        ];

        if (!empty($filters['mode_paiement'])) {
            $human['Mode de paiement'] = $filters['mode_paiement'];
        }
        if (!empty($filters['filiere_id'])) {
            $human['Filière'] = optional(ESBTPFiliere::find($filters['filiere_id']))->name ?? '#' . $filters['filiere_id'];
        }
        if (!empty($filters['classe_id'])) {
            $human['Classe'] = optional(ESBTPClasse::find($filters['classe_id']))->name ?? '#' . $filters['classe_id'];
        }

        return $human;
    }
}
