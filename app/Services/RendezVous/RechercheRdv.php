<?php

namespace App\Services\RendezVous;

use App\Domain\Notifications\PhoneNormalizer;
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
use Illuminate\Support\Facades\Log;

/**
 * Retrouver le rendez-vous d'une famille, quel que soit le jour.
 *
 * L'accueil du jour ne cherche que dans sa journee : une famille qui appelle
 * « c'est quand, notre rendez-vous ? » ne dit pas le jour, elle dit son nom.
 * Une seule definition de la recherche, pour l'ecran et pour klassci-cli.
 *
 * Le texte libre se decoupe en mots, et chaque mot doit se retrouver quelque
 * part : nom, prenoms, courriel, reference du dossier, et pour une
 * reinscription le nom et le matricule de l'eleve. La collation de la base
 * ignore deja accents et casse ; apostrophes et tirets sont retires des deux
 * cotes. Sans correspondance exacte, une orthographe voisine est proposee et
 * signalee. Une saisie faite de chiffres est lue comme un numero de telephone
 * (celui de la reservation ou celui de l'eleve), avec ou sans l'indicatif de
 * l'instance.
 */
class RechercheRdv
{
    public const QUAND_A_VENIR = 'a_venir';

    public const QUAND_PASSES = 'passes';

    public const QUAND_TOUS = 'tous';

    public const TYPE_CANDIDATURE = 'candidature';

    public const TYPE_REINSCRIPTION = 'reinscription';

    /**
     * Le repli approchant juge en PHP : au-dela, la saisie est trop vague pour
     * qu'une orthographe voisine ait un sens. La recherche exacte, elle, reste
     * en SQL et n'a pas de plafond.
     */
    private const CANDIDATS_MAX = 2000;

    /**
     * 70 : une faute de frappe sur un mot tape seul (« KOUADO » pour
     * « KOUADIO », une lettre de distance) passe tout juste — le score de
     * FuzzyNameMatcher plafonne un tel mot a 72. Deux lettres de distance sur
     * un mot seul (« KOUAKOU » pour « KOUADIO ») restent dehors. Le repli ne
     * joue que sans resultat exact, et l'ecran le signale.
     */
    private const SEUIL_APPROCHANT = 70;

    /** L'encart n'en montre pas davantage ; au-dela, on dit qu'il y en a d'autres. */
    public const ELEVES_MAX = 5;

    /** Apostrophes (droite et typographique) et tirets, retires avant de comparer. */
    private const PONCTUATION_NOM = ["\u{2019}", "'", '-'];

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

        if ($chiffres !== null) {
            return $this->memo[$cle] = ['ids' => $this->ids($this->parChiffres($this->base($filtres), $q, $chiffres)), 'approchant' => false];
        }

        $exacts = $this->ids($this->parTexteExact($this->base($filtres), $q));
        if ($exacts !== []) {
            return $this->memo[$cle] = ['ids' => $exacts, 'approchant' => false];
        }

        $voisins = $this->orthographesVoisines($this->base($filtres), $q);

