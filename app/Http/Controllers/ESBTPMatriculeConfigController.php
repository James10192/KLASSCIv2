<?php

namespace App\Http\Controllers;

use App\Models\ESBTPMatriculeConfig;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPEtablissement;
use App\Models\ESBTPSystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ESBTPMatriculeConfigController extends Controller
{
    /**
     * Afficher la page de configuration des matricules
     */
    public function index()
    {
        $currentEtablissementId = ESBTPSystemSetting::getCurrentEtablissementId();
        $configurations = ESBTPMatriculeConfig::with('etablissement')
            ->where('etablissement_id', $currentEtablissementId)
            ->orderBy('niveau_etude_name')->get();

        $etablissements = ESBTPEtablissement::active()->orderBy('nom')->get();
        $niveauxEtudes = ESBTPNiveauEtude::active()->orderBy('name')->get();
        $matriculeMode = ESBTPSystemSetting::getValue('matricule_mode', 'automatique');

        // Ajouter les exemples à chaque configuration
        foreach ($configurations as $config) {
            $config->exemples_generes = $config->genererExemples();
        }

        return view('esbtp.matricule-config.index', compact(
            'configurations',
            'niveauxEtudes',
            'etablissements',
            'currentEtablissementId',
            'matriculeMode'
        ));
    }

    /**
     * Créer ou mettre à jour une configuration
     */
    public function store(Request $request)
    {
        $request->validate([
            'niveau_etude_code' => 'required|string|max:50',
            'niveau_etude_name' => 'required|string|max:255',
            'prefixe' => 'nullable|string|max:10',
            'annee_format' => 'required|integer|in:2,4',
            'numero_digits' => 'required|integer|min:3|max:6',
            'etablissement_code' => 'required|string|max:20',
            'description' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $currentEtablissementId = ESBTPSystemSetting::getCurrentEtablissementId();
            $etablissement = ESBTPEtablissement::find($currentEtablissementId);

            $config = ESBTPMatriculeConfig::updateOrCreate(
                [
                    'etablissement_id' => $currentEtablissementId,
                    'niveau_etude_code' => $request->niveau_etude_code
                ],
                [
                    'niveau_etude_name' => $request->niveau_etude_name,
                    'prefixe' => $request->prefixe,
                    'annee_format' => $request->annee_format,
                    'numero_digits' => $request->numero_digits,
                    'etablissement_code' => $etablissement->code_court ?? $request->etablissement_code,
                    'description' => $request->description,
                    'is_active' => true
                ]
            );

            // Générer et sauvegarder les exemples
            $config->exemple = $config->genererExemples();
            $config->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configuration sauvegardée avec succès',
                'exemples' => $config->genererExemples()
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une configuration
     */
    public function destroy($id)
    {
        try {
            $config = ESBTPMatriculeConfig::findOrFail($id);
            $config->delete();

            return response()->json([
                'success' => true,
                'message' => 'Configuration supprimée avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser les exemples de matricules
     */
    public function previewMatricule(Request $request)
    {
        $request->validate([
            'prefixe' => 'nullable|string|max:10',
            'annee_format' => 'required|integer|in:2,4',
            'numero_digits' => 'required|integer|min:3|max:6',
            'etablissement_code' => 'required|string|max:20'
        ]);

        $anneeActuelle = date('Y');
        $anneeFormatee = $request->annee_format == 2 ?
            substr($anneeActuelle, -2) : $anneeActuelle;

        $exemples = [
            'masculin' => 'M' .
                         ($request->prefixe ? $request->prefixe : '') .
                         $request->etablissement_code .
                         $anneeFormatee . '-' .
                         str_pad(1, $request->numero_digits, '0', STR_PAD_LEFT),

            'feminin' => 'F' .
                        ($request->prefixe ? $request->prefixe : '') .
                        $request->etablissement_code .
                        $anneeFormatee . '-' .
                        str_pad(1, $request->numero_digits, '0', STR_PAD_LEFT)
        ];

        return response()->json([
            'success' => true,
            'exemples' => $exemples
        ]);
    }

    /**
     * Générer un matricule pour un étudiant
     */
    public function genererMatricule(Request $request)
    {
        $request->validate([
            'niveau_etude_code' => 'required|string',
            'genre' => 'required|in:M,F',
            'annee' => 'nullable|integer'
        ]);

        try {
            $config = ESBTPMatriculeConfig::where('niveau_etude_code', $request->niveau_etude_code)
                ->where('is_active', true)
                ->first();

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Configuration non trouvée pour ce niveau d\'études'
                ], 404);
            }

            $matricule = $config->genererMatricule($request->genre, $request->annee);

            return response()->json([
                'success' => true,
                'matricule' => $matricule
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier si un matricule existe déjà
     */
    public function checkMatricule(Request $request)
    {
        $request->validate([
            'matricule' => 'required|string'
        ]);

        $exists = ESBTPMatriculeConfig::matriculeExists($request->matricule);

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Ce matricule existe déjà' : 'Matricule disponible'
        ]);
    }

    /**
     * Changer le mode de génération des matricules
     */
    public function changeMode(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:manuel,automatique'
        ]);

        try {
            ESBTPSystemSetting::setValue('matricule_mode', $request->mode);

            return response()->json([
                'success' => true,
                'message' => 'Mode changé avec succès',
                'mode' => $request->mode
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Changer l'établissement actuel
     */
    public function changeEtablissement(Request $request)
    {
        $request->validate([
            'etablissement_id' => 'required|exists:esbtp_etablissements,id'
        ]);

        try {
            ESBTPSystemSetting::setValue('current_etablissement_id', $request->etablissement_id);

            $etablissement = ESBTPEtablissement::find($request->etablissement_id);

            return response()->json([
                'success' => true,
                'message' => 'Établissement changé avec succès',
                'etablissement' => $etablissement->nom
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations du mode actuel pour l'interface inscription
     */
    public function getModeInfo()
    {
        try {
            $mode = ESBTPSystemSetting::getValue('matricule_mode', 'automatique');
            $etablissementId = ESBTPSystemSetting::getCurrentEtablissementId();
            $etablissement = ESBTPEtablissement::find($etablissementId);

            return response()->json([
                'success' => true,
                'mode' => $mode,
                'etablissement' => $etablissement ? [
                    'id' => $etablissement->id,
                    'nom' => $etablissement->nom,
                    'code' => $etablissement->code_court
                ] : null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les configurations pour un établissement (AJAX)
     */
    public function getConfigurations(Request $request)
    {
        $request->validate([
            'etablissement_id' => 'required|exists:esbtp_etablissements,id'
        ]);

        try {
            $configurations = ESBTPMatriculeConfig::where('etablissement_id', $request->etablissement_id)
                ->where('is_active', true)
                ->orderBy('niveau_etude_code')
                ->get();

            return response()->json([
                'success' => true,
                'configurations' => $configurations->map(function ($config) {
                    return [
                        'id' => $config->id,
                        'niveau_etude_code' => $config->niveau_etude_code,
                        'niveau_etude_name' => $config->niveau_etude_name,
                        'description' => $config->description,
                        'annee_format' => $config->annee_format,
                        'numero_digits' => $config->numero_digits,
                        'etablissement_code' => $config->etablissement_code,
                        'exemples_generes' => $config->exemples_generes
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'configurations' => []
            ], 500);
        }
    }
}