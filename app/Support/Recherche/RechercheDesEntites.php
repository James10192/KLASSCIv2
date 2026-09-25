<?php

namespace App\Support\Recherche;

use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPTeacher;
use App\Support\PorteDeRoute;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

/**
 * Les fiches (étudiants, classes, filières, matières, enseignants, paiements)
 * que la palette Ctrl K / ⌘ K retrouve par leur nom, matricule ou numéro.
 *
 * Chaque groupe a DEUX portes, et il faut franchir les deux :
 *
 *   1. sa permission (`students.view`, `classes.view`…), comme le menu ;
 *   2. la route de la fiche qu'il ouvre, lue par PorteDeRoute. Un groupe dont
 *      la fiche refuserait l'utilisateur n'est pas cherché du tout : un
 *      résultat qui mène à un refus d'accès est pire que pas de résultat.
 *
 * Les paiements ont une troisième règle : qui ne tient que
 * `paiements.view_own` ne retrouve que ce qu'il a lui-même encaissé — la
 * même portée que la liste des paiements (PaymentFilterService).
 *
 * Aucune liste de personnel ici. L'ancienne recherche proposait les
 * secrétaires et administrateurs, mais chaque résultat ouvrait le profil de
 * l'utilisateur CONNECTÉ, pas celui de la personne trouvée : l'écran du
 * personnel n'a pas de fiche individuelle adressable.
 */
final class RechercheDesEntites
{
    /** Plafond dur par groupe dans la palette, quoi que demande l'appelant. */
    public const LIMITE_PALETTE = 5;

    /** Plafond de la page de résultats complète. */
    public const LIMITE_PAGE = 20;

    /** Groupes connus, dans l'ordre d'affichage. */
    public const GROUPES = ['etudiants', 'paiements', 'classes', 'filieres', 'matieres', 'enseignants'];

    /**
     * @param  list<string>|null  $seulement  groupes à chercher (null = tous)
     * @return list<array{group: string, type: string, id: int, title: string, subtitle: string, url: string, icon: string}>
     */
    public function chercher(string $saisie, ?Authorizable $utilisateur, int $limite, ?array $seulement = null): array
    {
        $saisie = trim($saisie);

        if ($utilisateur === null || mb_strlen($saisie) < 2) {
            return [];
        }

        $jetons = array_slice(preg_split('/[\s,]+/u', $saisie, -1, PREG_SPLIT_NO_EMPTY) ?: [], 0, 4);
        $resultats = [];

        foreach (self::GROUPES as $groupe) {
            if ($seulement !== null && ! in_array($groupe, $seulement, true)) {
                continue;
            }
            if (! $this->groupeOuvert($groupe, $utilisateur)) {
                continue;
            }

            $resultats = array_merge($resultats, $this->{'chercher'.ucfirst($groupe)}($saisie, $jetons, $utilisateur, $limite));
        }

        return $resultats;
    }

    /** Les groupes que cet utilisateur a le droit de voir. @return list<string> */
    public function groupesOuverts(?Authorizable $utilisateur): array
    {
        if ($utilisateur === null) {
            return [];
        }

        return array_values(array_filter(self::GROUPES, fn ($g) => $this->groupeOuvert($g, $utilisateur)));
    }

    private function groupeOuvert(string $groupe, Authorizable $utilisateur): bool
    {
        [$permissions, $routeFiche] = match ($groupe) {
            'etudiants' => [['students.view'], 'esbtp.etudiants.show'],
            'paiements' => [['paiements.view', 'paiements.view_own'], 'esbtp.paiements.show'],
            'classes' => [['classes.view'], 'esbtp.classes.show'],
            'filieres' => [['filieres.view'], 'esbtp.filieres.show'],
            'matieres' => [['matieres.view'], 'esbtp.matieres.show'],
            'enseignants' => [['teachers.view'], 'esbtp.enseignants.show'],
        };

        $tenue = false;
        foreach ($permissions as $permission) {
            if ($utilisateur->can($permission)) {
                $tenue = true;
                break;
            }
        }

        return $tenue
            && Route::has($routeFiche)
            && PorteDeRoute::verdict($routeFiche, $utilisateur) !== false;
    }

    /** @param list<string> $jetons */
    private function chercherEtudiants(string $saisie, array $jetons, Authorizable $u, int $limite): array
    {
        $etudiants = ESBTPEtudiant::query()
            ->select(['id', 'nom', 'prenoms', 'matricule'])
            ->where(function (Builder $q) use ($saisie, $jetons) {
                $q->where('matricule', 'like', self::commencePar($saisie))
                    ->orWhere(function (Builder $tous) use ($jetons) {
                        // « Kouamé Patrick » comme « Patrick Kouamé » : chaque mot
                        // doit se trouver dans le nom, les prénoms ou le matricule.
                        foreach ($jetons as $jeton) {
                            $tous->where(function (Builder $un) use ($jeton) {
                                $motif = self::contient($jeton);
                                $un->where('nom', 'like', $motif)
                                    ->orWhere('prenoms', 'like', $motif)
                                    ->orWhere('matricule', 'like', $motif);
                            });
                        }
                    });
            })
            ->with('classe')
            ->orderBy('nom')
            ->limit($limite)
            ->get();

        return $etudiants->map(fn (ESBTPEtudiant $e) => self::resultat(
            'Étudiants', 'etudiant', $e->id,
            trim($e->nom.' '.$e->prenoms),
            self::joindre([$e->matricule, optional($e->classe)->name]),
            route('esbtp.etudiants.show', $e->id),
            'fa-user-graduate'
        ))->all();
    }