        return $this->memo[$cle] = ['ids' => $voisins, 'approchant' => $voisins !== []];
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
     * demande de reinscription. Rend un eleve de plus que ELEVES_MAX : l'ecran
     * sait ainsi qu'il y en a d'autres.
     *
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     * @return Collection<int, ESBTPEtudiant>
     */
    public function elevesSansRendezVous(array $filtres): Collection
    {
        $requete = $this->elevesDesignes($filtres);
        if ($requete === null) {
            return collect();
        }

        $avecRendezVous = DB::table('esbtp_rdv_reservations')
            ->join('esbtp_reinscription_demandes', 'esbtp_reinscription_demandes.id', '=', 'esbtp_rdv_reservations.reinscription_demande_id')
            ->whereNotNull('esbtp_reinscription_demandes.etudiant_id')
            // NOT IN sur une sous-requete qui rendrait un NULL ne rendrait plus rien.
            ->select('esbtp_reinscription_demandes.etudiant_id');

        $eleves = $requete
            ->select('id', 'matricule', 'nom', 'prenoms')
            ->whereNotIn('id', $avecRendezVous)
            ->orderBy('nom')->orderBy('prenoms')
            ->limit(self::ELEVES_MAX + 1)
            ->get();
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
     * Un eleve de l'ecole repond-il a la saisie, rendez-vous ou non ?
     *
     * Null quand la question ne se pose pas (aucun texte, ou recherche limitee
     * aux nouvelles inscriptions) : l'ecran ne doit alors rien affirmer. Sert a
     * ne dire « aucun eleve ne porte ce nom » que quand on l'a verifie.
     *
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     */
    public function eleveDesigne(array $filtres): ?bool
    {
        $requete = $this->elevesDesignes($filtres);

        return $requete?->exists();
    }

    /**
     * Les eleves que la saisie designe, avec la meme tolerance que la
     * recherche exacte des rendez-vous. Aucun filtre de periode ni de statut :
     * ils portent sur les rendez-vous, pas sur l'eleve.
     *
     * @param  array{q: string, quand: string, statut: string, type: string}  $filtres
     */
    private function elevesDesignes(array $filtres): ?Builder
    {
        $q = $filtres['q'];
        if ($q === '' || $filtres['type'] === self::TYPE_CANDIDATURE) {
            return null;
        }

        $requete = ESBTPEtudiant::query();
        $chiffres = $this->chiffresSaisis($q);

        if ($chiffres !== null) {
            $likeSaisie = '%'.$this->echapper($q).'%';
            $requete->where(function (Builder $w) use ($likeSaisie, $q, $chiffres) {
                $w->where('matricule', 'like', $likeSaisie);
                foreach ($this->variantesTelephone($q, $chiffres) as $variante) {
                    $like = '%'.$this->echapper($variante).'%';
                    $w->orWhere('matricule', 'like', $like)->orWhere('telephone', 'like', $like);
                }
            });

            return $requete;
        }

        $mots = $this->mots($q);
        if ($mots === []) {
            return null;
        }
        foreach ($mots as $mot) {
            $like = '%'.$this->echapper($mot).'%';
            $requete->where(fn (Builder $w) => $w->where('nom', 'like', $like)
                ->orWhere('prenoms', 'like', $like)
                ->orWhere('matricule', 'like', $like)
                ->orWhereRaw($this->nomColle('nom', 'prenoms').' LIKE ?', [$like]));
        }

        return $requete;
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
     * Le numero tel qu'il a ete tape, et sa partie nationale quand la saisie
     * porte l'indicatif de l'instance : la famille dit « +225 05 00… » quand
     * la fiche de l'eleve porte « 0500… ». L'indicatif vient des reglages de
     * l'instance (PhoneNormalizer), jamais du code : ucao-benin est en +229.
     *
     * @return list<string>
     */
    private function variantesTelephone(string $q, string $chiffres): array
    {
        $variantes = [$chiffres];
        $parties = PhoneNormalizer::decomposer($q);
        if ($parties !== null && $parties['indicatif'] !== null && strlen($parties['national']) >= 4) {
            $variantes[] = $parties['national'];
        }

        return array_values(array_unique($variantes));
    }

    private function parChiffres(Builder $requete, string $q, string $chiffres): Builder
    {
        $likeSaisie = '%'.$this->echapper($q).'%';
        $variantes = $this->variantesTelephone($q, $chiffres);

        return $requete->where(function (Builder $w) use ($likeSaisie, $variantes, $chiffres) {
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
    }

    /**
     * L'exact, en SQL et sans plafond : chaque mot se retrouve dans l'un des
     * champs de la reservation ou de l'eleve — ou la saisie entiere est une
     * reference de dossier (« LMXB-EWX9 »).
     */
    private function parTexteExact(Builder $requete, string $q): Builder
    {
        $mots = $this->mots($q);
        if ($mots === []) {
            return $requete->whereRaw('1 = 0');
        }

        $compacte = $this->references->normaliser($q);

        return $requete->where(function (Builder $w) use ($mots, $compacte) {
            $w->where(function (Builder $tous) use ($mots) {
                foreach ($mots as $mot) {
                    $like = '%'.$this->echapper($mot).'%';
                    $tous->where(fn (Builder $champ) => $champ
                        ->where('esbtp_rdv_reservations.nom', 'like', $like)
                        ->orWhere('esbtp_rdv_reservations.prenoms', 'like', $like)
                        ->orWhere('esbtp_rdv_reservations.email', 'like', $like)
                        // « NGUESSAN » tape d'un bloc retrouve « N'GUESSAN ».
                        ->orWhereRaw($this->nomColle('esbtp_rdv_reservations.nom', 'esbtp_rdv_reservations.prenoms').' LIKE ?', [$like])
                        // Pour une reinscription, l'eleve : le parent qui a
                        // reserve a parfois donne son propre nom.
                        ->orWhereHas('demande.etudiant', fn (Builder $e) => $e
                            ->where('matricule', 'like', $like)
                            ->orWhere('nom', 'like', $like)
                            ->orWhere('prenoms', 'like', $like)
                            ->orWhereRaw($this->nomColle('nom', 'prenoms').' LIKE ?', [$like])));
                }
            });
            $this->references($w, $compacte);
        });
    }

    /**
     * Le repli, seulement quand l'exact ne rend rien : SQL reunit les
     * candidats plausibles (un mot, ou le debut d'un mot long, suffit), puis
     * FuzzyNameMatcher les juge avec la regle de la liste des etudiants.
     *
     * @return list<int>
     */
    private function orthographesVoisines(Builder $requete, string $q): array
    {
        $mots = $this->mots($q);
        if ($mots === []) {
            return [];
        }

        $requete->where(function (Builder $w) use ($mots) {
            foreach ($mots as $mot) {
                foreach (array_unique([$mot, $this->racine($mot)]) as $morceau) {
                    $like = '%'.$this->echapper($morceau).'%';
                    $w->orWhere('esbtp_rdv_reservations.nom', 'like', $like)
                        ->orWhere('esbtp_rdv_reservations.prenoms', 'like', $like)
                        ->orWhereHas('demande.etudiant', fn (Builder $e) => $e
                            ->where('nom', 'like', $like)
                            ->orWhere('prenoms', 'like', $like));
                }
            }
        });

        $candidats = $requete
            ->select('esbtp_rdv_reservations.id', 'esbtp_rdv_reservations.nom', 'esbtp_rdv_reservations.prenoms',
                'esbtp_rdv_reservations.email', 'esbtp_rdv_reservations.reinscription_demande_id')
            ->with(['demande:id,etudiant_id', 'demande.etudiant:id,matricule,nom,prenoms'])
            // Les plus recents d'abord : si la saisie est trop vague, on garde
            // les dossiers de l'annee en cours plutot qu'un tirage arbitraire.
            ->orderByDesc('esbtp_rdv_reservations.id')
            ->limit(self::CANDIDATS_MAX + 1)
            ->get();

        if ($candidats->count() > self::CANDIDATS_MAX) {
            Log::info('RechercheRdv : saisie trop vague, orthographes voisines jugees sur les plus recents seulement', [
                'q' => $q, 'plafond' => self::CANDIDATS_MAX,
            ]);
            $candidats = $candidats->take(self::CANDIDATS_MAX);
        }

        return $this->matcher->match($q, $candidats, fn (ESBTPRdvReservation $r) => $this->champs($r), [
            'threshold' => self::SEUIL_APPROCHANT,
            'boosts' => ['matricule' => 20],
        ])->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * Ce qu'on compare au texte : le nom laisse a la reservation, dans les deux
     * ordres, et pour une reinscription celui de l'eleve.
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
     * Nom et prenoms colles, sans apostrophe ni tiret, pour un LIKE.
     */
    private function nomColle(string $nom, string $prenoms): string
    {
        $sql = "CONCAT_WS('', {$nom}, {$prenoms})";
        foreach (self::PONCTUATION_NOM as $signe) {
            $sql = 'REPLACE('.$sql.', '.DB::getPdo()->quote($signe).", '')";
        }

        return $sql;
    }

    /**
     * @return list<int>
     */
    private function ids(Builder $requete): array
    {
        return $requete->pluck('esbtp_rdv_reservations.id')->map(fn ($id) => (int) $id)->all();
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
