<?php

namespace App\Services\RendezVous;

use App\Enums\StatutReservationRdv;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPRdvReservation;
use App\Models\ESBTPReinscriptionDemande;
use App\Services\FuzzyNameMatcher;
use App\Services\Portail\ReferencePublique;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Retrouver le rendez-vous d'une famille, quel que soit le jour.
 *
 * L'accueil du jour ne cherche que dans sa journee : une famille qui appelle
 * « c'est quand, notre rendez-vous ? » ne dit pas le jour, elle dit son nom.
 * Une seule definition de la recherche, pour l'ecran et pour klassci-cli.
 *
 * Le texte libre se decoupe en mots, et chaque mot doit se retrouver quelque
 * part : nom, prenoms, courriel, reference du dossier, et pour une
 * reinscription le nom et le matricule de l'eleve. Sans correspondance
 * exacte, une orthographe voisine est proposee et signalee. Une saisie faite
 * de chiffres est lue comme un numero de telephone (celui de la reservation
 * ou celui de l'eleve), espaces et indicatif compris.
 */
class RechercheRdv
{
    public const QUAND_A_VENIR = 'a_venir';

    public const QUAND_PASSES = 'passes';

    public const QUAND_TOUS = 'tous';

    public const TYPE_CANDIDATURE = 'candidature';

    public const TYPE_REINSCRIPTION = 'reinscription';

    /** Au-dela, la saisie est trop vague pour chercher une orthographe voisine. */
    private const CANDIDATS_MAX = 2000;

    /** Le seuil de la liste des etudiants : en dessous, ce n'est plus la meme personne. */
    private const SEUIL_APPROCHANT = 80;

    /** @var array<string, array{ids: list<int>, approchant: bool}> */
    private array $memo = [];

    public function __construct(
        private readonly ReferencePublique $references,
        private readonly FuzzyNameMatcher $matcher,
    ) {
    }

    /**
     * @return array{q: string, quand: string, statut: string, type: string}
     */
    public function filtres(Request $request): array
    {
        $quand = (string) $request->input('quand', '');
        $statut = (string) $request->input('statut', '');
        $type = (string) $request->input('type', '');
        $q = trim((string) $request->input('q', ''));

        return [
            'q' => mb_substr($q, 0, 120),
            // Sans texte, on regarde ce qui vient ; avec un nom, on cherche
            // partout : la famille a peut-etre deja ete recue.
            'quand' => in_array($quand, [self::QUAND_A_VENIR, self::QUAND_PASSES, self::QUAND_TOUS], true)
                ? $quand
                : ($q === '' ? self::QUAND_A_VENIR : self::QUAND_TOUS),
            'statut' => in_array($statut, StatutReservationRdv::values(), true) ? $statut : '',
            'type' => in_array($type, [self::TYPE_CANDIDATURE, self::TYPE_REINSCRIPTION], true) ? $type : '',
        ];
    }

    /**
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     */
    public function requete(array $filtres): Builder
    {
        $requete = ESBTPRdvReservation::query()
            ->join('esbtp_rdv_creneaux', 'esbtp_rdv_creneaux.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->select('esbtp_rdv_reservations.*')
            ->with(['creneau', 'candidature', 'demande.etudiant:id,matricule', 'accueilliPar:id,name'])
            // Meme compte que l'accueil du jour : les rendez-vous deja manques.
            ->withCount(['reprogrammations as absences' => fn ($r) => $r->where('non_venue', true)]);

        $this->restreindre($requete, $filtres);

        if ($filtres['q'] !== '') {
            $ids = $this->correspondances($filtres)['ids'];
            $requete->whereIn('esbtp_rdv_reservations.id', $ids !== [] ? $ids : [0]);
        }

        // Ce qui vient se lit dans l'ordre du calendrier ; le passe, du plus
        // recent au plus ancien. L'identifiant departage : la liste se charge
        // par tranches.
        $sens = $filtres['quand'] === self::QUAND_A_VENIR ? 'asc' : 'desc';

        return $requete
            ->orderBy('esbtp_rdv_creneaux.date', $sens)
            ->orderBy('esbtp_rdv_creneaux.heure_debut', $sens)
            ->orderBy('esbtp_rdv_reservations.id', $sens);
    }

    /**
     * Les identifiants des rendez-vous qui repondent au texte, et la maniere
     * dont ils ont ete trouves. Calcule une fois par jeu de filtres : l'ecran
     * pose ensuite la meme question pour son bandeau « resultats approchants ».
     *
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     * @return array{ids: list<int>, approchant: bool}
     */
    public function correspondances(array $filtres): array
    {
        $cle = serialize($filtres);
        if (isset($this->memo[$cle])) {
            return $this->memo[$cle];
        }

        $q = $filtres['q'];
        $chiffres = $this->chiffresSaisis($q);

        return $this->memo[$cle] = $chiffres !== null
            ? ['ids' => $this->parChiffres($this->base($filtres), $q, $chiffres), 'approchant' => false]
            : $this->parTexte($this->base($filtres), $q);
    }

    /**
     * La saisie a-t-elle ete elargie a une orthographe voisine faute de
     * correspondance exacte ? L'ecran le dit, pour qu'on ne prenne pas un
     * homonyme pour la famille cherchee.
     *
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     */
    public function approchant(array $filtres): bool
    {
        return $filtres['q'] !== '' && $this->correspondances($filtres)['approchant'];
    }

    /**
     * Les eleves de l'ecole que la saisie designe et qui n'ont AUCUN rendez-vous.
     *
     * « Je ne le trouve pas » voulait souvent dire « la famille n'a jamais
     * reserve » : la liste vide ne permettait pas de trancher entre une panne
     * de la recherche et une famille a relancer. On le dit, avec l'etat de sa
     * demande de reinscription.
     *
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     * @return Collection<int, ESBTPEtudiant>
     */
    public function elevesSansRendezVous(array $filtres, int $limite = 5): Collection
    {
        $q = $filtres['q'];
        if ($q === '' || $filtres['type'] === self::TYPE_CANDIDATURE) {
            return collect();
        }

        $avecRendezVous = DB::table('esbtp_rdv_reservations')
            ->join('esbtp_reinscription_demandes', 'esbtp_reinscription_demandes.id', '=', 'esbtp_rdv_reservations.reinscription_demande_id')
            ->whereNotNull('esbtp_reinscription_demandes.etudiant_id')
            // NOT IN sur une sous-requete qui rendrait un NULL ne rendrait plus rien.
            ->select('esbtp_reinscription_demandes.etudiant_id');

        $requete = ESBTPEtudiant::query()
            ->select('id', 'matricule', 'nom', 'prenoms')
            ->whereNotIn('id', $avecRendezVous);

        $chiffres = $this->chiffresSaisis($q);
        if ($chiffres !== null) {
            $likeSaisie = '%'.$this->echapper($q).'%';
            $requete->where(function (Builder $w) use ($likeSaisie, $chiffres) {
                $w->where('matricule', 'like', $likeSaisie);
                foreach ($this->variantesTelephone($chiffres) as $variante) {
                    $like = '%'.$this->echapper($variante).'%';
                    $w->orWhere('matricule', 'like', $like)->orWhere('telephone', 'like', $like);
                }
            });
        } else {
            $mots = $this->mots($q);
            if ($mots === []) {
                return collect();
            }
            // Meme exigence que la recherche exacte : chaque mot se retrouve.
            foreach ($mots as $mot) {
                $like = '%'.$this->echapper($mot).'%';
                $requete->where(fn (Builder $w) => $w->where('nom', 'like', $like)
                    ->orWhere('prenoms', 'like', $like)
                    ->orWhere('matricule', 'like', $like));
            }
        }

        $eleves = $requete->orderBy('nom')->orderBy('prenoms')->limit($limite)->get();
        if ($eleves->isEmpty()) {
            return $eleves;
        }

        // La derniere demande de reinscription dit quoi faire : relancer la
        // famille pour qu'elle reserve, ou l'inviter a deposer sa demande.
        $demandes = ESBTPReinscriptionDemande::query()
            ->whereIn('etudiant_id', $eleves->pluck('id'))
            ->orderByDesc('id')
            ->get(['id', 'etudiant_id', 'statut', 'reference_publique', 'created_at'])
            ->unique('etudiant_id')
            ->keyBy('etudiant_id');

        return $eleves->each(fn (ESBTPEtudiant $e) => $e->setRelation('derniereDemandeRdv', $demandes->get($e->id)));
    }

    /**
     * La requete sans le texte : memes filtres de periode, statut et dossier,
     * pour que le texte ne cherche que parmi ce que l'ecran peut afficher.
     *
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     */
    private function base(array $filtres): Builder
    {
        $requete = ESBTPRdvReservation::query()
            ->join('esbtp_rdv_creneaux', 'esbtp_rdv_creneaux.id', '=', 'esbtp_rdv_reservations.creneau_id');
        $this->restreindre($requete, $filtres);

        return $requete;
    }

    /**
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     */
    private function restreindre(Builder $requete, array $filtres): void
    {
        $aujourdhui = Carbon::today()->toDateString();

        match ($filtres['quand']) {
            self::QUAND_A_VENIR => $requete->where('esbtp_rdv_creneaux.date', '>=', $aujourdhui),
            self::QUAND_PASSES => $requete->where('esbtp_rdv_creneaux.date', '<', $aujourdhui),
            default => null,
        };

        if ($filtres['statut'] !== '') {
            $requete->where('esbtp_rdv_reservations.statut', $filtres['statut']);
        }
        if ($filtres['type'] === self::TYPE_CANDIDATURE) {
            $requete->whereNotNull('esbtp_rdv_reservations.candidature_id');
        } elseif ($filtres['type'] === self::TYPE_REINSCRIPTION) {
            $requete->whereNotNull('esbtp_rdv_reservations.reinscription_demande_id');
        }
    }

    /**
     * Une saisie de chiffres : un telephone (« 07 07 12 34 », « +225 0707… »),
     * mais aussi un matricule ou une reference, dont le format se regle par
     * ecole et peut n'etre fait que de chiffres (« 22-0545 »).
     */
    private function chiffresSaisis(string $q): ?string
    {
        if (preg_match('/^[\d\s+().-]+$/', $q) !== 1) {
            return null;
        }
        $chiffres = preg_replace('/\D/', '', $q) ?? '';

        return strlen($chiffres) >= 4 ? $chiffres : null;
    }

    /**
     * Le numero tel qu'il a ete tape, et sans indicatif : la famille dit
     * « +225 05 00… » quand la fiche de l'eleve porte « 0500… ».
     *
     * @return list<string>
     */
    private function variantesTelephone(string $chiffres): array
    {
        $variantes = [$chiffres];
        foreach (['00225', '225'] as $indicatif) {
            if (str_starts_with($chiffres, $indicatif) && strlen($chiffres) - strlen($indicatif) >= 8) {
                $variantes[] = substr($chiffres, strlen($indicatif));
                break;
            }
        }

        return $variantes;
    }

    /**
     * @return list<int>
     */
    private function parChiffres(Builder $requete, string $q, string $chiffres): array
    {
        $likeSaisie = '%'.$this->echapper($q).'%';
        $variantes = $this->variantesTelephone($chiffres);

        $requete->where(function (Builder $w) use ($likeSaisie, $variantes, $chiffres) {
            foreach ($variantes as $variante) {
                $w->orWhere('esbtp_rdv_reservations.telephone', 'like', '%'.$this->echapper($variante).'%');
            }
            // Le numero de l'eleve, pas seulement celui laisse a la reservation :
            // un parent reserve souvent avec le sien.
            $w->orWhereHas('demande.etudiant', function (Builder $e) use ($likeSaisie, $variantes) {
                $e->where('matricule', 'like', $likeSaisie);
                foreach ($variantes as $variante) {
                    $like = '%'.$this->echapper($variante).'%';
                    $e->orWhere('matricule', 'like', $like)->orWhere('telephone', 'like', $like);
                }
            });
            $this->references($w, $chiffres);
        });

        return $requete->pluck('esbtp_rdv_reservations.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Le texte libre, comme la liste des etudiants : on reunit en SQL les
     * candidats plausibles, puis on juge en PHP avec la meme normalisation que
     * FuzzyNameMatcher — accents, apostrophes (’ ou '), tirets et ordre des
     * noms n'ont plus d'importance.
     *
     * D'abord l'exact : chaque mot se retrouve quelque part. A defaut
     * seulement, une orthographe voisine (« KOUADO » pour « KOUADIO »), que
     * l'ecran signale : un homonyme ne doit pas passer pour la famille.
     *
     * @return array{ids: list<int>, approchant: bool}
     */
    private function parTexte(Builder $requete, string $q): array
    {
        $mots = $this->mots($q);
        if ($mots === []) {
            return ['ids' => [], 'approchant' => false];
        }

        $compacte = $this->references->normaliser($q);
        $requete->where(function (Builder $w) use ($mots, $compacte) {
            // Le nom sans apostrophe ni tiret : « NGUESSAN » attrape « N'GUESSAN ».
            $colle = "REPLACE(REPLACE(REPLACE(CONCAT_WS('', esbtp_rdv_reservations.nom, esbtp_rdv_reservations.prenoms), '\u{2019}', ''), '''', ''), '-', '')";
            foreach ($mots as $mot) {
                $w->orWhereRaw($colle.' LIKE ?', ['%'.$this->echapper($mot).'%']);
                foreach (array_unique([$mot, $this->racine($mot)]) as $morceau) {
                    $like = '%'.$this->echapper($morceau).'%';
                    $w->orWhere('esbtp_rdv_reservations.nom', 'like', $like)
                        ->orWhere('esbtp_rdv_reservations.prenoms', 'like', $like)
                        ->orWhere('esbtp_rdv_reservations.email', 'like', $like)
                        ->orWhereHas('demande.etudiant', fn (Builder $e) => $e
                            ->where('matricule', 'like', $like)
                            ->orWhere('nom', 'like', $like)
                            ->orWhere('prenoms', 'like', $like));
                }
            }
            $this->references($w, $compacte);
        });

        $candidats = $requete
            ->select('esbtp_rdv_reservations.id', 'esbtp_rdv_reservations.nom', 'esbtp_rdv_reservations.prenoms',
                'esbtp_rdv_reservations.email', 'esbtp_rdv_reservations.candidature_id', 'esbtp_rdv_reservations.reinscription_demande_id')
            ->with(['candidature:id,reference_publique', 'demande:id,etudiant_id,reference_publique', 'demande.etudiant:id,matricule,nom,prenoms'])
            ->limit(self::CANDIDATS_MAX)
            ->get();

        $exacts = $candidats->filter(function (ESBTPRdvReservation $r) use ($mots, $compacte) {
            $botte = ' '.$this->matcher->normalizeString(implode(' ', $this->champs($r))).' ';
            $references = $this->references->normaliser(implode(' ', [
                $r->candidature?->reference_publique, $r->demande?->reference_publique,
            ]));
            if (strlen($compacte) >= 4 && str_contains($references, $compacte)) {
                return true;
            }
            // « NGUESSAN » tape d'un bloc retrouve « N'GUESSAN » : on compare
            // aussi au texte sans espaces.
            $colle = str_replace(' ', '', $botte);
            foreach ($mots as $mot) {
                if (! str_contains($botte, $mot) && ! str_contains($colle, $mot)) {
                    return false;
                }
            }

            return true;
        });

        if ($exacts->isNotEmpty()) {
            return ['ids' => $exacts->pluck('id')->map(fn ($id) => (int) $id)->values()->all(), 'approchant' => false];
        }

        $voisins = $this->matcher->match($q, $candidats, fn (ESBTPRdvReservation $r) => $this->champs($r), [
            'threshold' => self::SEUIL_APPROCHANT,
            'boosts' => ['matricule' => 20],
        ]);

        return ['ids' => $voisins->pluck('id')->map(fn ($id) => (int) $id)->values()->all(), 'approchant' => $voisins->isNotEmpty()];
    }

    /**
     * Ce qu'on compare au texte : le nom laisse a la reservation, dans les deux
     * ordres, et pour une reinscription celui de l'eleve — le parent qui a
     * reserve a parfois donne le sien.
     *
     * @return array<string, string|null>
     */
    private function champs(ESBTPRdvReservation $r): array
    {
        $eleve = $r->demande?->etudiant;

        return [
            'nom_complet' => trim($r->nom.' '.$r->prenoms),
            'nom_inverse' => trim($r->prenoms.' '.$r->nom),
            'eleve' => $eleve ? trim($eleve->nom.' '.$eleve->prenoms) : null,
            'eleve_inverse' => $eleve ? trim($eleve->prenoms.' '.$eleve->nom) : null,
            'matricule' => $eleve?->matricule,
            'email' => $r->email,
        ];
    }

    /**
     * Les mots de la saisie, normalises comme FuzzyNameMatcher. Une lettre
     * isolee (le « N » de « N'GUESSAN ») ne filtre rien et se laisse tomber.
     *
     * @return list<string>
     */
    private function mots(string $q): array
    {
        $normalise = $this->matcher->normalizeString($q);
        $mots = array_values(array_unique(array_filter(explode(' ', $normalise), fn ($m) => strlen($m) >= 2)));

        return $mots !== [] ? $mots : array_values(array_filter(explode(' ', $normalise), fn ($m) => $m !== ''));
    }

    /**
     * Le debut d'un mot long, pour que SQL ramene aussi les orthographes
     * voisines (« KOUADO » attrape « KOUADIO ») ; c'est le score qui trie.
     */
    private function racine(string $mot): string
    {
        return strlen($mot) >= 5 ? substr($mot, 0, 4) : $mot;
    }

    /**
     * Les references sont stockees sans tiret ni espace (ReferencePublique).
     */
    private function references(Builder $w, string $reference): void
    {
        if (strlen($reference) < 4) {
            return;
        }
        $likeRef = '%'.$reference.'%';
        $w->orWhereHas('candidature', fn (Builder $c) => $c->where('reference_publique', 'like', $likeRef))
            ->orWhereHas('demande', fn (Builder $d) => $d->where('reference_publique', 'like', $likeRef));
    }

    private function echapper(string $valeur): string
    {
        return addcslashes($valeur, '\\%_');
    }
}
