<?php

namespace App\Http\Controllers;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Services\CataloguePiecesDossier;
use App\Services\DossierPiecesEtudiant;
use App\Support\ListeInfinie;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Qui n'a pas rendu ses pièces, et lesquelles.
 *
 * Cet écran n'est PAS celui du guichet. Le geste de guichet — cocher ce qu'un
 * étudiant vient de poser sur le comptoir — vit sur la fiche d'inscription, là
 * où l'on est déjà quand la personne est devant soi. Ici on regarde une
 * promotion entière pour savoir qui rappeler : ce n'est pas le même moment, pas
 * la même personne, pas le même geste. D'où l'absence de toute action de masse :
 * « cocher toutes les pièces de cette classe » n'aurait aucun sens, personne
 * n'a vu ces papiers.
 *
 * ─── Pourquoi tout est calculé avant d'être paginé ───
 *
 * « Dossier incomplet » n'est pas une colonne : c'est une soustraction entre ce
 * qui est déposé et ce que l'année consomme. On ne peut donc ni le filtrer ni le
 * compter en SQL.
 *
 * Le calculer page par page rendrait l'écran inutilisable : filtrer sur
 * « incomplet » donnerait trois lignes sur une page de quarante, et les
 * compteurs ne parleraient que de la page qu'on regarde. On calcule donc la
 * promotion entière, puis on pagine le résultat. Le coût tient : trois requêtes
 * groupées, et une boucle sur quelques milliers de couples étudiant × pièce.
 */
class ESBTPSuiviPiecesController extends Controller
{
    /** Taille d'une tranche : la liste se charge au défilement. */
    private const PAR_PAGE = 40;

    /**
     * Plafond du calcul d'un coup.
     *
     * Il n'existe pas pour brider l'école mais pour que l'écran réponde toujours.
     * Au-delà, on demande un filtre — une classe, une recherche — plutôt que de
     * faire attendre devant une page qui n'aboutit pas. Aucune instance n'en est
     * proche : les deux plus grosses tournent autour de deux mille inscriptions.
     */
    private const PLAFOND_CALCUL = 6000;

    public function __construct(
        private readonly DossierPiecesEtudiant $dossiers,
        private readonly CataloguePiecesDossier $catalogue
    ) {
    }

    public function index(Request $request)
    {
        $annees = ESBTPAnneeUniversitaire::orderByDesc('is_current')->orderByDesc('id')
            ->get(['id', 'name', 'is_current']);

        $classes = ESBTPClasse::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        $commun = [
            'classes' => $classes,
            'annees' => $annees,
            'filtres' => $this->filtres($request),
            'peutSuivre' => $request->user()?->can('pieces_dossier.suivre') ?? false,
            'tropVolumineux' => false,
        ];

        // Une ecole qui n'a rien configure ne voit pas un tableau vide avec des
        // filtres : elle voit ce qu'il faut faire pour que cet ecran serve.
        if (! $this->catalogue->estConfigure()) {
            return view('esbtp.pieces-dossier.suivi', $commun + [
                'configure' => false,
                'lignes' => $this->paginateur(collect(), $request),
                'kpis' => $this->kpisVides(),
            ]);
        }

        $anneeId = (int) ($request->input('annee_id')
            ?: ($annees->firstWhere('is_current', true)->id ?? $annees->first()->id ?? 0));

        $commun['filtres']['annee_id'] = $anneeId;

        $requete = $this->requete($request, $anneeId);
        $nombre = (clone $requete)->count();

        if ($nombre > self::PLAFOND_CALCUL) {
            return view('esbtp.pieces-dossier.suivi', $commun + [
                'configure' => true,
                'tropVolumineux' => true,
                'nombre' => $nombre,
                'lignes' => $this->paginateur(collect(), $request),
                'kpis' => $this->kpisVides(),
            ]);
        }

        $inscriptions = $requete->orderBy('id')->get();
        $syntheses = $this->dossiers->syntheseParInscription($inscriptions);

        $lignes = $inscriptions
            ->map(fn (ESBTPInscription $i) => [
                'inscription' => $i,
                'synthese' => $syntheses[$i->id] ?? null,
            ])
            ->filter(fn (array $l) => $l['synthese'] !== null)
            ->filter(fn (array $l) => $this->retenue($l['synthese'], (string) $request->input('etat', '')))
            ->values();

        // La suite de la liste : ses lignes seules. La promotion est recalculee
        // (l'etat d'un dossier n'est pas une colonne), mais pas les compteurs.
        if (ListeInfinie::demandee($request)) {
            return ListeInfinie::reponse(
                $this->paginateur($lignes, $request),
                fn (array $ligne) => view('esbtp.pieces-dossier._ligne-suivi', compact('ligne'))->render(),
            );
        }

        return view('esbtp.pieces-dossier.suivi', $commun + [
            'configure' => true,
            'lignes' => $this->paginateur($lignes, $request),
            'kpis' => $this->kpis(collect($syntheses), $inscriptions->count()),
        ]);
    }

