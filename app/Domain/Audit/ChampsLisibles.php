<?php

namespace App\Domain\Audit;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPNiveauEtude;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OwenIt\Auditing\Models\Audit;

/**
 * Ce qu'un audit a change, dit avec les mots de l'ecran.
 *
 * « Classe : 1A BTS Bâtiment → 2BTS GBAT E », pas « classe_id : 12 → 31 ».
 * Les champs techniques (horodatages, jetons, auteur deja nomme dans la
 * phrase) sont tus. Les noms lies sont lus par lot, une requete par table,
 * pour toutes les lignes d'une page.
 */
class ChampsLisibles
{
    /** Ce qui ne dit rien a une personne, ou qui est deja dans la phrase. */
    private const TUS = [
        'id', 'created_at', 'updated_at', 'deleted_at', 'archived_at', 'remember_token', 'password',
        'created_by', 'updated_by', 'createur_id', 'two_factor_secret', 'two_factor_recovery_codes',
        'api_token', 'token_saisie_externe', 'token_expire_at', 'note_vivante', 'metadata', 'guard_name',
    ];

    private const LIBELLES = [
        'nom' => 'Nom', 'prenoms' => 'Prénoms', 'name' => 'Nom', 'titre' => 'Titre', 'description' => 'Description',
        'montant' => 'Montant', 'amount' => 'Montant', 'montant_ttc' => 'Montant TTC', 'default_amount' => 'Montant par défaut',
        'status' => 'Statut', 'statut' => 'Statut', 'workflow_step' => 'Étape', 'affectation_status' => 'Affectation',
        'note' => 'Note', 'moyenne' => 'Moyenne', 'moyenne_generale' => 'Moyenne générale', 'rang' => 'Rang',
        'mention' => 'Mention', 'decision_conseil' => 'Décision du conseil', 'appreciation' => 'Appréciation',
        'coefficient' => 'Coefficient', 'bareme' => 'Barème', 'periode' => 'Période', 'semestre' => 'Semestre',
        'mode_paiement' => 'Mode de paiement', 'date_paiement' => 'Date du paiement', 'numero_recu' => 'Reçu',
        'reference_paiement' => 'Référence', 'motif' => 'Motif', 'motif_rejet' => 'Motif du rejet',
        'motif_suppression' => 'Motif de suppression', 'commentaire' => 'Commentaire', 'is_published' => 'Publié',
        'is_active' => 'Actif', 'is_absent' => 'Absent', 'places_totales' => 'Capacité', 'telephone' => 'Téléphone',
        'phone' => 'Téléphone', 'email' => 'E-mail', 'matricule' => 'Matricule', 'date_naissance' => 'Date de naissance',
        'lieu_naissance' => 'Lieu de naissance', 'sexe' => 'Sexe', 'username' => 'Nom d\'utilisateur',
        'must_change_password' => 'Mot de passe à changer', 'value' => 'Valeur', 'key' => 'Réglage',
        'date_evaluation' => 'Date', 'traite_at' => 'Traitée le', 'date_validation' => 'Validé le',
        'type_inscription' => 'Type d\'inscription', 'date_inscription' => 'Date d\'inscription', 'code' => 'Code',
    ];

    /** Cle etrangere => [table lue, libelle du champ]. */
    private const LIENS = [
        'classe_id' => [ESBTPClasse::class, 'Classe'],
        'classe_souhaitee_id' => [ESBTPClasse::class, 'Classe souhaitée'],
        'matiere_id' => [ESBTPMatiere::class, 'Matière'],
        'filiere_id' => [ESBTPFiliere::class, 'Filière'],
        'niveau_id' => [ESBTPNiveauEtude::class, 'Niveau'],
        'niveau_etude_id' => [ESBTPNiveauEtude::class, 'Niveau'],
        'annee_universitaire_id' => [ESBTPAnneeUniversitaire::class, 'Année'],
        'etudiant_id' => [ESBTPEtudiant::class, 'Étudiant'],
        'frais_category_id' => [ESBTPFraisCategory::class, 'Catégorie de frais'],
        'evaluation_id' => [ESBTPEvaluation::class, 'Évaluation'],
        'validateur_id' => [User::class, 'Validé par'],
        'validated_by' => [User::class, 'Validé par'],
        'deleted_by' => [User::class, 'Supprimé par'],
        'traite_par' => [User::class, 'Traitée par'],
        'enseignant_id' => [User::class, 'Enseignant'],
        'submitted_by' => [User::class, 'Saisie par'],
        'inscription_id' => [ESBTPInscription::class, 'Inscription'],
    ];

