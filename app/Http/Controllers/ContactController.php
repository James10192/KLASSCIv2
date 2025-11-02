<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function sendDemo(Request $request)
    {
        try {
            // Validation des données
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'telephone' => 'nullable|string|max:20',
                'etablissement' => 'required|string|max:255',
                'type_etablissement' => 'required|string|in:ecole_primaire,college,lycee,universite,ecole_superieure,centre_formation,autre',
                'nombre_etudiants' => 'nullable|string|in:moins_100,100_500,500_1000,1000_5000,plus_5000',
                'message' => 'nullable|string|max:1000'
            ], [
                'nom.required' => 'Le nom est obligatoire.',
                'email.required' => 'L\'email est obligatoire.',
                'email.email' => 'Format d\'email invalide.',
                'etablissement.required' => 'Le nom de l\'établissement est obligatoire.',
                'type_etablissement.required' => 'Le type d\'établissement est obligatoire.',
                'type_etablissement.in' => 'Type d\'établissement invalide.'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            // Préparer les données pour l'email
            $emailData = [
                'nom' => $validatedData['nom'],
                'email' => $validatedData['email'],
                'telephone' => $validatedData['telephone'] ?? 'Non renseigné',
                'etablissement' => $validatedData['etablissement'],
                'type_etablissement' => $this->getTypeEtablissementLabel($validatedData['type_etablissement']),
                'nombre_etudiants' => $this->getNombreEtudiantsLabel($validatedData['nombre_etudiants'] ?? ''),
                'message' => $validatedData['message'] ?? 'Aucun message spécifique',
                'date_demande' => now()->format('d/m/Y à H:i'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ];

            // Email de destination (configurable via .env)
            $destinationEmail = env('DEMO_EMAIL', 'commercial@klassci.com');

            // Envoyer l'email
            Mail::send('emails.demo-request', $emailData, function ($message) use ($destinationEmail, $emailData) {
                $message->to($destinationEmail)
                        ->subject('📋 Nouvelle demande de démonstration KLASSCI - ' . $emailData['etablissement'])
                        ->replyTo($emailData['email'], $emailData['nom']);
            });

            // Log de la demande pour suivi
            Log::info('Nouvelle demande de démonstration', [
                'nom' => $emailData['nom'],
                'email' => $emailData['email'],
                'etablissement' => $emailData['etablissement'],
                'type_etablissement' => $emailData['type_etablissement'],
                'ip' => $emailData['ip_address']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Votre demande de démonstration a été envoyée avec succès ! Notre équipe vous contactera sous 24h.'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de la demande de démonstration', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer ou nous contacter directement.'
            ], 500);
        }
    }

    private function getTypeEtablissementLabel($type)
    {
        $types = [
            'ecole_primaire' => 'École primaire',
            'college' => 'Collège',
            'lycee' => 'Lycée',
            'universite' => 'Université',
            'ecole_superieure' => 'École supérieure',
            'centre_formation' => 'Centre de formation',
            'autre' => 'Autre'
        ];

        return $types[$type] ?? $type;
    }

    private function getNombreEtudiantsLabel($nombre)
    {
        if (empty($nombre)) {
            return 'Non renseigné';
        }

        $nombres = [
            'moins_100' => 'Moins de 100',
            '100_500' => '100 - 500',
            '500_1000' => '500 - 1 000',
            '1000_5000' => '1 000 - 5 000',
            'plus_5000' => 'Plus de 5 000'
        ];

        return $nombres[$nombre] ?? $nombre;
    }
}