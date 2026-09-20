<?php

namespace App\Http\Requests\Classe;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreClasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // En mode LMD, filiere_id sert semantiquement de Mention (Option A).
        // Si parcours_id est fourni : filiere_id sera derive depuis parcours.filiere_id
        // (cf. ESBTPClasseController::store L325-329). Donc nullable dans ce cas.
        // Si parcours_id absent (tronc commun mention) : filiere_id required (= mention).
        $isLmd = $this->detectLmdMode();
        $hasParcours = $this->filled('parcours_id');

        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:esbtp_classes,code',
            'filiere_id' => $this->regleFiliere($isLmd, $hasParcours),
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
                return; // validation principale gere l'absence de niveau
            }

            $niveau = ESBTPNiveauEtude::find($niveauId);
            if (!$niveau) {
                return;
            }

            $isLmdNiveau = $niveau->estUnCycleLmd();
            $filiereId = $this->input('filiere_id');
            $parcoursId = $this->input('parcours_id');

            if ($isLmdNiveau) {
                // LMD : doit avoir au moins une mention (filiere_id sert de mention) OU un parcours
                if (empty($filiereId) && empty($parcoursId)) {
                    $validator->errors()->add(
                        'filiere_id',
                        'En mode LMD (niveau Licence/Master/Doctorat), une mention est requise (avec ou sans parcours).'
                    );
                    return;
                }

                // Si parcours fourni : verifier coherence avec la mention (filiere_id = mention en LMD)
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

    /**
     * Heuristique pour determiner si la requete est en mode LMD.
     * Utilise le type du niveau d'etudes (source de verite).
     */
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
        return $niveau->estUnCycleLmd();
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

    /**
     * La regle du champ `filiere_id`, qui ne designe pas la meme chose selon
     * le systeme academique.
     *
     * En BTS, c'est une filiere. En LMD, le selecteur de mention est pose sur
     * ce champ faute de colonne dediee sur `esbtp_classes` : il porte alors un
     * id de MENTION. Le controleur convertit ensuite en filiere d'ancrage.
     *
     * En LMD on accepte les deux tables : une classe creee avant cette regle
     * porte une vraie filiere, et rouvrir sa fiche pour corriger un nom ne doit
     * pas exiger de rechoisir sa mention.
     */
    private function regleFiliere(bool $isLmd, bool $hasParcours): array
    {
        $presence = ($isLmd && $hasParcours) ? 'nullable' : 'required';

        if (! $isLmd) {
            return [$presence, 'exists:esbtp_filieres,id'];
        }

        return [$presence, function (string $attribut, $valeur, Closure $echoue) {
            if (blank($valeur)) {
                return;
            }

            $connue = ESBTPLMDMention::whereKey($valeur)->exists()
                || ESBTPFiliere::whereKey($valeur)->exists();

            if (! $connue) {
                $echoue('La mention sélectionnée est invalide.');
            }
        }];
    }
}
