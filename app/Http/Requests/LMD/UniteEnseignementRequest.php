<?php

namespace App\Http\Requests\LMD;

use App\Enums\TypeUE;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\CodeDeMaquette;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validation du formulaire « Unité d'Enseignement » (création et modification).
 *
 * Pourquoi une seule classe pour les deux : les règles sont identiques à
 * l'unicité du code près, qui doit ignorer l'UE en cours de modification.
 *
 * Cette classe couvre TOUS les champs réellement postés par le formulaire
 * (resources/views/esbtp/lmd/ue/partials/_form.blade.php). Auparavant, seuls
 * name / code / description / credit / type_ue étaient validés : le semestre,
 * la filière, le niveau, le parcours, l'ordre et la liste des ECUE étaient
 * silencieusement jetés, et l'UE créée n'était rattachée à rien — donc absente
 * des calculs, du bulletin et du procès-verbal.
 */
class UniteEnseignementRequest extends FormRequest
{
    /**
     * L'accès est déjà filtré par les middlewares du groupe de routes
     * (« permission:module.lmd.access » + rôles habilités). Refaire un contrôle
     * de permission ici, avec un nom de permission différent, retirerait l'accès
     * à des utilisateurs qui l'ont aujourd'hui.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** La cle interne calculee une fois, apres validation (voir cle()). */
    private ?string $cle = null;

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // L'unicite se controle sur la CLE, pas sur le code saisi : une UE
            // propre a un parcours imprime le code d'une autre (CodeDeMaquette).
            // Le tilde est reserve a cette cle, jamais saisi.
            'code' => ['nullable', 'string', 'max:50', CodeDeMaquette::REGLE_SAISIE],
            // « Cette UE est propre a ce parcours » : meme code qu'une UE d'un
            // autre parcours, mais un autre enseignement. Choix de l'ecole.
            'propre_au_parcours' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'credit' => ['nullable', 'integer', 'min:0'],
            'type_ue' => ['required', Rule::in(TypeUE::values())],

            // Le semestre porte le rattachement au parcours (colonne NOT NULL du
            // pivot esbtp_lmd_parcours_ue) : sans lui, le lien serait impossible.
            'semestre' => ['nullable', 'integer', 'between:1,10', 'required_with:parcours_id'],
            'parcours_id' => ['nullable', 'integer', 'exists:esbtp_lmd_parcours,id'],
            'filiere_id' => ['nullable', 'integer', 'exists:esbtp_filieres,id'],
            'niveau_id' => ['nullable', 'integer', 'exists:esbtp_niveau_etudes,id'],
            'ordre' => ['nullable', 'integer', 'min:0'],

