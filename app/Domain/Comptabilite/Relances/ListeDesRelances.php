<?php

namespace App\Domain\Comptabilite\Relances;

use App\Models\ESBTPInscription;
use App\Services\RelanceCalculationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * La liste des etudiants a relancer, chargee par tranches au defilement.
 *
 * Le solde de chaque inscription se calcule en memoire (echeancier), sur toute
 * l'annee : c'est ce calcul qui dit qui doit de l'argent, donc qui figure dans
 * la liste et a quelle place. Le refaire a chaque tranche coutait plusieurs
 * dizaines de calculs complets pour parcourir une instance de 2000 inscrits.
 *
 * On garde donc l'INDEX de la liste (identifiant et situation de chaque
 * debiteur, dans l'ordre) et les compteurs ; une tranche ne recalcule que ses
 * propres lignes. Le cache ne porte que des entiers et des chaines : driver
 * `file`, pas de `tags`.
 *
 * L'arrivee sur la page, elle, recalcule toujours : un encaissement fait a
 * l'instant doit sortir l'etudiant de la liste et changer les compteurs tout
 * de suite. L'index est range PAR UTILISATEUR et par filtres, et chaque visite
 * ecrase le sien : la visite d'un autre agent ne reordonne pas la liste qu'on
 * est en train de faire defiler, et il n'y a jamais qu'une entree par agent et
 * par jeu de filtres, quel que soit le nombre de visites.
 */
class ListeDesRelances
{
    public const TRANCHE = 25;

    // Duree de vie de l'index d'une visite : le temps de la faire defiler.
    public const DUREE_CACHE_SECONDES = 600;

    public function __construct(private readonly RelanceCalculationService $calcul)
    {
    }

    /**
     * @param  array{search: string, risk: string, filiere_id: string, classe_id: string, annee_id: mixed}  $filtres
     * @param  array<string, mixed>  $query
     * @param  bool  $arrivee  true a l'arrivee sur la page, false pour une tranche suivante
     * @return array{paginated: LengthAwarePaginator, kpis: array<string, mixed>}
     */
    public function tranche(array $filtres, int $page, string $path, array $query, int $utilisateurId, bool $arrivee): array
    {
        $cle = $this->cle($filtres, $utilisateurId);
        if ($arrivee) {
            $index = $this->indexer($filtres);
            Cache::put($cle, $index, self::DUREE_CACHE_SECONDES);
        } else {
            // Index expire (visite trop longue) : on le refait.
            $index = Cache::remember($cle, self::DUREE_CACHE_SECONDES, fn () => $this->indexer($filtres));
        }

        $lignes = collect($index['lignes']);
        if ($filtres['risk'] !== '') {
            $lignes = $lignes->where('risk', $filtres['risk'])->values();
        }

        $page = max(1, $page);
        $ids = $lignes->slice(($page - 1) * self::TRANCHE, self::TRANCHE)->pluck('id')->all();
        $rang = array_flip($ids);

        $inscriptions = $ids === []
            ? collect()
            : $this->requete($filtres)->whereIn('esbtp_inscriptions.id', $ids)->get()
                ->sortBy(fn (ESBTPInscription $i) => $rang[$i->id])->values();

        $rows = $inscriptions->isEmpty()
            ? collect()
            : $this->calcul->preloadForInscriptions($inscriptions)->buildBatch($inscriptions)['rows'];

        return [
            'paginated' => new LengthAwarePaginator($rows->values(), $lignes->count(), self::TRANCHE, $page, ['path' => $path, 'query' => $query]),
            'kpis' => $index['kpis'],
        ];
    }

    /**
     * @return array{lignes: list<array{id: int, risk: string}>, kpis: array<string, mixed>}
     */
    private function indexer(array $filtres): array
    {
        $toutes = $this->requete($filtres)->get();
        $batch = $this->calcul->preloadForInscriptions($toutes)->buildBatch($toutes);

        return [
            // Seuls les debiteurs figurent dans la liste ; les compteurs, eux,
            // portent sur toute l'annee filtree.
            'lignes' => $batch['rows']
                ->filter(fn ($r) => $r->soldeRestant > 0)
                ->map(fn ($r) => ['id' => (int) $r->inscription->id, 'risk' => (string) $r->risk])
                ->values()->all(),
            'kpis' => $batch['kpis'],
        ];
    }

    private function requete(array $filtres): Builder
    {
        $search = $filtres['search'];

        return ESBTPInscription::with([
            'etudiant',
            'classe.filiere',
            'anneeUniversitaire',
            'fraisSubscriptions',
            'paiements' => fn ($q) => $q->whereIn('status', ['validé', 'en_attente'])->whereNull('deleted_at'),
        ])
            ->where('workflow_step', 'etudiant_cree')
            ->when($filtres['annee_id'], fn ($q) => $q->where('annee_universitaire_id', $filtres['annee_id']))
            ->when($filtres['classe_id'], fn ($q) => $q->where('classe_id', $filtres['classe_id']))
            ->when($filtres['filiere_id'], fn ($q) => $q->whereHas('classe', fn ($c) => $c->where('filiere_id', $filtres['filiere_id'])))
            ->when($search, fn ($q) => $q->whereHas('etudiant', fn ($e) => $e->where('nom', 'like', "%$search%")->orWhere('prenoms', 'like', "%$search%")->orWhere('matricule', 'like', "%$search%")))
            ->latest('created_at')
            // Departage stable : l'index doit donner le meme ordre a chaque calcul.
            ->orderByDesc('id');
    }

    private function cle(array $filtres, int $utilisateurId): string
    {
        // Le risque filtre l'index, il ne le change pas : un seul calcul pour
        // les quatre onglets de situation.
        unset($filtres['risk']);

        return 'relances:liste:'.$utilisateurId.':'.md5(json_encode($filtres));
    }
}
