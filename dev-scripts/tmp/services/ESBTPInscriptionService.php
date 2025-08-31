<?php

namespace App\Services;

use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class ESBTPInscriptionService
{
    /**
     * Créer une nouvelle inscription d'un étudiant
     *
     * @param array $etudiantData Les données de l'étudiant
     * @param array $inscriptionData Les données de l'inscription
     * @param array $parentData Les données du parent/tuteur (optionnel)
     * @param array $paiementData Les données du paiement initial (optionnel)
     * @param int $userId ID de l'utilisateur qui crée l'inscription
     * @return array Résultat de l'opération
     */
    public function createInscription(array $etudiantData, array $inscriptionData, ?array $parentData = null, ?array $paiementData = null, int $userId)
    {
        try {
            DB::beginTransaction();
            
            // 1. Création de l'étudiant
            $etudiant = $this->createOrUpdateEtudiant($etudiantData, $userId);
            
            // 2. Création de l'inscription
            $inscriptionData['etudiant_id'] = $etudiant->id;
            $inscriptionData['created_by'] = $userId;
            $inscriptionData['updated_by'] = $userId;
            
            // Générer un matricule unique pour l'étudiant
            if (empty($etudiant->matricule)) {
                $filiere = ESBTPFiliere::find($inscriptionData['filiere_id']);
                $niveau = ESBTPNiveauEtude::find($inscriptionData['niveau_id']);
                $annee = ESBTPAnneeUniversitaire::find($inscriptionData['annee_universitaire_id']);
                
                if ($filiere && $niveau && $annee) {
                    // Format: [CODE_FILIERE][CODE_NIVEAU][ANNÉE][NUMÉRO_SÉQUENTIEL]
                    // Exemple: GC1BTS23001 pour Génie Civil 1ère année BTS, année 2023, numéro 001
                    $filiereCode = $filiere->code ?? substr($filiere->name, 0, 2);
                    $niveauCode = $niveau->code ?? $niveau->year ?? '1';
                    $anneeCode = substr($annee->code ?? date('Y'), 2, 2);
                    
                    // Trouver le dernier numéro séquentiel pour cette combinaison
                    $matriculePrefix = strtoupper($filiereCode . $niveauCode . $anneeCode);
                    $lastMatricule = ESBTPEtudiant::where('matricule', 'LIKE', $matriculePrefix . '%')
                                                ->orderBy('matricule', 'desc')
                                                ->first();
                    
                    $sequence = 1;
                    if ($lastMatricule) {
                        $lastSequence = (int) substr($lastMatricule->matricule, strlen($matriculePrefix));
                        $sequence = $lastSequence + 1;
                    }
                    
                    $etudiant->matricule = $matriculePrefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);
                    $etudiant->save();
                    
                    // Création automatique d'un compte utilisateur pour l'étudiant
                    if (!$etudiant->user_id) {
                        $prenoms = explode(' ', $etudiant->prenoms);
                        $prenom = $prenoms[0] ?? '';
                        
                        // Génération du nom d'utilisateur: prénom + première lettre du nom + 3 derniers chiffres du matricule
                        $username = strtolower($prenom . substr($etudiant->nom, 0, 1) . substr($etudiant->matricule, -3));
                        $username = Str::slug($username, '');
                        
                        // Vérifier si le nom d'utilisateur existe déjà
                        $existingCount = User::where('username', 'LIKE', $username . '%')->count();
                        if ($existingCount > 0) {
                            $username .= ($existingCount + 1);
                        }
                        
                        // Générer un mot de passe aléatoire
                        $password = Str::random(8);
                        $etudiantData['password_generated'] = $password;
                        
                        // Créer le compte utilisateur
                        $user = User::create([
                            'name' => $etudiant->prenoms . ' ' . $etudiant->nom,
                            'username' => $username,
                            'email' => $etudiant->email_personnel,
                            'password' => Hash::make($password),
                            'is_active' => true,
                        ]);
                        
                        // Assigner le rôle étudiant
                        $role = Role::where('name', 'etudiant')->first();
                        if ($role) {
                            $user->assignRole($role);
                        }
                        
                        // Associer l'utilisateur à l'étudiant
                        $etudiant->user_id = $user->id;
                        $etudiant->save();
                    }
                }
            }
            
            // Générer un numéro de reçu pour le paiement initial
            if (!empty($inscriptionData['numero_recu'])) {
                $numeroRecu = $inscriptionData['numero_recu'];
            } else {
                $anneeCode = substr(date('Y'), 2, 2);
                $annee = ESBTPAnneeUniversitaire::find($inscriptionData['annee_universitaire_id']);
                if ($annee) {
                    $anneeCode = substr($annee->code, 2, 2);
                }
                $numeroRecu = 'INSC' . $anneeCode . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            }
            
            $inscriptionData['numero_recu'] = $numeroRecu;
            
            $inscription = ESBTPInscription::create($inscriptionData);
            
            // 3. Création du parent/tuteur (si fourni)
            if ($parentData && !empty($parentData)) {
                $parent = $this->createOrUpdateParent($parentData, $userId);
                
                // Associer le parent à l'étudiant
                $etudiant->parents()->attach($parent->id, [
                    'relation' => $parentData['relation'] ?? 'tuteur',
                    'is_tuteur' => $parentData['is_tuteur'] ?? true,
                ]);
            }
            
            // 4. Enregistrement du paiement initial (si fourni)
            if ($paiementData && !empty($paiementData)) {
                $paiementData['inscription_id'] = $inscription->id;
                $paiementData['etudiant_id'] = $etudiant->id;
                $paiementData['created_by'] = $userId;
                $paiementData['updated_by'] = $userId;
                
                // Générer un numéro de reçu
                if (empty($paiementData['numero_recu'])) {
                    $paiementData['numero_recu'] = ESBTPPaiement::genererNumeroRecu();
                }
                
                ESBTPPaiement::create($paiementData);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'etudiant' => $etudiant,
                'inscription' => $inscription,
                'message' => 'Inscription créée avec succès'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création de l\'inscription: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la création de l\'inscription: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Crée ou met à jour un étudiant
     *
     * @param array $etudiantData Données de l'étudiant
     * @param int $userId ID de l'utilisateur qui crée/modifie l'étudiant
     * @return ESBTPEtudiant L'instance de l'étudiant
     */
    protected function createOrUpdateEtudiant(array $etudiantData, int $userId)
    {
        // Si un ID d'étudiant est fourni, récupérer l'étudiant existant
        if (!empty($etudiantData['id'])) {
            $etudiant = ESBTPEtudiant::findOrFail($etudiantData['id']);
        } else {
            // Sinon, créer un nouvel étudiant
            $etudiant = new ESBTPEtudiant();
            $etudiant->created_by = $userId;
        }
        
        // Remplir les données de l'étudiant
        $fillableData = array_intersect_key($etudiantData, array_flip($etudiant->getFillable()));
        $etudiant->fill($fillableData);
        
        // Gérer la photo si présente
        if (isset($etudiantData['photo_file']) && $etudiantData['photo_file']) {
            // Supprimer l'ancienne photo si elle existe
            if ($etudiant->photo && Storage::exists(str_replace('/storage', 'public', $etudiant->photo))) {
                Storage::delete(str_replace('/storage', 'public', $etudiant->photo));
            }
            
            $photoPath = $etudiantData['photo_file']->store('public/etudiants/photos');
            $etudiant->photo = Storage::url($photoPath);
        }
        
        $etudiant->updated_by = $userId;
        $etudiant->save();
        
        return $etudiant;
    }
    
    /**
     * Crée ou met à jour un parent
     *
     * @param array $parentData Données du parent
     * @param int $userId ID de l'utilisateur qui crée/modifie le parent
     * @return ESBTPParent L'instance du parent
     */
    protected function createOrUpdateParent(array $parentData, int $userId)
    {
        // Si un ID de parent est fourni, récupérer le parent existant
        if (!empty($parentData['id'])) {
            $parent = ESBTPParent::findOrFail($parentData['id']);
        } else {
            // Sinon, vérifier si un parent avec les mêmes informations existe déjà
            $parent = ESBTPParent::where('nom', $parentData['nom'])
                        ->where('prenoms', $parentData['prenoms'])
                        ->where('telephone', $parentData['telephone'])
                        ->first();
                        
            // Si aucun parent n'est trouvé, en créer un nouveau
            if (!$parent) {
                $parent = new ESBTPParent();
                $parent->created_by = $userId;
                
                // Créer un compte utilisateur pour le parent si nécessaire
                if (isset($parentData['create_account']) && $parentData['create_account']) {
                    $prenoms = explode(' ', $parentData['prenoms']);
                    $prenom = $prenoms[0] ?? '';
                    
                    // Générer le nom d'utilisateur: prénom + première lettre du nom + initiales
                    $username = strtolower($prenom . substr($parentData['nom'], 0, 1));
                    for ($i = 1; $i < count($prenoms); $i++) {
                        if (isset($prenoms[$i][0])) {
                            $username .= strtolower($prenoms[$i][0]);
                        }
                    }
                    
                    // Vérifier si le nom d'utilisateur existe déjà
                    $existingCount = User::where('username', 'LIKE', $username . '%')->count();
                    if ($existingCount > 0) {
                        $username .= ($existingCount + 1);
                    }
                    
                    // Générer un mot de passe aléatoire
                    $password = Str::random(8);
                    $parentData['password_generated'] = $password;
                    
                    // Créer le compte utilisateur
                    $user = User::create([
                        'name' => $parentData['prenoms'] . ' ' . $parentData['nom'],
                        'username' => $username,
                        'email' => $parentData['email'] ?? null,
                        'password' => Hash::make($password),
                        'is_active' => true,
                    ]);
                    
                    // Assigner le rôle parent
                    $role = Role::where('name', 'parent')->first();
                    if ($role) {
                        $user->assignRole($role);
                    }
                    
                    // Associer l'utilisateur au parent
                    $parent->user_id = $user->id;
                }
            }
        }
        
        // Remplir les données du parent
        $fillableData = array_intersect_key($parentData, array_flip($parent->getFillable()));
        $parent->fill($fillableData);
        $parent->updated_by = $userId;
        $parent->save();
        
        return $parent;
    }
    
    /**
     * Valider une inscription
     *
     * @param int $inscriptionId ID de l'inscription à valider
     * @param int $userId ID de l'utilisateur qui valide l'inscription
     * @return array Résultat de l'opération
     */
    public function validerInscription(int $inscriptionId, int $userId)
    {
        try {
            DB::beginTransaction();
            
            $inscription = ESBTPInscription::findOrFail($inscriptionId);
            
            // Ne pas valider une inscription déjà validée
            if ($inscription->status === 'active') {
                return [
                    'success' => false,
                    'message' => 'Cette inscription est déjà validée'
                ];
            }
            
            $inscription->status = 'active';
            $inscription->date_validation = now();
            $inscription->validated_by = $userId;
            $inscription->updated_by = $userId;
            $inscription->save();
            
            // Mettre à jour le statut de l'étudiant
            $etudiant = $inscription->etudiant;
            $etudiant->statut = 'actif';
            $etudiant->updated_by = $userId;
            $etudiant->save();
            
            // Activer le compte utilisateur de l'étudiant
            if ($etudiant->user_id) {
                $user = User::find($etudiant->user_id);
                if ($user) {
                    $user->is_active = true;
                    $user->save();
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'inscription' => $inscription,
                'message' => 'Inscription validée avec succès'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la validation de l\'inscription: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors de la validation de l\'inscription: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Annuler une inscription
     *
     * @param int $inscriptionId ID de l'inscription à annuler
     * @param string $motif Motif de l'annulation
     * @param int $userId ID de l'utilisateur qui annule l'inscription
     * @return array Résultat de l'opération
     */
    public function annulerInscription(int $inscriptionId, string $motif, int $userId)
    {
        try {
            DB::beginTransaction();
            
            $inscription = ESBTPInscription::findOrFail($inscriptionId);
            
            // Ne pas annuler une inscription déjà annulée
            if ($inscription->status === 'annulée') {
                return [
                    'success' => false,
                    'message' => 'Cette inscription est déjà annulée'
                ];
            }
            
            $inscription->status = 'annulée';
            $inscription->observations = ($inscription->observations ? $inscription->observations . "\n" : '') . 
                                        "Annulée le " . now()->format('d/m/Y') . ". Motif: " . $motif;
            $inscription->updated_by = $userId;
            $inscription->save();
            
            // Désactiver l'étudiant si c'est sa seule inscription active
            $etudiant = $inscription->etudiant;
            $hasActiveInscriptions = $etudiant->inscriptions()
                                            ->where('id', '!=', $inscriptionId)
                                            ->where('status', 'active')
                                            ->exists();
            
            if (!$hasActiveInscriptions) {
                $etudiant->statut = 'inactif';
                $etudiant->updated_by = $userId;
                $etudiant->save();
                
                // Désactiver le compte utilisateur de l'étudiant
                if ($etudiant->user_id) {
                    $user = User::find($etudiant->user_id);
                    if ($user) {
                        $user->is_active = false;
                        $user->save();
                    }
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'inscription' => $inscription,
                'message' => 'Inscription annulée avec succès'
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de l\'annulation de l\'inscription: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de l\'inscription: ' . $e->getMessage()
            ];
        }
    }
} 