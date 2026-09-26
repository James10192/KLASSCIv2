<?php

namespace App\Domain\Admissions;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use App\Services\Reinscription\PortailReinscriptionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Une seule file pour les deux sortes de demandes : la candidature d'un nouvel
 * eleve et la demande de reinscription d'un ancien.
 *
 * Elles vivaient sur deux pages jumelles, avec les memes onglets et le meme
 * filtre de contact. L'agent au guichet ne sait pas toujours laquelle des deux
 * la famille a deposee : il cherchait un nom sur une page, puis sur l'autre.
 * Le type devient un filtre.
 *
 * Les deux tables restent separees : leurs regles de decision different
 * (accepter un nouvel eleve est un jugement, reconduire un ancien ne l'est
 * pas). La file ne fait que les lire ensemble, par une union triee en SQL,
 * pour que la pagination reste exacte quel que soit le volume.
 *
 * Chaque sorte n'apparait qu'a qui a le droit de la voir. La recherche vit
 * dans RechercheDesDossiers, le rechargement des lignes dans
 * HydratationDesDossiers, les etapes dans EtapeDuDossier et EtapesEnSql.
 */
class FileDesDemandes
{
    public const TYPE_NOUVELLE = 'nouvelle';

    public const TYPE_REINSCRIPTION = 'reinscription';

    /** Les vues de la file. `a_traiter` est celle qui s'ouvre. */
    public const ETATS = ['a_traiter', 'recues', 'rendez_vous', 'inscrites', 'rejetees', 'toutes'];

    public const PAR_PAGE = 25;

    /** Prefixe du compte « a traiter » d'un type, partage par le menu et le bandeau. */
    private const CLE_CACHE_A_TRAITER = 'admissions.a_traiter.';

    private const CLE_CACHE_ATTENDUES = 'admissions.accueil.attendues';

    private ?EtapesEnSql $etapes = null;

    public function __construct(
        private readonly RechercheDesDossiers $recherche,
        private readonly HydratationDesDossiers $hydratation,
        private readonly PortailReinscriptionService $campagne,
    ) {
    }

    /** L'annee que vise la campagne d'admission : elle borne l'etape « Inscrit ». */
    public function anneeDeCampagne(): ?ESBTPAnneeUniversitaire
    {
        return $this->campagne->anneeCible();
    }

    /** @return list<string> les types que cet agent peut lire */
    public static function typesVisibles(?User $agent): array
    {
        return array_values(array_filter([
            $agent?->can('inscriptions.candidatures.view') ? self::TYPE_NOUVELLE : null,
            $agent?->can('reinscriptions.demandes.view') ? self::TYPE_REINSCRIPTION : null,
        ]));
    }

    /**
     * Ce qui attend une decision, dans les types que l'agent lit. Lu par le menu
     * sur CHAQUE page : cache court, et garde sur les tables, parce que le code
     * precede la migration de quelques secondes au deploiement.
     */
    public static function aTraiter(?User $agent): int
    {
        return array_sum(array_map(fn (string $type) => self::aTraiterDuType($type), self::typesVisibles($agent)));
    }

    public static function aTraiterDuType(string $type): int
    {
        $modele = self::modele($type);

        return Cache::remember(self::CLE_CACHE_A_TRAITER.$type, 60, fn (): int => Schema::hasTable((new $modele())->getTable())
            ? self::ouvertes($modele::query())->count()
            : 0);
    }

    /**
     * Les familles encore attendues au guichet aujourd'hui : pas reçues, creneau
     * pas termine, dossier ouvert. Le meme compte que « À recevoir » sur
     * l'Accueil du jour, que le menu et le bouton de cette page affichent tous
     * les deux. Tous types confondus : l'Accueil du jour les montre tous.
     */
    public static function famillesAttenduesAujourdhui(?User $agent): int
    {
        if (! $agent?->can('inscriptions.rdv.accueil')) {
            return 0;
        }

        return Cache::remember(self::CLE_CACHE_ATTENDUES.'.'.now()->format('Y-m-d-H-i'), 60, fn (): int => Schema::hasTable('esbtp_rdv_reservations')
            ? ESBTPRdvReservation::query()
                ->where('statut', StatutReservationRdv::Confirmee->value)
                ->whereHas('creneau', fn ($c) => $c->whereDate('date', today())->whereTime('heure_fin', '>', now()->format('H:i:s')))
                ->dossierOuvert()
                ->count()
            : 0);
    }

