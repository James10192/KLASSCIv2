<?php

namespace App\Http\Requests\PiecesDossier;

use App\Enums\AppartenancePieceDossier;
use App\Enums\EcheancePieceDossier;
use App\Enums\FormePieceDossier;
use App\Services\CataloguePiecesDossier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPieceDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La route porte déjà la permission. La dupliquer ici ne protégerait
        // rien de plus et finirait par diverger d'elle.
        return true;
    }

    public function rules(): array
    {
        // Le plafond est un réglage d'école (pieces_dossier.exemplaires_max),
        // pas une constante : voir CataloguePiecesDossier::exemplairesMax().
        $exemplairesMax = app(CataloguePiecesDossier::class)->exemplairesMax();

        return [
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_obligatoire' => ['required', 'boolean'],
            'forme_attendue' => ['required', Rule::in(FormePieceDossier::values())],
            'exemplaires_par_inscription' => ['required', 'integer', 'min:1', 'max:' . $exemplairesMax],
            'echeance' => ['required', Rule::in(EcheancePieceDossier::values())],
            'appartenance' => ['required', Rule::in(AppartenancePieceDossier::values())],

            // `min:1`, et surtout PAS de zéro toléré : « 0 mois de validité »
            // se lit « périmé à l'instant du dépôt », l'exact contraire de
            // l'intention de qui l'aurait saisi pour dire « jamais ». « Jamais »
            // s'écrit en laissant le champ vide, donc NULL.
            'duree_validite_mois' => ['nullable', 'integer', 'min:1', 'max:600'],

            // Portée vide = toutes les filières, tous les niveaux. Le tableau
            // vide est donc accepté tel quel, jamais remplacé par une valeur
            // sentinelle qui voudrait dire « toutes » en extension.
            'filiere_ids' => ['array'],
            'filiere_ids.*' => ['integer', 'exists:esbtp_filieres,id'],
            'niveau_ids' => ['array'],
            'niveau_ids.*' => ['integer', 'exists:esbtp_niveau_etudes,id'],

            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé de la pièce est obligatoire.',
            'exemplaires_par_inscription.min' => 'Une pièce est attendue en au moins un exemplaire.',
            'exemplaires_par_inscription.max' => 'Au-delà de :max exemplaires, vérifiez la saisie. Ce plafond se règle dans les paramètres de scolarité.',
            'appartenance.in' => "Dites si la pièce appartient à l'étudiant (elle dure) ou à l'inscription (elle est redonnée chaque année).",
            'duree_validite_mois.min' => "Une durée de validité se compte en mois pleins. Pour une pièce qui ne périme jamais, laissez le champ vide.",
            'forme_attendue.in' => "La forme attendue doit être l'original, une copie, ou indifférente.",
            'echeance.in' => "L'échéance doit être « à l'inscription » ou « avant la fin de l'année ».",
            'filiere_ids.*.exists' => "Une des filières sélectionnées n'existe plus.",
            'niveau_ids.*.exists' => "Un des niveaux sélectionnés n'existe plus.",
        ];
    }

    /**
     * Une portée vide arrive du navigateur sous plusieurs formes : absente,
     * tableau vide, chaîne vide, identifiants en texte. On la ramène à un
     * tableau d'entiers avant validation, pour que « tout le monde » ait une
     * seule représentation et une seule façon d'être testé.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_obligatoire' => $this->boolean('is_obligatoire'),
            'is_active' => $this->boolean('is_active'),
            'filiere_ids' => $this->normaliserIds($this->input('filiere_ids')),
            'niveau_ids' => $this->normaliserIds($this->input('niveau_ids')),
        ]);
    }

    /** Champs de la pièce elle-même, sans la portée qui vit dans les pivots. */
    public function attributsPiece(): array
    {
        return collect($this->validated())
            ->except(['filiere_ids', 'niveau_ids'])
            ->all();
    }

    /** @return array<int, int> */
    public function filiereIds(): array
    {
        return $this->validated()['filiere_ids'] ?? [];
    }

    /** @return array<int, int> */
    public function niveauIds(): array
    {
        return $this->validated()['niveau_ids'] ?? [];
    }

    /** @return array<int, int> */
    private function normaliserIds($valeur): array
    {
        if (! is_array($valeur)) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            array_filter($valeur, fn ($v) => $v !== null && $v !== '' && is_numeric($v))
        )));
    }
}
