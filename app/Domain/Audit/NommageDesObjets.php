<?php

namespace App\Domain\Audit;

use App\Domain\Admissions\DemandeDInscription;
use App\Helpers\EntityLabelHelper;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFacture;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNote;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPReinscriptionDemande;
use App\Models\ESBTPResultat;
use App\Models\ESBTPStudentAccessibilityProfile;
use App\Models\Setting;
use App\Models\User;
use App\Services\PermissionRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use OwenIt\Auditing\Models\Audit;

/**
 * La regle de nommage des objets du journal d'audit.
 *
 * Chaque objet se presente par ce qu'on reconnait : son nom ; a defaut, la
 * personne qu'il concerne ; puis deux ou trois reperes. Jamais « Paiement #826 »
 * — un identifiant ne dit rien a la scolarite, et c'est ce que le journal
 * affichait partout.
 *
 * Les objets d'une page sont charges par lot, une requete par sorte d'objet.
 * Un objet supprime en douceur est relu tel quel ; supprime pour de bon, il
 * est decrit par les valeurs que l'audit a gardees.
 */
class NommageDesObjets
{
    /** Relations a charger pour nommer chaque sorte d'objet. */
    private const AVEC = [
        ESBTPPaiement::class => ['etudiant:id,nom,prenoms'],
        ESBTPNote::class => ['etudiant:id,nom,prenoms', 'evaluation:id,titre,bareme', 'matiere:id,name'],
        ESBTPResultat::class => ['etudiant:id,nom,prenoms', 'matiere:id,name', 'classe:id,name'],
        ESBTPBulletin::class => ['etudiant:id,nom,prenoms', 'classe:id,name', 'anneeUniversitaire:id,name'],
        ESBTPInscription::class => ['etudiant:id,nom,prenoms', 'classe:id,name', 'anneeUniversitaire:id,name'],
        ESBTPFraisSubscription::class => ['inscription:id,etudiant_id,annee_universitaire_id', 'inscription.etudiant:id,nom,prenoms', 'inscription.anneeUniversitaire:id,name', 'fraisCategory:id,name'],
        ESBTPEvaluation::class => ['matiere:id,name', 'classe:id,name'],
        ESBTPClasse::class => ['filiere:id,name', 'niveau:id,name'],
        ESBTPReinscriptionDemande::class => ['etudiant:id,nom,prenoms,matricule', 'anneeUniversitaire:id,name'],
        ESBTPStudentAccessibilityProfile::class => ['etudiant:id,nom,prenoms'],
        ESBTPFacture::class => ['etudiant:id,nom,prenoms'],
        User::class => ['roles:id,name'],
    ];

    public function __construct(private readonly PermissionRegistry $registre)
    {
    }

    /**
     * @param  iterable<Audit>  $audits
     * @return array<int, ObjetNomme> identifiant d'audit => objet
     */
    public function pour(iterable $audits): array
    {
        $audits = collect($audits);
        $charges = $audits->groupBy('auditable_type')
            ->map(fn (Collection $g, string $type) => $this->charger($type, $g->pluck('auditable_id')->unique()->values()->all()));

        return $audits->mapWithKeys(fn (Audit $a) => [
            $a->id => $this->nommer($a, $charges[$a->auditable_type][$a->auditable_id] ?? null),
        ])->all();
    }

    /** @return Collection<int|string, Model> */
    private function charger(string $type, array $ids): Collection
    {
        if (! class_exists($type) || ! is_subclass_of($type, Model::class)) {
            return collect();
        }

        try {
            $requete = $type::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($type), true)) {
                $requete->withTrashed();
            }

