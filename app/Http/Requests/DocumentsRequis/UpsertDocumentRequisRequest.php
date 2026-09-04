<?php

namespace App\Http\Requests\DocumentsRequis;

use App\Enums\EcheanceDocumentRequis;
use App\Enums\FormeDocumentRequis;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertDocumentRequisRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La route porte deja la permission ; on ne duplique pas la regle ici,
        // sinon elle finira par diverger de la route.
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'            => ['required', 'string', 'max:255'],
            'description'        => ['nullable', 'string', 'max:2000'],
            'is_obligatoire'     => ['required', 'boolean'],
            'forme_attendue'     => ['required', Rule::in(FormeDocumentRequis::values())],

            // Plafond a 20 : au-dela ce n'est plus un dossier d'inscription mais
            // une erreur de saisie, et le guichet n'a aucun moyen de la corriger
            // apres coup sans rouvrir chaque dossier.
            'nombre_exemplaires' => ['required', 'integer', 'min:1', 'max:20'],

            'echeance'           => ['required', Rule::in(EcheanceDocumentRequis::values())],

            // Portee vide = toutes les filieres / tous les niveaux. On accepte
            // donc explicitement le tableau vide, jamais une valeur sentinelle.
            'filiere_ids'        => ['nullable', 'array'],
            'filiere_ids.*'      => ['integer', 'exists:esbtp_filieres,id'],
            'niveau_ids'         => ['nullable', 'array'],
            'niveau_ids.*'       => ['integer', 'exists:esbtp_niveau_etudes,id'],

            'is_active'          => ['required', 'boolean'],
            'ordre'              => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'            => 'Le libelle de la piece est obligatoire.',
            'nombre_exemplaires.min'      => 'Une piece est attendue en au moins un exemplaire.',
            'nombre_exemplaires.max'      => 'Vingt exemplaires au maximum : au-dela, verifiez la saisie.',
            'filiere_ids.*.exists'        => 'Une des filieres selectionnees n\'existe plus.',
            'niveau_ids.*.exists'         => 'Un des niveaux selectionnes n\'existe plus.',
        ];
    }

    /**
     * Une portee vide arrive du navigateur sous plusieurs formes (absente,
     * tableau vide, chaine vide). On la normalise en null AVANT validation pour
     * que « toutes filieres » ait une seule representation en base.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_obligatoire' => $this->boolean('is_obligatoire'),
            'is_active'      => $this->boolean('is_active'),
            'filiere_ids'    => $this->normaliserIds($this->input('filiere_ids')),
            'niveau_ids'     => $this->normaliserIds($this->input('niveau_ids')),
        ]);
    }

    private function normaliserIds($valeur): array
    {
        if (! is_array($valeur)) {
            return [];
        }

        return array_values(array_unique(array_map(
            'intval',
            array_filter($valeur, fn ($v) => $v !== null && $v !== '')
        )));
    }

    /** Portee vide stockee en null, pas en tableau vide : une seule verite en base. */
    public function pourPersistance(): array
    {
        $donnees = $this->validated();

        $donnees['filiere_ids'] = empty($donnees['filiere_ids']) ? null : $donnees['filiere_ids'];
        $donnees['niveau_ids'] = empty($donnees['niveau_ids']) ? null : $donnees['niveau_ids'];

        return $donnees;
    }
}
