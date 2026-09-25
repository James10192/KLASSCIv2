<?php

namespace App\Domain\Admissions;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\User;
use App\Services\Portail\ReferencePublique;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /** Ouverts, du point de vue de la decision : ce qui attend encore quelqu'un. */
    private const OUVERTS_CANDIDATURE = [ESBTPCandidature::STATUT_EN_ATTENTE, ESBTPCandidature::STATUT_ACCEPTEE];

    private const OUVERTS_DEMANDE = [ESBTPReinscriptionDemande::STATUT_EN_ATTENTE];

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
        $somme = fn (callable $candidatures, callable $demandes) => (in_array(self::TYPE_NOUVELLE, $types, true) ? $candidatures(ESBTPCandidature::query())->count() : 0)
            + (in_array(self::TYPE_REINSCRIPTION, $types, true) ? $demandes(ESBTPReinscriptionDemande::query())->count() : 0);

        return [
            'a_traiter' => $somme(fn ($q) => $q->whereIn('statut', self::OUVERTS_CANDIDATURE), fn ($q) => $q->whereIn('statut', self::OUVERTS_DEMANDE)),
            'nouvelles' => in_array(self::TYPE_NOUVELLE, $types, true) ? ESBTPCandidature::whereIn('statut', self::OUVERTS_CANDIDATURE)->count() : 0,
            'reinscriptions' => in_array(self::TYPE_REINSCRIPTION, $types, true) ? ESBTPReinscriptionDemande::whereIn('statut', self::OUVERTS_DEMANDE)->count() : 0,
            'recues' => $somme(fn ($q) => $this->recues($q->whereIn('statut', self::OUVERTS_CANDIDATURE)), fn ($q) => $this->recues($q->whereIn('statut', self::OUVERTS_DEMANDE))),
            'rendez_vous' => $somme(fn ($q) => $this->avecRdvAVenir($q->whereIn('statut', self::OUVERTS_CANDIDATURE)), fn ($q) => $this->avecRdvAVenir($q->whereIn('statut', self::OUVERTS_DEMANDE))),
            'rendez_vous_aujourdhui' => $somme(fn ($q) => $this->avecRdvLe($q->whereIn('statut', self::OUVERTS_CANDIDATURE)), fn ($q) => $this->avecRdvLe($q->whereIn('statut', self::OUVERTS_DEMANDE))),
            'sans_rdv' => $somme(fn ($q) => $this->sansRdv($q->whereIn('statut', self::OUVERTS_CANDIDATURE)), fn ($q) => $this->sansRdv($q->whereIn('statut', self::OUVERTS_DEMANDE))),
            'contact' => $somme(fn ($q) => $q->whereIn('statut', self::OUVERTS_CANDIDATURE)->contactNonConfirme(), fn ($q) => $q->whereIn('statut', self::OUVERTS_DEMANDE)->contactNonConfirme()),
            'inscrites_semaine' => $somme(fn ($q) => $q->where('statut', 'convertie')->where('traite_at', '>=', $debutSemaine), fn ($q) => $q->where('statut', 'convertie')->where('traite_at', '>=', $debutSemaine)),
            'inscrites_semaine_passee' => $somme(fn ($q) => $q->where('statut', 'convertie')->whereBetween('traite_at', [$semainePassee, $debutSemaine]), fn ($q) => $q->where('statut', 'convertie')->whereBetween('traite_at', [$semainePassee, $debutSemaine])),
            'rejetees_semaine' => $somme(fn ($q) => $q->where('statut', 'rejetee')->where('traite_at', '>=', $debutSemaine), fn ($q) => $q->where('statut', 'rejetee')->where('traite_at', '>=', $debutSemaine)),
        ];
    }

    /** @param  array<string, mixed>  $filtres */
    private function union(User $agent, array $filtres): QueryBuilder
    {
        $types = self::typesVisibles($agent);
        $type = $filtres['type'] ?? '';
        $voulus = in_array($type, $types, true) ? [$type] : $types;

        $parties = [];
        if (in_array(self::TYPE_NOUVELLE, $voulus, true)) {
            $parties[] = $this->filtrer(ESBTPCandidature::query(), self::OUVERTS_CANDIDATURE, $filtres)
                ->where(fn ($q) => $this->chercherCandidature($q, (string) ($filtres['q'] ?? '')))
                ->selectRaw("'".self::TYPE_NOUVELLE."' as type, esbtp_candidatures.id, esbtp_candidatures.created_at as depose_le, ".$this->priorite('esbtp_candidatures', 'candidature_id', self::OUVERTS_CANDIDATURE).' as priorite')
                ->toBase();
        }
        if (in_array(self::TYPE_REINSCRIPTION, $voulus, true)) {
            $parties[] = $this->filtrer(ESBTPReinscriptionDemande::query(), self::OUVERTS_DEMANDE, $filtres)
                ->where(fn ($q) => $this->chercherDemande($q, (string) ($filtres['q'] ?? '')))
                ->selectRaw("'".self::TYPE_REINSCRIPTION."' as type, esbtp_reinscription_demandes.id, esbtp_reinscription_demandes.created_at as depose_le, ".$this->priorite('esbtp_reinscription_demandes', 'reinscription_demande_id', self::OUVERTS_DEMANDE).' as priorite')
                ->toBase();
        }

        if ($parties === []) {
            // Aucun type lisible : une union vide, pour une page vide mais valide.
            return DB::query()->selectRaw("'' as type, 0 as id, NULL as depose_le, 0 as priorite")->whereRaw('1 = 0');
        }

        return array_reduce(array_slice($parties, 1), fn (QueryBuilder $u, QueryBuilder $p) => $u->unionAll($p), $parties[0]);
    }

    /**
     * @param  list<string>  $ouverts
     * @param  array<string, mixed>  $filtres
     */
    private function filtrer(Builder $q, array $ouverts, array $filtres): Builder
    {
        $etat = in_array($filtres['etat'] ?? '', self::ETATS, true) ? $filtres['etat'] : 'a_traiter';

        match ($etat) {
            'a_traiter' => $q->whereIn('statut', $ouverts),
            'recues' => $this->recues($q->whereIn('statut', $ouverts)),
            'rendez_vous' => $this->avecRdvAVenir($q->whereIn('statut', $ouverts)),
            // Comme leurs compteurs : la semaine en cours. L'historique complet
            // est la vue `toutes`, avec la recherche.
            'inscrites' => $q->where('statut', 'convertie')->where('traite_at', '>=', now()->startOfWeek()),
            'rejetees' => $q->where('statut', 'rejetee')->where('traite_at', '>=', now()->startOfWeek()),
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
     * @param  list<string>  $ouverts
     */
    private function priorite(string $table, string $cle, array $ouverts): string
    {
        $liste = implode(',', array_map(fn ($s) => "'".$s."'", $ouverts));
        $honoree = StatutReservationRdv::Honoree->value;

        return "CASE WHEN {$table}.statut NOT IN ({$liste}) THEN 2"
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

    /**
     * Encore attendues aujourd'hui : pas reçues, creneau pas termine. Le meme
     * compte que « À recevoir » sur l'Accueil du jour ; une non-venue n'y est plus.
     */
    private function avecRdvLe(Builder $q): Builder
    {
        return $q->whereHas('reservations', fn ($r) => $r->where('statut', StatutReservationRdv::Confirmee->value)
            ->whereHas('creneau', fn ($c) => $c->whereDate('date', today())->whereTime('heure_fin', '>', now()->format('H:i:s'))));
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