    private const STATUTS = [
        'en_attente' => 'En attente', 'validé' => 'Validé', 'valide' => 'Validé', 'rejeté' => 'Rejeté',
        'rejete' => 'Rejeté', 'annule' => 'Annulé', 'annulé' => 'Annulé', 'active' => 'Active',
        'inactive' => 'Inactive', 'acceptee' => 'Acceptée', 'convertie' => 'Convertie', 'rejetee' => 'Rejetée',
        'etudiant_cree' => 'Étudiant créé', 'affecté' => 'Affecté', 'non_affecté' => 'Non affecté',
    ];

    /** « en_attente » dit comme on le lit : « En attente ». */
    public static function statut(?string $statut): ?string
    {
        return $statut === null || $statut === '' ? null : (self::STATUTS[$statut] ?? Str::ucfirst(str_replace('_', ' ', $statut)));
    }

    private const MONETAIRES = ['amount', 'montant', 'prix', 'total', 'cout', 'frais', 'salaire', 'taux_horaire', 'reliquat', 'reduction', 'bourse'];

    /** @var array<string, Collection<int|string, string>> table => id => nom */
    private array $noms = [];

    /** @param  iterable<Audit>  $audits  les lignes dont on lira les changements */
    public function __construct(iterable $audits = [])
    {
        $ids = [];
        foreach ($audits as $audit) {
            foreach ([ValeursDAudit::de($audit->old_values), ValeursDAudit::de($audit->new_values)] as $valeurs) {
                foreach (array_intersect_key($valeurs, self::LIENS) as $cle => $id) {
                    if (is_numeric($id)) {
                        $ids[self::LIENS[$cle][0]][] = (int) $id;
                    }
                }
            }
        }

        foreach ($ids as $table => $liste) {
            $this->noms[$table] = $this->lireNoms($table, array_values(array_unique($liste)));
        }
    }

    /**
     * Les changements d'un audit, champ par champ. Une creation dit ce qui a
     * ete pose, une suppression ce qui existait.
     *
     * @return list<array{cle: string, champ: string, avant: string, apres: string}>
     */
    public function changements(Audit $audit): array
    {
        $avant = ValeursDAudit::de($audit->old_values);
        $apres = ValeursDAudit::de($audit->new_values);
        $lignes = [];

        foreach (array_unique(array_merge(array_keys($avant), array_keys($apres))) as $cle) {
            if ($this->tu($cle)) {
                continue;
            }
            $a = $avant[$cle] ?? null;
            $n = $apres[$cle] ?? null;
            if (self::vide($a) && self::vide($n) || $audit->event === 'updated' && $a == $n) {
                continue;
            }
            $lignes[] = ['cle' => $cle, 'champ' => $this->libelle($cle), 'avant' => $this->valeur($cle, $a), 'apres' => $this->valeur($cle, $n)];
        }

        return $lignes;
    }

    /**
     * Le changement a montrer dans la ligne du journal : celui qui compte le
     * plus, en une etiquette courte. Une creation ou une suppression n'en a
     * pas — la phrase et les reperes disent deja l'essentiel.
     *
     * @param  list<string>  $principaux  champs qui n'ont pas besoin d'etre nommes (la note d'une note)
     */
    public function principal(Audit $audit, array $principaux = []): ?string
    {
        if ($audit->event !== 'updated') {
            return null;
        }
        $lignes = $this->changements($audit);
        if ($lignes === []) {
            return null;
        }
        usort($lignes, fn ($x, $y) => (int) in_array($y['cle'], $principaux, true) <=> (int) in_array($x['cle'], $principaux, true));
        $l = $lignes[0];
        $texte = (in_array($l['cle'], $principaux, true) ? '' : $l['champ'].' ').Str::limit($l['avant'], 40).' → '.Str::limit($l['apres'], 40);
        $autres = count($lignes) - 1;

        return $texte.($autres > 0 ? ' · +'.$autres : '');
    }