    /** Apres une decision : le menu et le bandeau se relisent au prochain affichage. */
    public static function oublierLesCompteurs(): void
    {
        foreach ([self::TYPE_NOUVELLE, self::TYPE_REINSCRIPTION] as $type) {
            Cache::forget(self::CLE_CACHE_A_TRAITER.$type);
        }
        Cache::forget(self::CLE_CACHE_ATTENDUES.'.'.now()->format('Y-m-d-H-i'));
    }

    /**
     * @param  array{type?: string, etat?: string, etape?: string, q?: string, sans_rdv?: bool, contact?: bool}  $filtres
     */
    public function page(User $agent, array $filtres, int $page = 1): LengthAwarePaginator
    {
        $union = $this->union($agent, $filtres);

        $paginateur = DB::query()->fromSub($union, 'u')
            ->orderBy('priorite')
            ->orderByDesc('depose_le')
            // Departage unique : la liste se charge par tranches.
            ->orderBy('type')
            ->orderByDesc('id')
            ->paginate(self::PAR_PAGE, ['*'], 'page', $page);

        $paginateur->setCollection($this->hydratation->hydrater($paginateur->getCollection()));

        return $paginateur;
    }

    /**
     * Les compteurs de la page : les onglets (dossiers ouverts par type), les
     * etapes et le repere « sans rendez-vous ». Les etapes et le repere suivent
     * l'onglet choisi ; les onglets comptent toujours tous les types visibles.
     *
     * @return array{a_traiter: int, nouvelles: int, reinscriptions: int, attendues_aujourdhui: int, sans_rdv: int, etapes: array<string, int>}
     */
    public function compteurs(User $agent, string $type = ''): array
    {
        $types = self::typesVisibles($agent);
        $voulus = in_array($type, $types, true) ? [$type] : $types;
        $somme = fn (callable $filtre) => array_sum(array_map(fn (string $t) => $filtre(self::modele($t)::query())->count(), $voulus));
        $nouvelles = in_array(self::TYPE_NOUVELLE, $types, true) ? self::aTraiterDuType(self::TYPE_NOUVELLE) : 0;
        $reinscriptions = in_array(self::TYPE_REINSCRIPTION, $types, true) ? self::aTraiterDuType(self::TYPE_REINSCRIPTION) : 0;

        $etapes = array_fill_keys(array_column(EtapeDuDossier::cases(), 'value'), 0);
        foreach ($voulus as $t) {
            foreach ($this->etapes()->compter(self::modele($t)::query()) as $etape => $n) {
                $etapes[$etape] += $n;
            }
        }

        return [
            'a_traiter' => $nouvelles + $reinscriptions,
            'nouvelles' => $nouvelles,
            'reinscriptions' => $reinscriptions,
            'attendues_aujourdhui' => self::famillesAttenduesAujourdhui($agent),
            'sans_rdv' => $somme(fn ($q) => $this->sansRdv(self::ouvertes($q))),
            'etapes' => $etapes,
        ];
    }

    private function etapes(): EtapesEnSql
    {
        return $this->etapes ??= new EtapesEnSql($this->anneeDeCampagne()?->id);
    }

    /** @return class-string<ESBTPCandidature|ESBTPReinscriptionDemande> */
    private static function modele(string $type): string
    {
        return $type === self::TYPE_NOUVELLE ? ESBTPCandidature::class : ESBTPReinscriptionDemande::class;
    }

    /** Dossier ouvert : la regle des deux modeles, celle que lisent aussi les rendez-vous. */
    private static function ouvertes(Builder $q): Builder
    {
        return $q->whereNotIn('statut', $q->getModel()::statutsDossierClos());
    }

