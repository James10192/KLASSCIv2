<?php

namespace App\Domain\Admissions;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use App\Services\Portail\ReferencePublique;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
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
 * Chaque sorte n'apparait qu'a qui a le droit de la voir.
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

    public function __construct(private readonly ReferencePublique $references)
    {
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
     * @param  array{type?: string, etat?: string, q?: string, sans_rdv?: bool, contact?: bool}  $filtres
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

        $paginateur->setCollection($this->hydrater($paginateur->getCollection()));

        return $paginateur;
    }

    /**
     * Les compteurs du bandeau, chacun sur les types visibles seulement.
     *
     * @return array<string, int>
     */
    public function compteurs(User $agent): array
    {
        $types = self::typesVisibles($agent);
        $debutSemaine = now()->startOfWeek();
        $semainePassee = $debutSemaine->copy()->subWeek();
        $somme = fn (callable $filtre) => array_sum(array_map(fn (string $type) => $filtre(self::modele($type)::query())->count(), $types));
        $closes = fn (string $statut, $depuis, $jusqua = null) => fn ($q) => $q->where('statut', constant(get_class($q->getModel()).'::'.$statut))
            ->when($jusqua, fn ($q) => $q->whereBetween('traite_at', [$depuis, $jusqua]), fn ($q) => $q->where('traite_at', '>=', $depuis));
        $nouvelles = in_array(self::TYPE_NOUVELLE, $types, true) ? self::aTraiterDuType(self::TYPE_NOUVELLE) : 0;
        $reinscriptions = in_array(self::TYPE_REINSCRIPTION, $types, true) ? self::aTraiterDuType(self::TYPE_REINSCRIPTION) : 0;

        return [
            'a_traiter' => $nouvelles + $reinscriptions,
            'nouvelles' => $nouvelles,
            'reinscriptions' => $reinscriptions,
            'recues' => $somme(fn ($q) => $this->recues(self::ouvertes($q))),
            'rendez_vous' => $somme(fn ($q) => $this->avecRdvAVenir(self::ouvertes($q))),
            'attendues_aujourdhui' => self::famillesAttenduesAujourdhui($agent),
            'sans_rdv' => $somme(fn ($q) => $this->sansRdv(self::ouvertes($q))),
            'contact' => $somme(fn ($q) => self::ouvertes($q)->contactNonConfirme()),
            'inscrites_semaine' => $somme($closes('STATUT_CONVERTIE', $debutSemaine)),
            'inscrites_semaine_passee' => $somme($closes('STATUT_CONVERTIE', $semainePassee, $debutSemaine)),
            'rejetees_semaine' => $somme($closes('STATUT_REJETEE', $debutSemaine)),
        ];
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
                ->where(fn ($q) => $this->chercherCandidature($q, (string) ($filtres['q'] ?? '')))
                ->selectRaw("'".self::TYPE_NOUVELLE."' as type, esbtp_candidatures.id, esbtp_candidatures.created_at as depose_le, ".$this->priorite('esbtp_candidatures', 'candidature_id', ESBTPCandidature::statutsDossierClos()).' as priorite')
                ->toBase();
        }
        if (in_array(self::TYPE_REINSCRIPTION, $voulus, true)) {
            $parties[] = $this->filtrer(ESBTPReinscriptionDemande::query(), $filtres)
                ->where(fn ($q) => $this->chercherDemande($q, (string) ($filtres['q'] ?? '')))
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
        $etat = in_array($filtres['etat'] ?? '', self::ETATS, true) ? $filtres['etat'] : 'a_traiter';
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

        return $q
            ->when(! empty($filtres['sans_rdv']), fn ($q) => $this->sansRdv($q))
            ->when(! empty($filtres['contact']), fn ($q) => $q->contactNonConfirme());
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

    private function chercherCandidature(Builder $q, string $texte): void
    {
        $texte = trim($texte);
        if ($texte === '') {
            return;
        }
        $like = '%'.addcslashes($texte, '%_\\').'%';
        $reference = $this->references->normaliser($texte);
        $chiffres = preg_replace('/\D/', '', $texte);

        $q->where(fn ($w) => $w->where('nom', 'like', $like)->orWhere('prenoms', 'like', $like)
            ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$like])
            ->orWhereRaw("CONCAT(prenoms, ' ', nom) LIKE ?", [$like])
            ->orWhere('email', 'like', $like)
            ->when($reference !== '', fn ($w) => $w->orWhere('reference_publique', $reference))
            ->when(strlen($chiffres) >= 6, fn ($w) => $w->orWhere('telephone', 'like', '%'.$chiffres.'%')));
    }

    private function chercherDemande(Builder $q, string $texte): void
    {
        $texte = trim($texte);
        if ($texte === '') {
            return;
        }
        $like = '%'.addcslashes($texte, '%_\\').'%';
        $reference = $this->references->normaliser($texte);
        $chiffres = preg_replace('/\D/', '', $texte);

        $q->where(fn ($w) => $w
            ->whereHas('etudiant', fn ($e) => $e->where(fn ($e) => $e->where('nom', 'like', $like)->orWhere('prenoms', 'like', $like)
                ->orWhere('matricule', 'like', $like)
                ->orWhereRaw("CONCAT(nom, ' ', prenoms) LIKE ?", [$like])
                ->orWhereRaw("CONCAT(prenoms, ' ', nom) LIKE ?", [$like])
                ->when(strlen($chiffres) >= 6, fn ($e) => $e->orWhere('telephone', 'like', '%'.$chiffres.'%'))))
            ->when($reference !== '', fn ($w) => $w->orWhere('reference_publique', $reference)));
    }

    /**
     * Les lignes de l'union, rechargees en modeles avec ce que l'ecran lit.
     *
     * @param  Collection<int, object>  $lignes
     * @return Collection<int, DemandeDInscription>
     */
    private function hydrater(Collection $lignes): Collection
    {
        $ids = $lignes->groupBy('type')->map(fn ($g) => $g->pluck('id')->all());
        $avecRdv = ['reservations' => fn ($r) => $r->occupantes()->with('creneau', 'accueilliPar:id,name')->latest('id')];

        $candidatures = ESBTPCandidature::query()
            ->with(['anneeUniversitaire:id,name', 'filiere:id,name', 'niveau:id,name', 'traitePar:id,name'] + $avecRdv)
            ->findMany($ids[self::TYPE_NOUVELLE] ?? [])->keyBy('id');
        $demandes = ESBTPReinscriptionDemande::query()
            ->with(['etudiant:id,nom,prenoms,matricule,telephone,email,email_personnel,date_naissance', 'anneeUniversitaire:id,name,is_current', 'classeSouhaitee:id,name', 'traitePar:id,name', 'inscription.classe:id,name'] + $avecRdv)
            ->findMany($ids[self::TYPE_REINSCRIPTION] ?? [])->keyBy('id');
        $inscrits = $this->dejaInscrits($demandes);

        return $lignes->map(fn ($l) => $l->type === self::TYPE_NOUVELLE
            ? ($candidatures->has($l->id) ? DemandeDInscription::deCandidature($candidatures[$l->id]) : null)
            : ($demandes->has($l->id) ? DemandeDInscription::deReinscription($demandes[$l->id], isset($inscrits[$demandes[$l->id]->etudiant_id.'-'.$demandes[$l->id]->annee_universitaire_id])) : null))
            ->filter()->values();
    }

    /**
     * Les couples (etudiant, annee) deja inscrits, en une requete pour toute la
     * tranche : la meme regle que ESBTPInscription::aUneInscriptionVivantePour().
     *
     * @param  Collection<int, ESBTPReinscriptionDemande>  $demandes
     * @return array<string, true>
     */
    private function dejaInscrits(Collection $demandes): array
    {
        $ouvertes = $demandes->filter->estTraitable();
        if ($ouvertes->isEmpty()) {
            return [];
        }

        return \App\Models\ESBTPInscription::query()
            ->whereIn('etudiant_id', $ouvertes->pluck('etudiant_id')->unique())
            ->whereIn('annee_universitaire_id', $ouvertes->pluck('annee_universitaire_id')->unique())
            ->whereIn('status', ['en_attente', 'active'])
            ->get(['etudiant_id', 'annee_universitaire_id'])
            ->mapWithKeys(fn ($i) => [$i->etudiant_id.'-'.$i->annee_universitaire_id => true])
            ->all();
    }
}