    private function requete(Request $request, int $anneeId)
    {
        $requete = ESBTPInscription::query()
            ->with([
                'etudiant:id,nom,prenoms,matricule,photo',
                'classe:id,name,filiere_id,niveau_etude_id',
            ])
            ->where('annee_universitaire_id', $anneeId)
            // Une inscription annulee n'a plus de dossier a completer : la
            // laisser dans la liste ferait rappeler des etudiants partis.
            ->where('status', '<>', 'annulee');

        if ($request->filled('classe_id')) {
            $requete->where('classe_id', (int) $request->input('classe_id'));
        }

        if ($request->filled('recherche')) {
            $recherche = trim((string) $request->input('recherche'));
            $requete->whereHas('etudiant', function ($q) use ($recherche) {
                $q->where('nom', 'like', "%{$recherche}%")
                    ->orWhere('prenoms', 'like', "%{$recherche}%")
                    ->orWhere('matricule', 'like', "%{$recherche}%");
            });
        }

        return $requete;
    }

    /** @param  array<string, mixed>  $synthese */
    private function retenue(array $synthese, string $etat): bool
    {
        return match ($etat) {
            'incomplet' => ! $synthese['complet'],
            'complet' => $synthese['complet'],
            'a_relire' => $synthese['a_relire'] > 0,
            default => true,
        };
    }

    /**
     * Pagination d'une collection déjà calculée.
     *
     * `LengthAwarePaginator` à la main plutôt que `paginate()` : la liste n'existe
     * plus en base à ce stade, elle est le produit d'un calcul.
     */
    private function paginateur(Collection $lignes, Request $request): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $lignes->forPage($page, self::PAR_PAGE)->values(),
            $lignes->count(),
            self::PAR_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * Les compteurs, sur la promotion entière — pas sur la page affichée.
     *
     * @param  Collection<int, array<string, mixed>>  $syntheses
     * @return array<string, mixed>
     */
    private function kpis(Collection $syntheses, int $total): array
    {
        return [
            'total' => $total,
            'complets' => $syntheses->filter(fn ($s) => $s['complet'])->count(),
            'incomplets' => $syntheses->filter(fn ($s) => ! $s['complet'])->count(),
            'a_relire' => $syntheses->sum(fn ($s) => $s['a_relire']),
            'pieces_manquantes' => $syntheses->sum(fn ($s) => $s['manquantes']),
        ];
    }

    /** @return array<string, mixed> */
    private function kpisVides(): array
    {
        return ['total' => 0, 'complets' => 0, 'incomplets' => 0, 'a_relire' => 0, 'pieces_manquantes' => 0];
    }

    /** @return array<string, mixed> */
    private function filtres(Request $request): array
    {
        return [
            'classe_id' => $request->input('classe_id', ''),
            'etat' => $request->input('etat', ''),
            'recherche' => $request->input('recherche', ''),
            'annee_id' => $request->input('annee_id', ''),
        ];
    }
}
