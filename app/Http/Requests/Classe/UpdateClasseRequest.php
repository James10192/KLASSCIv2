<?php

namespace App\Http\Requests\Classe;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Services\ClasseManagementService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Meme logique que StoreClasseRequest mais avec exception du code unique pour cette classe.
        $isLmd = $this->detectLmdMode();
        $hasParcours = $this->filled('parcours_id');

        $classeId = $this->route('classe')?->id ?? $this->route('classe');

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_classes,code,' . $classeId,
            'filiere_id' => ($isLmd && $hasParcours)
                ? 'nullable|exists:esbtp_filieres,id'
                : 'required|exists:esbtp_filieres,id',
            'niveau_etude_id' => 'required|exists:esbtp_niveau_etudes,id',
            'annee_universitaire_id' => 'required|exists:esbtp_annee_universitaires,id',
            'places_totales' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'parcours_id' => 'nullable|exists:esbtp_lmd_parcours,id',
        ];
    }

    public function messages(): array
    {
        return [
            'filiere_id.required' => 'En mode BTS, la filière est requise. En mode LMD, sélectionnez au moins une mention.',
            'filiere_id.exists' => 'La mention/filière sélectionnée est invalide.',
            'parcours_id.exists' => 'Le parcours sélectionné est invalide.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $niveauId = $this->input('niveau_etude_id');
            if (!$niveauId) {
                return;
            }

            $niveau = ESBTPNiveauEtude::find($niveauId);
            if (!$niveau) {
                return;
            }

            $isLmdNiveau = in_array($niveau->type, ClasseManagementService::LMD_TYPES, true);
            $filiereId = $this->input('filiere_id');
            $parcoursId = $this->input('parcours_id');

            if ($isLmdNiveau) {
                if (empty($filiereId) && empty($parcoursId)) {
                    $validator->errors()->add(
                        'filiere_id',
                        'En mode LMD (niveau Licence/Master/Doctorat), une mention est requise (avec ou sans parcours).'
                    );
                    return;
                }

                if ($parcoursId && $filiereId) {
                    $parcours = ESBTPLMDParcours::find($parcoursId);
                    if ($parcours && (int) $parcours->mention_id !== (int) $filiereId) {
                        $validator->errors()->add(
                            'parcours_id',
                            'Le parcours sélectionné n\'appartient pas à la mention choisie. Vérifiez la cohérence Mention/Parcours.'
                        );
                    }
                }
            }
        });
    }

    private function detectLmdMode(): bool
    {
        $niveauId = $this->input('niveau_etude_id');
        if (!$niveauId) {
            return false;
        }
        $niveau = ESBTPNiveauEtude::find($niveauId);
        if (!$niveau) {
            return false;
        }
        return in_array($niveau->type, ClasseManagementService::LMD_TYPES, true);
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->ajax() || $this->input('is_ajax') === '1') {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