    /** @param  array<string, mixed>  $filtres */
    private function union(User $agent, array $filtres): QueryBuilder
    {
        $types = self::typesVisibles($agent);
        $type = $filtres['type'] ?? '';
        $voulus = in_array($type, $types, true) ? [$type] : $types;

        $parties = [];
        if (in_array(self::TYPE_NOUVELLE, $voulus, true)) {
            $parties[] = $this->filtrer(ESBTPCandidature::query(), $filtres)
                ->where(fn ($q) => $this->recherche->candidatures($q, (string) ($filtres['q'] ?? '')))
                ->selectRaw("'".self::TYPE_NOUVELLE."' as type, esbtp_candidatures.id, esbtp_candidatures.created_at as depose_le, ".$this->priorite('esbtp_candidatures', 'candidature_id', ESBTPCandidature::statutsDossierClos()).' as priorite')
                ->toBase();
        }
        if (in_array(self::TYPE_REINSCRIPTION, $voulus, true)) {
            $parties[] = $this->filtrer(ESBTPReinscriptionDemande::query(), $filtres)
                ->where(fn ($q) => $this->recherche->reinscriptions($q, (string) ($filtres['q'] ?? '')))
                ->selectRaw("'".self::TYPE_REINSCRIPTION."' as type, esbtp_reinscription_demandes.id, esbtp_reinscription_demandes.created_at as depose_le, ".$this->priorite('esbtp_reinscription_demandes', 'reinscription_demande_id', ESBTPReinscriptionDemande::statutsDossierClos()).' as priorite')
                ->toBase();
        }

        if ($parties === []) {
            // Aucun type lisible : une union vide, pour une page vide mais valide.
            return DB::query()->selectRaw("'' as type, 0 as id, NULL as depose_le, 0 as priorite")->whereRaw('1 = 0');
        }

        return array_reduce(array_slice($parties, 1), fn (QueryBuilder $u, QueryBuilder $p) => $u->unionAll($p), $parties[0]);
    }

    /** @param  array<string, mixed>  $filtres */
    private function filtrer(Builder $q, array $filtres): Builder
    {
        $etape = EtapeDuDossier::depuis($filtres['etape'] ?? null);

        // Une etape choisie remplace la vue : elle dit deja ouvert, inscrit ou pas.
        if ($etape !== null) {
            $this->etapes()->filtrer($q, $etape);
        } else {
            $this->vue($q, in_array($filtres['etat'] ?? '', self::ETATS, true) ? $filtres['etat'] : 'a_traiter');
        }

        return $q
            ->when(! empty($filtres['sans_rdv']), fn ($q) => $this->sansRdv($q))
            ->when(! empty($filtres['contact']), fn ($q) => $q->contactNonConfirme());
    }

    private function vue(Builder $q, string $etat): void
    {
        $modele = $q->getModel();

        match ($etat) {
            'a_traiter' => self::ouvertes($q),
            'recues' => $this->recues(self::ouvertes($q)),
            'rendez_vous' => $this->avecRdvAVenir(self::ouvertes($q)),
            // Comme leurs compteurs : la semaine en cours. L'historique complet
            // est la vue `toutes`, avec la recherche.
            'inscrites' => $q->where('statut', $modele::STATUT_CONVERTIE)->where('traite_at', '>=', now()->startOfWeek()),
            'rejetees' => $q->where('statut', $modele::STATUT_REJETEE)->where('traite_at', '>=', now()->startOfWeek()),
            default => $q,
        };
    }

    /**
     * 0 : reçue au guichet, la decision attend ; 1 : a traiter ; 2 : close.
     * Une famille deja sur place passe devant : elle attend au comptoir.
     *
     * @param  list<string>  $clos
     */
    private function priorite(string $table, string $cle, array $clos): string
    {
        $liste = implode(',', array_map(fn ($s) => "'".$s."'", $clos));
        $honoree = StatutReservationRdv::Honoree->value;

        return "CASE WHEN {$table}.statut IN ({$liste}) THEN 2"
            ." WHEN EXISTS (SELECT 1 FROM esbtp_rdv_reservations r WHERE r.{$cle} = {$table}.id AND r.statut = '{$honoree}') THEN 0"
            .' ELSE 1 END';
    }

    private function recues(Builder $q): Builder
    {
        return $q->whereHas('reservations', fn ($r) => $r->where('statut', StatutReservationRdv::Honoree->value));
    }

    private function avecRdvAVenir(Builder $q): Builder
    {
        return $q->whereHas('reservations', fn ($r) => $r->where('statut', StatutReservationRdv::Confirmee->value)
            ->whereHas('creneau', fn ($c) => $c->whereDate('date', '>=', today())));
    }

    private function sansRdv(Builder $q): Builder
    {
        return $q->whereDoesntHave('reservations', fn ($r) => $r->occupantes());
    }
}