            return $requete->with(self::AVEC[$type] ?? [])->whereKey($ids)->get()->keyBy(fn (Model $m) => $m->getKey());
        } catch (\Throwable $e) {
            // Une table absente (instance qui n'a pas le module) ne doit pas
            // faire tomber le journal : l'objet sera nomme par ses valeurs.
            Log::warning('Journal d\'audit : objets non charges', ['type' => $type, 'message' => $e->getMessage()]);

            return collect();
        }
    }

    private function nommer(Audit $audit, ?Model $objet): ObjetNomme
    {
        $type = (string) $audit->auditable_type;
        $libelle = EntityLabelHelper::for($type);

        if ($objet !== null) {
            [$designation, $nom, $reperes, $url] = $this->decrire($type, $objet);
            $supprime = method_exists($objet, 'trashed') && $objet->trashed();

            return new ObjetNomme($libelle, $designation, $nom, array_values(array_filter($reperes, 'filled')),
                $supprime ? null : $url, $supprime);
        }

        // Supprime pour de bon : ce que l'audit a garde de lui.
        $valeurs = array_merge(ValeursDAudit::de($audit->new_values), ValeursDAudit::de($audit->old_values));
        [$designation, $nom] = $this->depuisValeurs($type, $libelle, $valeurs);

        return new ObjetNomme($libelle, $designation, $nom, [], null, true);
    }

    /** @return array{0: string, 1: string, 2: list<?string>, 3: ?string} */
    private function decrire(string $type, Model $m): array
    {
        return match (true) {
            $m instanceof ESBTPPaiement => ['le paiement', $m->numero_recu ?: ($m->reference_paiement ?: 'du '.optional($m->date_paiement)->format('d/m/Y')),
                [self::personne($m->etudiant), self::argent($m->montant), \App\Enums\ModePaiement::fromLegacy((string) $m->mode_paiement)?->label() ?? $m->mode_paiement], self::lien('esbtp.paiements.show', $m)],
            $m instanceof ESBTPNote => ['la note de', self::personne($m->etudiant),
                [$m->matiere?->name, $m->evaluation?->titre, $m->note !== null ? self::nombre($m->note).' / '.self::nombre($m->evaluation?->bareme ?? 20) : null], null],
            $m instanceof ESBTPResultat => ['la moyenne de', self::personne($m->etudiant), [$m->matiere?->name, $m->classe?->name, $m->periode], null],
            $m instanceof ESBTPBulletin => ['le bulletin de', self::personne($m->etudiant), [$m->classe?->name, $m->periode, $m->anneeUniversitaire?->name], self::lien('esbtp.bulletins.show', $m)],
            $m instanceof ESBTPInscription => ['l\'inscription de', self::personne($m->etudiant), [$m->classe?->name, $m->anneeUniversitaire?->name, ChampsLisibles::statut($m->status)], self::lien('esbtp.inscriptions.show', $m)],
            $m instanceof ESBTPEtudiant => ['l\'étudiant', self::personne($m), [$m->matricule], self::lien('esbtp.etudiants.show', $m)],
            $m instanceof ESBTPFraisSubscription => ['les frais « '.($m->fraisCategory?->name ?? 'frais').' » de', self::personne($m->inscription?->etudiant),
                [self::argent($m->amount), $m->inscription?->anneeUniversitaire?->name], $m->inscription_id ? self::lien('esbtp.inscriptions.show', $m->inscription_id) : null],
            $m instanceof ESBTPFraisCategory => ['la catégorie de frais', (string) $m->name, [], null],
            $m instanceof ESBTPEvaluation => ['l\'évaluation', (string) $m->titre, [$m->matiere?->name, $m->classe?->name, optional($m->date_evaluation)->format('d/m/Y')], self::lien('esbtp.evaluations.show', $m)],
            $m instanceof ESBTPClasse => ['la classe', (string) $m->name, [$m->filiere?->name, $m->niveau?->name], self::lien('esbtp.classes.show', $m)],
            $m instanceof ESBTPMatiere => ['la matière', (string) $m->name, [$m->code], self::lien('esbtp.matieres.show', $m)],
            $m instanceof ESBTPCandidature => ['la demande d\'inscription de', $m->nomComplet(), [(string) ($m->referencePubliqueAffichee() ?? '')], DemandeDInscription::lien((int) $m->id, null)],
            $m instanceof ESBTPReinscriptionDemande => ['la demande de réinscription de', self::personne($m->etudiant), [$m->etudiant?->matricule, $m->anneeUniversitaire?->name], DemandeDInscription::lien(null, (int) $m->id)],
            $m instanceof ESBTPStudentAccessibilityProfile => ['le profil d\'accessibilité de', self::personne($m->etudiant), [], null],
            $m instanceof ESBTPFacture => ['la facture', (string) ($m->numero_facture ?: 'sans numéro'), [self::personne($m->etudiant), self::argent($m->montant_ttc)], null],
            $m instanceof User => ['le compte de', (string) $m->name, [$this->roleDe($m), $m->username], self::lien('esbtp.users.show', $m)],
            $m instanceof Setting => ['le réglage', (string) ($m->description ?: $m->key), [], null],
            str_ends_with($m::class, '\\Role') => ['le rôle', (string) ($this->registre->roleMeta((string) $m->name)['label'] ?? $m->name), [], null],
            str_ends_with($m::class, '\\Permission') => ['la permission', (string) ($this->registre->permissionMeta((string) $m->name)['label'] ?? $m->name), [], null],
            default => $this->generique(EntityLabelHelper::for($m::class), $m->getAttributes()),
        };
    }

    /** @return array{0: string, 1: string} */
    private function depuisValeurs(string $type, string $libelle, array $v): array
    {
        $personne = trim(($v['nom'] ?? '').' '.($v['prenoms'] ?? ''));

        return match ($type) {
            ESBTPPaiement::class => ['le paiement', ($v['numero_recu'] ?? null) ?: (($v['reference_paiement'] ?? null) ?: 'supprimé')],
            ESBTPEtudiant::class, ESBTPCandidature::class => [$type === ESBTPEtudiant::class ? 'l\'étudiant' : 'la demande d\'inscription de', $personne ?: 'supprimé'],
            default => array_slice($this->generique($libelle, $v), 0, 2),
        };
    }

    /**
     * Une sorte d'objet sans regle propre : son libelle, et le premier attribut
     * qui ressemble a un nom.
     *
     * @return array{0: string, 1: string, 2: list<?string>, 3: ?string}
     */
    private function generique(string $libelle, array $v): array
    {
        foreach (['name', 'nom', 'titre', 'title', 'libelle', 'label', 'code', 'reference'] as $cle) {
            if (filled($v[$cle] ?? null) && is_scalar($v[$cle])) {
                return ['« '.$libelle.' »', (string) $v[$cle], [], null];
            }
        }

        return ['un élément « '.$libelle.' »', '', [], null];
    }

    private function roleDe(User $u): ?string
    {
        $role = $u->relationLoaded('roles') ? $u->roles->first()?->name : null;

        return $role ? (string) ($this->registre->roleMeta($role)['label'] ?? $role) : null;
    }

    public static function personne(?Model $e): string
    {
        return $e ? trim(($e->nom ?? '').' '.($e->prenoms ?? '')) : 'un étudiant supprimé';
    }

    public static function argent(mixed $montant): ?string
    {
        return is_numeric($montant) ? number_format((float) $montant, 0, ',', ' ').' FCFA' : null;
    }

    public static function nombre(mixed $n): string
    {
        return is_numeric($n) ? rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',') : (string) $n;
    }

    private static function lien(string $route, mixed $parametre): ?string
    {
        return Route::has($route) ? route($route, $parametre) : null;
    }
}
