<?php

namespace App\Traits;

trait HasRoles
{
    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     * 
     * @param string|array $roles Le(s) rôle(s) à vérifier
     * @return bool
     */
    public function hasRole($roles)
    {
        if (is_string($roles)) {
            return $this->role === $roles;
        }

        if (is_array($roles)) {
            return in_array($this->role, $roles);
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur a l'un des rôles spécifiés
     * 
     * @param array $roles Les rôles à vérifier
     * @return bool
     */
    public function hasAnyRole($roles)
    {
        return $this->hasRole($roles);
    }

    /**
     * Vérifie si l'utilisateur a tous les rôles spécifiés
     * 
     * @param array $roles Les rôles à vérifier
     * @return bool
     */
    public function hasAllRoles($roles)
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }
} 