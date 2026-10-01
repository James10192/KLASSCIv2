<?php

namespace App\Domain\Assistant\Actions\Bulletins;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPResultat;
use App\Services\BulletinService;
use App\Services\ESBTP\BulletinConsistencyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Prépare le retrait des moyennes qui ne reposent sur aucune note. Cette action
 * est volontairement aussi stricte que l'écran Bulletins : une seule matière,
 * une classe, une année et une période, puis une relecture complète au clic.
 *
 * Une matière retirée de la maquette actuelle reste néanmoins une cible valide
 * lorsqu'une ancienne moyenne de cette matière est encore enregistrée. C'est
 * précisément le cas que cette action répare : l'identifiant transmis par la
 * page désigne l'historique, tandis que la requête finale borne toujours la
 * suppression à la classe, à l'année, à la période et à l'absence de note.
 */
class SupprimerMoyennesSansNote extends ActionAgent
{
    public function __construct(
        private BulletinService $bulletins,
        private BulletinConsistencyService $consistency,
    ) {
    }

    public function cle(): string
    {
        return 'supprimer_moyennes_sans_note';
    }

    public function libelle(): string
    {
        return 'Préparation du nettoyage des moyennes sans note…';
    }

    public function description(): string
    {
        return "PROPOSE de supprimer les moyennes d'UNE matière qui n'a réellement aucune note sur une période. "
            . "Utilise les identifiants de la page ou des outils lorsqu'ils sont disponibles ; sinon passe les libellés exacts. "
            . "Une matière retirée de la maquette peut être concernée : si la page donne son identifiant, utilise-le ; le serveur vérifie toujours la classe, l'année, la période et l'absence totale de note. "
            . "Ne supprime rien : le serveur montre chaque étudiant, puis l'utilisateur valide. "
            . "Si une donnée est absente ou ambiguë, demande-la. N'utilise jamais cette action pour effacer une note ou une moyenne qui a une note.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'etudiant_id' => ['type' => 'integer', 'description' => "Identifiant de l'étudiant affiché. Si la demande vient de sa fiche, il borne impérativement le nettoyage à cet étudiant."],
                'classe_id' => ['type' => 'integer', 'description' => 'Identifiant de la classe, si la page ou un outil le donne.'],
                'classe' => ['type' => 'string', 'description' => 'Code ou libellé EXACT de la classe, si son identifiant est inconnu.'],
                'annee_universitaire_id' => ['type' => 'integer', 'description' => "Identifiant de l'année universitaire, si disponible."],
                'annee' => ['type' => 'string', 'description' => "Libellé EXACT de l'année universitaire. Omettre seulement pour l'année courante."],
                'matiere_id' => ['type' => 'integer', 'description' => 'Identifiant de la matière, si disponible.'],
                'matiere' => ['type' => 'string', 'description' => 'Libellé EXACT de la matière, si son identifiant est inconnu.'],
                'periode' => ['type' => 'string', 'description' => "Période exacte : semestre1 / semestre2 (ou S1 / S2 si affiché ainsi)."],
            ],
            'required' => ['periode'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        if (! $user->can('bulletins.delete')) {
            return $this->manque("Cet utilisateur n'a pas le droit de supprimer des moyennes de bulletin.");
        }

        [$classe, $erreurClasse] = $this->classe($args);
        [$annee, $erreurAnnee] = $this->annee($args);
        [$matiere, $erreurMatiere] = $this->matiere($args, $classe);
        $periode = $this->periode($args['periode'] ?? null);
        $manques = array_values(array_filter([$erreurClasse, $erreurAnnee, $erreurMatiere, $periode === null ? 'Période inconnue : indiquez S1 ou S2.' : null]));
        if ($manques !== []) {
            return new Proposition(titre: 'Nettoyage des moyennes sans note', resume: '', manques: $manques);
        }

        $etudiantId = (int) ($args['etudiant_id'] ?? 0);
        $lignes = $this->requete($classe->id, $annee->id, $matiere->id, $periode)
            ->when($etudiantId > 0, fn (Builder $q) => $q->where('etudiant_id', $etudiantId))
            ->with('etudiant:id,nom,prenoms,matricule')
            ->orderBy('etudiant_id')
            ->get();
        if ($lignes->isEmpty()) {
            return $this->manque("Aucune moyenne sans note à supprimer pour {$matiere->name}, {$this->libellePeriode($periode)}, {$classe->name}.");
        }

        $bulletinsOfficiels = $this->bulletinsOfficiels(
            $lignes->pluck('etudiant_id')->all(),
            (int) $classe->id,
            (int) $annee->id,
            $periode,
        );
        if ($bulletinsOfficiels->isNotEmpty() && ! $user->can('bulletins.edit')) {
            return $this->manque(
                'Un bulletin officiel existe déjà pour cette moyenne. La correction doit aussi régénérer son snapshot ; '
                . 'elle nécessite le droit de modifier les bulletins.'
            );
        }

        $etat = $lignes->map(fn (ESBTPResultat $ligne) => [
            'id' => (int) $ligne->id,
            'moyenne' => (string) $ligne->moyenne,
            'updated_at' => $ligne->updated_at?->toIso8601String(),
        ])->values()->all();

        return new Proposition(
            titre: 'Supprimer les moyennes sans note',
            resume: $lignes->count() . ' moyenne(s) sans aucune note seront retirées de ' . $matiere->name . ' (' . $this->libellePeriode($periode) . ')' . ($etudiantId > 0 ? ', uniquement pour l’étudiant affiché.' : '.'),
            tableau: [
                'colonnes' => ['Étudiant', 'Matricule', 'Matière', 'Période', 'Moyenne à retirer'],
                'lignes' => $lignes->map(fn (ESBTPResultat $ligne) => [
                    trim(($ligne->etudiant?->nom ?? '') . ' ' . ($ligne->etudiant?->prenoms ?? '')) ?: 'Étudiant #' . $ligne->etudiant_id,
                    (string) ($ligne->etudiant?->matricule ?? '—'),
                    (string) $matiere->name,
                    $this->libellePeriode($periode),
                    number_format((float) $ligne->moyenne, 2, ',', ' ') . '/20',
                ])->all(),
            ],
            avertissements: array_values(array_filter([
                'Seules les moyennes sans aucune note sont concernées. Si une note revient avant la validation, la proposition expirera et rien ne sera supprimé.',
                $bulletinsOfficiels->isNotEmpty()
                    ? $bulletinsOfficiels->count() . ' bulletin(s) officiel(s) seront régénérés dans la même validation pour retirer aussi la moyenne du snapshot et du PDF.'
                    : null,
            ])),
            donnees: [
                'etudiant_id' => $etudiantId > 0 ? $etudiantId : null,
                'classe_id' => (int) $classe->id,
                'annee_universitaire_id' => (int) $annee->id,
                'matiere_id' => (int) $matiere->id,
                'periode' => $periode,
            ],
            etat: ['lignes' => $etat],
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        if (! $user->can('bulletins.delete')) {
            throw new PropositionPerimee("Vous n'avez plus le droit de supprimer ces moyennes.");
        }

        $donnees = $proposition->donnees;
        $lignes = $this->requete((int) $donnees['classe_id'], (int) $donnees['annee_universitaire_id'], (int) $donnees['matiere_id'], (string) $donnees['periode'])
            ->when((int) ($donnees['etudiant_id'] ?? 0) > 0, fn (Builder $q) => $q->where('etudiant_id', (int) $donnees['etudiant_id']))
            ->orderBy('id')->get();
        $attendues = collect($proposition->etat['lignes'] ?? [])->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $actuelles = $lignes->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        if ($actuelles !== $attendues) {
            throw new PropositionPerimee('Les moyennes sans note ont changé depuis la proposition.');
        }

        $bulletinsOfficiels = $this->bulletinsOfficiels(
            $lignes->pluck('etudiant_id')->all(),
            (int) $donnees['classe_id'],
            (int) $donnees['annee_universitaire_id'],
            (string) $donnees['periode'],
        );
        if ($bulletinsOfficiels->isNotEmpty() && ! $user->can('bulletins.edit')) {
            throw new PropositionPerimee('Un bulletin officiel doit aussi être régénéré, mais vous n’avez plus le droit de le modifier.');
        }

        // La source et son snapshot officiel sont corrigés ensemble. Si une
        // régénération échoue (configuration incomplète, par exemple), toute
        // l'opération est annulée : aucun PDF officiel ne reste en décalage.
        DB::transaction(function () use ($lignes, $bulletinsOfficiels): void {
            foreach ($lignes as $ligne) {
                if (! $ligne->delete()) {
                    throw new PropositionPerimee('Une moyenne ne peut plus être supprimée.');
                }
            }

            foreach ($bulletinsOfficiels as $bulletin) {
                $this->consistency->regenerateOfficialBulletin(
                    (int) $bulletin->etudiant_id,
                    (int) $bulletin->classe_id,
                    (int) $bulletin->annee_universitaire_id,
                    (string) $bulletin->periode,
                );
            }
        });

        $regeneres = $bulletinsOfficiels->count();

        return [
            'message' => count($actuelles) . ' moyenne(s) sans note supprimée(s).'
                . ($regeneres > 0 ? ' ' . $regeneres . ' bulletin(s) officiel(s) régénéré(s).' : ''),
            'lien' => route('esbtp.bulletins.select', [], false),
            'model_type' => ESBTPResultat::class,
            'model_id' => $actuelles[0] ?? null,
            'details' => ['supprimees' => count($actuelles), 'bulletins_regeneres' => $regeneres],
        ];
    }

    /** @return array{0: ?ESBTPClasse, 1: ?string} */
    private function classe(array $args): array
    {
        if (($id = (int) ($args['classe_id'] ?? 0)) > 0) {
            return [ESBTPClasse::find($id), ESBTPClasse::find($id) ? null : 'Classe introuvable.'];
        }
        $libelle = trim((string) ($args['classe'] ?? ''));
        if ($libelle === '') return [null, 'Indiquez la classe concernée.'];
        $trouvees = ESBTPClasse::query()
            ->where(fn (Builder $q) => $q->whereRaw('LOWER(name) = ?', [mb_strtolower($libelle)])
                ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($libelle)]))
            ->get();
        return $trouvees->count() === 1 ? [$trouvees->first(), null] : [null, $trouvees->isEmpty() ? "Classe introuvable : {$libelle}." : "Plusieurs classes correspondent à {$libelle} : indiquez son code ou son identifiant."];
    }

    /** @return array{0: ?ESBTPAnneeUniversitaire, 1: ?string} */
    private function annee(array $args): array
    {
        if (($id = (int) ($args['annee_universitaire_id'] ?? 0)) > 0) {
            return [ESBTPAnneeUniversitaire::find($id), ESBTPAnneeUniversitaire::find($id) ? null : 'Année universitaire introuvable.'];
        }
        $libelle = trim((string) ($args['annee'] ?? ''));
        if ($libelle === '') {
            $courante = ESBTPAnneeUniversitaire::anneeCourante();
            return [$courante, $courante ? null : "Aucune année universitaire courante n'est définie."];
        }
        $trouvees = ESBTPAnneeUniversitaire::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($libelle)])->get();
        return $trouvees->count() === 1 ? [$trouvees->first(), null] : [null, $trouvees->isEmpty() ? "Année universitaire introuvable : {$libelle}." : "Plusieurs années correspondent à {$libelle} : indiquez son identifiant."];
    }

    /** @return array{0: ?ESBTPMatiere, 1: ?string} */
    private function matiere(array $args, ?ESBTPClasse $classe): array
    {
        if (($id = (int) ($args['matiere_id'] ?? 0)) > 0) {
            // La maquette est vivante : une matière peut avoir été retirée ou
            // déplacée après la génération d'un bulletin. L'id précis fourni
            // par la page est donc une référence historique légitime. La
            // requête sansNoteSurLaPeriode() reste la garde autoritaire :
            // aucun résultat hors de ce périmètre ne pourra être supprimé.
            $matiere = ESBTPMatiere::withTrashed()->find($id);
            return [$matiere, $matiere ? null : 'Matière introuvable.'];
        }
        if (! $classe) return [null, null];
        $libelle = trim((string) ($args['matiere'] ?? ''));
        if ($libelle === '') return [null, 'Indiquez la matière concernée.'];
        $trouvees = $classe->matieres()
            ->where(fn (Builder $q) => $q->whereRaw('LOWER(name) = ?', [mb_strtolower($libelle)])
                ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($libelle)]))
            ->get();
        return $trouvees->count() === 1 ? [$trouvees->first(), null] : [null, $trouvees->isEmpty() ? "Matière introuvable dans cette classe : {$libelle}." : "Plusieurs matières correspondent à {$libelle} : indiquez son identifiant."];
    }

    /** @return \Illuminate\Support\Collection<int, ESBTPBulletin> */
    private function bulletinsOfficiels(array $etudiantIds, int $classeId, int $anneeId, string $periode): \Illuminate\Support\Collection
    {
        return ESBTPBulletin::query()
            ->whereIn('etudiant_id', array_values(array_unique(array_map('intval', $etudiantIds))))
            ->where('classe_id', $classeId)
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('periode', $this->bulletins->periodeAliases($periode))
            ->get();
    }

    private function periode(mixed $value): ?string
    {
        $normalisee = Str::lower(trim((string) $value));
        return match ($normalisee) {
            's1', '1', 'semestre 1', 'semestre1' => 'semestre1',
            's2', '2', 'semestre 2', 'semestre2' => 'semestre2',
            default => null,
        };
    }

    private function libellePeriode(string $periode): string
    {
        return $periode === 'semestre1' ? 'Semestre 1' : 'Semestre 2';
    }

    private function requete(int $classeId, int $anneeId, int $matiereId, string $periode): Builder
    {
        return ESBTPResultat::query()
            ->sansNoteSurLaPeriode($classeId, $anneeId, $this->bulletins->periodeAliases($periode))
            ->where('matiere_id', $matiereId);
    }
}
