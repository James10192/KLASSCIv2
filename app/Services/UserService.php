<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Génère un username unique pour un coordinateur
     */
    public function generateCoordinateurUsername(string $prenom, string $nom): string
    {
        $baseUsername = $this->createBaseUsername('coord', $prenom, $nom);
        return $this->ensureUniqueUsername($baseUsername);
    }

    /**
     * Génère un username unique pour un enseignant
     */
    public function generateEnseignantUsername(string $prenom, string $nom): string
    {
        $baseUsername = $this->createBaseUsername('prof', $prenom, $nom);
        return $this->ensureUniqueUsername($baseUsername);
    }

    /**
     * Génère un username unique pour un secrétaire
     */
    public function generateSecretaireUsername(string $prenom, string $nom): string
    {
        $baseUsername = $this->createBaseUsername('sec', $prenom, $nom);
        return $this->ensureUniqueUsername($baseUsername);
    }

    /**
     * Génère le mot de passe générique de l'année courante
     */
    public function generateDefaultPassword(): string
    {
        return 'Bonjour@' . date('Y');
    }

    /**
     * Crée un utilisateur avec username auto et mot de passe générique
     */
    public function createUserWithAutoCredentials(array $userData, string $roleType): User
    {
        // Extraire prenom et nom du nom complet
        $nameParts = $this->extractNameParts($userData['name']);
        
        // Générer le username selon le type
        switch ($roleType) {
            case 'coordinateur':
                $username = $this->generateCoordinateurUsername($nameParts['prenom'], $nameParts['nom']);
                break;
            case 'enseignant':
                $username = $this->generateEnseignantUsername($nameParts['prenom'], $nameParts['nom']);
                break;
            case 'secretaire':
                $username = $this->generateSecretaireUsername($nameParts['prenom'], $nameParts['nom']);
                break;
            default:
                throw new \InvalidArgumentException("Type de rôle non supporté: {$roleType}");
        }

        // Générer le mot de passe par défaut
        $defaultPassword = $this->generateDefaultPassword();

        // Créer l'utilisateur
        $user = User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'username' => $username,
            'password' => Hash::make($defaultPassword),
            'phone' => $userData['phone'] ?? null,
            'is_active' => true,
            'must_change_password' => true,
            'created_by' => auth()->id(),
        ]);

        return $user;
    }

    /**
     * Extrait prénom et nom d'un nom complet
     * Prend le premier prénom et le dernier nom de famille pour éviter des usernames trop longs
     */
    private function extractNameParts(string $fullName): array
    {
        $parts = explode(' ', trim($fullName));
        
        if (count($parts) >= 2) {
            $prenom = $parts[0]; // Premier prénom
            $nom = end($parts); // Dernier nom de famille
        } else {
            $prenom = $parts[0];
            $nom = $parts[0]; // Utilise le même nom si un seul mot
        }

        return [
            'prenom' => $this->cleanString($prenom),
            'nom' => $this->cleanString($nom)
        ];
    }

    /**
     * Crée le username de base selon le format
     */
    private function createBaseUsername(string $prefix, string $prenom, string $nom): string
    {
        return "{$prefix}.{$prenom}.{$nom}";
    }

    /**
     * S'assure que le username est unique
     */
    private function ensureUniqueUsername(string $baseUsername): string
    {
        $username = strtolower($baseUsername);
        $originalUsername = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $originalUsername . '.' . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Nettoie une chaîne pour créer un username
     */
    private function cleanString(string $string): string
    {
        // Convertir en minuscules
        $string = strtolower($string);
        
        // Supprimer les accents
        $string = $this->removeAccents($string);
        
        // Remplacer les espaces et caractères spéciaux par des tirets
        $string = preg_replace('/[^a-z0-9]/', '-', $string);
        
        // Supprimer les tirets multiples consécutifs
        $string = preg_replace('/-+/', '-', $string);
        
        // Supprimer les tirets en début et fin
        $string = trim($string, '-');
        
        return $string;
    }

    /**
     * Supprime les accents d'une chaîne
     */
    private function removeAccents(string $string): string
    {
        $accents = [
            'à' => 'a', 'á' => 'a', 'ä' => 'a', 'â' => 'a', 'ā' => 'a', 'ã' => 'a',
            'è' => 'e', 'é' => 'e', 'ë' => 'e', 'ê' => 'e', 'ē' => 'e',
            'ì' => 'i', 'í' => 'i', 'ï' => 'i', 'î' => 'i', 'ī' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ö' => 'o', 'ô' => 'o', 'ō' => 'o', 'õ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ü' => 'u', 'û' => 'u', 'ū' => 'u',
            'ç' => 'c', 'ñ' => 'n',
            'À' => 'A', 'Á' => 'A', 'Ä' => 'A', 'Â' => 'A', 'Ā' => 'A', 'Ã' => 'A',
            'È' => 'E', 'É' => 'E', 'Ë' => 'E', 'Ê' => 'E', 'Ē' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Ï' => 'I', 'Î' => 'I', 'Ī' => 'I',
            'Ò' => 'O', 'Ó' => 'O', 'Ö' => 'O', 'Ô' => 'O', 'Ō' => 'O', 'Õ' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Ü' => 'U', 'Û' => 'U', 'Ū' => 'U',
            'Ç' => 'C', 'Ñ' => 'N',
        ];

        return strtr($string, $accents);
    }

    /**
     * Marque qu'un utilisateur a changé son mot de passe
     */
    public function markPasswordChanged(User $user): void
    {
        $user->update([
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
    }

    /**
     * Marque la première connexion d'un utilisateur
     */
    public function markFirstLogin(User $user): void
    {
        if (!$user->first_login_at) {
            $user->update([
                'first_login_at' => now(),
            ]);
        }
    }

    /**
     * Vérifie si un utilisateur doit changer son mot de passe
     */
    public function mustChangePassword(User $user): bool
    {
        return (bool) $user->must_change_password;
    }

    /**
     * Génère les informations d'affichage des credentials
     */
    public function getCredentialsInfo(string $username, string $password): array
    {
        return [
            'username' => $username,
            'password' => $password,
            'login_url' => route('login'),
            'message' => "Nom d'utilisateur: {$username}\nMot de passe temporaire: {$password}\n\nVous devrez changer votre mot de passe lors de la première connexion."
        ];
    }

}