    /** @param list<string> $jetons */
    private function chercherPaiements(string $saisie, array $jetons, Authorizable $u, int $limite): array
    {
        $motif = self::contient($saisie);

        $requete = ESBTPPaiement::query()
            ->where(function (Builder $q) use ($motif) {
                $q->where('numero_recu', 'like', $motif)
                    ->orWhere('reference_paiement', 'like', $motif)
                    ->orWhere('numero_transaction', 'like', $motif);
            })
            ->with('etudiant:id,nom,prenoms')
            ->orderByDesc('id')
            ->limit($limite);

        // Même portée que la liste des paiements : sans `paiements.view`, on ne
        // retrouve que ses propres encaissements.
        if (! $u->can('paiements.view')) {
            $requete->ownedBy($u);
        }

        return $requete->get()->map(function (ESBTPPaiement $p) {
            $etudiant = $p->etudiant ? trim($p->etudiant->nom.' '.$p->etudiant->prenoms) : null;
            $date = $p->date_paiement ? \Illuminate\Support\Carbon::parse($p->date_paiement)->format('d/m/Y') : null;

            return self::resultat(
                'Paiements', 'paiement', $p->id,
                $p->numero_recu ?: ($p->reference_paiement ?: 'Paiement n° '.$p->id),
                self::joindre([$etudiant, number_format((float) $p->montant, 0, ',', ' ').' FCFA', $date]),
                route('esbtp.paiements.show', $p->id),
                'fa-receipt'
            );
        })->all();
    }

    /** @param list<string> $jetons */
    private function chercherClasses(string $saisie, array $jetons, Authorizable $u, int $limite): array
    {
        return ESBTPClasse::query()
            ->where(fn (Builder $q) => self::tousLesMots($q, $jetons, ['name', 'code']))
            ->with(['filiere:id,name', 'niveauEtude:id,name'])
            ->orderBy('name')
            ->limit($limite)
            ->get()
            ->map(fn (ESBTPClasse $c) => self::resultat(
                'Classes', 'classe', $c->id, (string) $c->name,
                self::joindre([optional($c->filiere)->name, optional($c->niveauEtude)->name]),
                route('esbtp.classes.show', $c->id),
                'fa-chalkboard'
            ))->all();
    }

    /** @param list<string> $jetons */
    private function chercherFilieres(string $saisie, array $jetons, Authorizable $u, int $limite): array
    {
        return ESBTPFiliere::query()
            ->where(fn (Builder $q) => self::tousLesMots($q, $jetons, ['name', 'code']))
            ->orderBy('name')
            ->limit($limite)
            ->get(['id', 'name', 'code'])
            ->map(fn (ESBTPFiliere $f) => self::resultat(
                'Filières', 'filiere', $f->id, (string) $f->name, (string) ($f->code ?? ''),
                route('esbtp.filieres.show', $f->id),
                'fa-sitemap'
            ))->all();
    }

    /** @param list<string> $jetons */
    private function chercherMatieres(string $saisie, array $jetons, Authorizable $u, int $limite): array
    {
        return ESBTPMatiere::query()
            ->where(fn (Builder $q) => self::tousLesMots($q, $jetons, ['name', 'code']))
            ->orderBy('name')
            ->limit($limite)
            ->get(['id', 'name', 'code'])
            ->map(fn (ESBTPMatiere $m) => self::resultat(
                'Matières', 'matiere', $m->id, (string) $m->name, (string) ($m->code ?? ''),
                route('esbtp.matieres.show', $m->id),
                'fa-book'
            ))->all();
    }

    /** @param list<string> $jetons */
    private function chercherEnseignants(string $saisie, array $jetons, Authorizable $u, int $limite): array
    {
        $motif = self::contient($saisie);

        return ESBTPTeacher::query()
            ->where(function (Builder $q) use ($motif, $jetons) {
                $q->where('matricule', 'like', $motif)
                    ->orWhereHas('user', fn (Builder $user) => self::tousLesMots($user, $jetons, ['name', 'email']));
            })
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit($limite)
            ->get()
            ->map(fn (ESBTPTeacher $t) => self::resultat(
                'Enseignants', 'enseignant', $t->id,
                optional($t->user)->name ?: ($t->matricule ?: 'Enseignant n° '.$t->id),
                self::joindre([$t->matricule, $t->specialization]),
                route('esbtp.enseignants.show', $t->id),
                'fa-user-tie'
            ))->all();
    }

    /**
     * Chaque mot saisi doit se trouver dans l'une des colonnes.
     *
     * @param  list<string>  $jetons
     * @param  list<string>  $colonnes
     */
    private static function tousLesMots(Builder $q, array $jetons, array $colonnes): void
    {
        foreach ($jetons as $jeton) {
            $motif = self::contient($jeton);
            $q->where(function (Builder $un) use ($colonnes, $motif) {
                foreach ($colonnes as $colonne) {
                    $un->orWhere($colonne, 'like', $motif);
                }
            });
        }
    }

    /** `%` et `_` saisis sont des caractères, pas des jokers. */
    private static function echapper(string $texte): string
    {
        return addcslashes($texte, '\\%_');
    }

    private static function contient(string $texte): string
    {
        return '%'.self::echapper($texte).'%';
    }

    private static function commencePar(string $texte): string
    {
        return self::echapper($texte).'%';
    }

    /** @param list<?string> $morceaux */
    private static function joindre(array $morceaux): string
    {
        return implode(' · ', array_filter(array_map(fn ($m) => trim((string) $m), $morceaux), fn ($m) => $m !== ''));
    }

    private static function resultat(string $groupe, string $type, int $id, string $titre, string $sousTitre, string $url, string $icone): array
    {
        return [
            'group' => $groupe,
            'type' => $type,
            'id' => $id,
            'title' => $titre,
            'subtitle' => $sousTitre,
            'url' => $url,
            'icon' => $icone,
        ];
    }
}