    public function libelle(string $cle): string
    {
        return self::LIBELLES[$cle] ?? self::LIENS[$cle][1] ?? Str::ucfirst(str_replace('_', ' ', preg_replace('/_id$/', '', $cle)));
    }

    public function valeur(string $cle, mixed $v): string
    {
        if (self::vide($v)) {
            return '—';
        }
        if (isset(self::LIENS[$cle]) && is_numeric($v)) {
            return $this->noms[self::LIENS[$cle][0]][(int) $v] ?? 'élément supprimé';
        }
        if (is_bool($v) || (preg_match('/^(is_|must_|has_|est_)/', $cle) && in_array((string) $v, ['0', '1'], true))) {
            return filter_var($v, FILTER_VALIDATE_BOOLEAN) ? 'Oui' : 'Non';
        }
        if (is_array($v)) {
            return Str::limit(json_encode($v, JSON_UNESCAPED_UNICODE), 120);
        }
        if (is_numeric($v) && ! str_ends_with($cle, '_id') && Str::contains($cle, self::MONETAIRES)) {
            return number_format((float) $v, 0, ',', ' ').' FCFA';
        }
        if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T](\d{2}:\d{2}))?/', $v, $m)) {
            $date = \Carbon\Carbon::parse($v);

            return isset($m[1]) && $m[1] !== '00:00' ? $date->format('d/m/Y à H:i') : $date->format('d/m/Y');
        }
        if ($cle === 'mode_paiement' && ($mode = \App\Enums\ModePaiement::fromLegacy((string) $v))) {
            return $mode->label();
        }
        if (in_array($cle, ['status', 'statut', 'workflow_step', 'affectation_status', 'submission_status'], true)) {
            return (string) self::statut((string) $v);
        }
        if (is_numeric($v) && is_string($v) && str_contains($v, '.')) {
            return NommageDesObjets::nombre($v);
        }

        return (string) $v;
    }

    private function tu(string $cle): bool
    {
        return in_array($cle, self::TUS, true) || str_ends_with($cle, '_token');
    }

    private static function vide(mixed $v): bool
    {
        return $v === null || $v === '' || $v === [];
    }

    /** @return Collection<int, string> */
    private function lireNoms(string $table, array $ids): Collection
    {
        try {
            $requete = $table::query()->whereKey($ids);
            if (method_exists($table, 'bootSoftDeletes')) {
                $requete->withTrashed();
            }

            return match ($table) {
                ESBTPEtudiant::class => $requete->get(['id', 'nom', 'prenoms'])->mapWithKeys(fn ($e) => [$e->id => NommageDesObjets::personne($e)]),
                ESBTPEvaluation::class => $requete->pluck('titre', 'id'),
                // Une inscription se reconnait a sa classe et a son annee.
                ESBTPInscription::class => $requete->with(['classe:id,name', 'anneeUniversitaire:id,name'])->get(['id', 'classe_id', 'annee_universitaire_id'])
                    ->mapWithKeys(fn ($i) => [$i->id => implode(' · ', array_filter([$i->classe?->name ?? 'Sans classe', $i->anneeUniversitaire?->name]))]),
                default => $requete->pluck('name', 'id'),
            };
        } catch (\Throwable $e) {
            // Une table absente sur cette instance : les valeurs restent lisibles
            // (« élément supprimé »), mais on le dit au journal.
            \Illuminate\Support\Facades\Log::warning('Journal d\'audit : noms illisibles pour '.$table, ['erreur' => $e->getMessage()]);

            return collect();
        }
    }
}