            'ecues' => ['nullable', 'array'],
            'ecues.*.name' => ['required', 'string', 'max:255'],
            // Obligatoire : `esbtp_matieres.code` est NOT NULL et porte un index
            // unique. Un ECUE sans code faisait echouer l'enregistrement au niveau SQL,
            // et comme store()/update() ecrivent dans une transaction, le rollback
            // annulait aussi l'UE : l'utilisateur perdait toute sa saisie sans message.
            'ecues.*.code' => ['required', 'string', 'max:50', 'distinct:ignore_case', CodeDeMaquette::REGLE_SAISIE],
            'ecues.*.coefficient_ecue' => ['nullable', 'numeric', 'min:0'],
            'ecues.*.credit_ecue' => ['nullable', 'integer', 'min:0'],
            'ecues.*.ordre_bulletin' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'intitulé',
            'code' => 'code',
            'propre_au_parcours' => 'UE propre à ce parcours',
            'credit' => 'crédits',
            'type_ue' => 'type d\'UE',
            'semestre' => 'semestre',
            'parcours_id' => 'parcours',
            'filiere_id' => 'filière',
            'niveau_id' => 'niveau',
            'ordre' => 'ordre sur le bulletin',
        ];
    }

    public function messages(): array
    {
        return [
            'code.not_regex' => 'Le caractère « ~ » est réservé : retirez-le du code.',
            'ecues.*.code.not_regex' => 'Le caractère « ~ » est réservé : retirez-le du code.',
            'semestre.required_with' => 'Choisissez un semestre : c\'est lui qui rattache l\'unité d\'enseignement au parcours.',
            'ecues.*.name.required' => 'Chaque élément constitutif doit avoir un intitulé.',
            'ecues.*.code.required' => 'Chaque élément constitutif doit avoir un code : il l\'identifie de façon unique dans l\'établissement.',
            'ecues.*.code.distinct' => 'Deux éléments constitutifs ne peuvent pas porter le même code.',
        ];
    }

    /**
     * Un ECUE ne peut pas apporter plus de crédits que n'en porte son UE.
     * Même règle que l'ajout d'un ECUE isolé (checkCreditOverflow du contrôleur),
     * appliquée ici à la somme de la liste soumise.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $creditUe = $this->input('credit');
            if ($creditUe === null || $creditUe === '') {
                return;
            }

            $sommeEcues = 0;
            foreach ((array) $this->input('ecues', []) as $ecue) {
                $sommeEcues += (int) ($ecue['credit_ecue'] ?? 0);
            }

            if ($sommeEcues > (int) $creditUe) {
                $validator->errors()->add(
                    'credit',
                    "La somme des crédits des éléments constitutifs ({$sommeEcues}) dépasse les crédits de l'unité d'enseignement ({$creditUe})."
                );
            }
        });

        $validator->after(fn (Validator $validator) => $this->refuserCodesDeMatieresBts($validator));
        $validator->after(fn (Validator $validator) => $this->controlerLeCode($validator));
    }

    /**
     * La cle interne de l'UE : le code saisi, suffixe du parcours si l'UE lui
     * est propre. C'est elle que le controleur enregistre.
     */
    public function cle(): ?string
    {
        return $this->cle;
    }

    /** Les donnees validees, le code remplace par sa cle interne. */
    public function donneesAvecLaCle(): array
    {
        $donnees = $this->validated();
        if (array_key_exists('code', $donnees) && $this->cle !== null) {
            $donnees['code'] = $this->cle;
        }

        return $donnees;
    }

    /**
     * Le code n'est unique que DANS un parcours (CodeDeMaquette).
     *
     * - Une UE deja propre a un parcours garde son suffixe quand on la modifie :
     *   le formulaire montre le code imprime, on lui rend sa cle.
     * - A la creation, « propre a ce parcours » derive la cle du parcours choisi.
     * - Sinon la cle est le code saisi, unique dans l'ecole comme avant, et le
     *   refus dit comment obtenir une UE propre a un parcours.
     */
    private function controlerLeCode(Validator $validator): void
    {
        if ($validator->errors()->has('code')) {
            return;
        }

        $saisi = trim((string) $this->input('code', ''));
        if ($saisi === '') {
            return;
        }

        $ue = $this->route('ue');
        $ue = $ue instanceof ESBTPUniteEnseignement ? $ue : ($ue ? ESBTPUniteEnseignement::find($ue) : null);
        $maquette = app(CodeDeMaquette::class);
        $parcours = $this->filled('parcours_id') ? ESBTPLMDParcours::find($this->input('parcours_id')) : null;

        if ($ue && CodeDeMaquette::suffixe($ue->code) !== null) {
            $this->cle = $saisi . CodeDeMaquette::SEPARATEUR . \Illuminate\Support\Str::after($ue->code, CodeDeMaquette::SEPARATEUR);
        } elseif (! $ue && $this->boolean('propre_au_parcours')) {
            if (! $parcours) {
                $validator->errors()->add('parcours_id', 'Choisissez le parcours auquel cette UE est propre.');

                return;
            }
            $this->cle = $maquette->cleUnitePropre($saisi, $parcours);
        } else {
            $this->cle = $saisi;
        }

        $prise = ESBTPUniteEnseignement::withTrashed()
            ->where('code', $this->cle)
            ->when($ue, fn ($q) => $q->where('id', '!=', $ue->id))
            ->first(['id', 'name']);
        if ($prise) {
            $validator->errors()->add('code', $this->boolean('propre_au_parcours')
                ? sprintf('Ce parcours a déjà son UE propre « %s » sous ce code : modifiez-la plutôt que d\'en créer une seconde.', $prise->name)
                : sprintf(
                    'Ce code est déjà celui de l\'UE « %s ». S\'il s\'agit d\'une autre UE, propre à un parcours, cochez « UE propre à ce parcours » et choisissez le parcours.',
                    $prise->name
                ));

            return;
        }

        $parcoursIds = $parcours ? [(int) $parcours->id] : [];
        if ($ue) {
            $parcoursIds = array_merge($parcoursIds, $ue->parcoursMultiple()->pluck('esbtp_lmd_parcours.id')->map(fn ($id) => (int) $id)->all());
        }
        foreach (array_unique($parcoursIds) as $parcoursId) {
            if ($deja = $maquette->autreUniteDuParcours($parcoursId, $this->cle, $ue?->id)) {
                $validator->errors()->add('code', sprintf(
                    'Ce parcours imprime déjà le code %s pour l\'UE « %s ». Un relevé ne peut pas porter deux fois le même code.',
                    $saisi,
                    $deja->name
                ));

                return;
            }
        }
    }

    /**
     * Refuse un code d'element constitutif deja porte par une matiere du BTS.
     *
     * `esbtp_matieres` est partagee par les deux cursus et son `code` porte un
     * index unique global. La reutilisation par code, cote controleur, ecrirait
     * `unite_enseignement_id` sur la matiere trouvee : une matiere BTS
     * deviendrait un ECUE et disparaitrait de tous les selecteurs BTS, qui
     * filtrent precisement sur `unite_enseignement_id IS NULL` — ses evaluations
     * et ses notes resteraient en base mais deviendraient inatteignables.
     *
     * On nomme donc le code en conflit et on refuse la saisie, plutot que
     * d'absorber la matiere en silence. Une matiere deja rattachee a une UE
     * (colonne ou pivot `esbtp_ue_matiere`, le partage d'un ECUE entre deux UE
     * ne renseignant que le pivot) reste reutilisable : c'est le cas nominal.
     */
    private function refuserCodesDeMatieresBts(Validator $validator): void
    {
        $codes = [];
        foreach ((array) $this->input('ecues', []) as $index => $ecue) {
            $code = trim((string) ($ecue['code'] ?? ''));
            if ($code !== '') {
                $codes[$index] = $code;
            }
        }

        if ($codes === []) {
            return;
        }

        $matieres = ESBTPMatiere::withTrashed()
            ->whereIn('code', array_values($codes))
            ->get(['id', 'code', 'name', 'unite_enseignement_id'])
            // Cle insensible a la casse : la collation MySQL l'est aussi, « abc »
            // saisi retrouve donc bien la matiere enregistree « ABC ».
            ->keyBy(fn (ESBTPMatiere $matiere): string => mb_strtoupper((string) $matiere->code));

        if ($matieres->isEmpty()) {
            return;
        }

        // Le partage d'un ECUE entre deux UE ne renseigne que le pivot : une
        // matiere dont la colonne est nulle peut donc etre un ECUE malgre tout.
        $idsRattachesParPivot = DB::table('esbtp_ue_matiere')
            ->whereIn('matiere_id', $matieres->pluck('id')->all())
            ->pluck('matiere_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        foreach ($codes as $index => $code) {
            $matiere = $matieres->get(mb_strtoupper($code));
            if (! $matiere) {
                continue;
            }

            $estDejaEcue = $matiere->unite_enseignement_id !== null
                || in_array((int) $matiere->id, $idsRattachesParPivot, true);

            if ($estDejaEcue) {
                continue;
            }

            $validator->errors()->add(
                "ecues.{$index}.code",
                sprintf(
                    'Le code « %s » est déjà celui d\'une matière du cursus BTS (« %s »). Choisissez un autre code : réutiliser celui-ci retirerait cette matière des écrans BTS.',
                    $code,
                    $matiere->name
                )
            );
        }
    }
}
