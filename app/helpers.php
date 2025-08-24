<?php

if (!function_exists('userHasRole')) {
    /**
     * Vérifie si l'utilisateur authentifié a un rôle donné en toute sécurité
     *
     * @param string|array $role
     * @return bool
     */
    function userHasRole($role)
    {
        return auth()->check() && auth()->user() && auth()->user()->hasRole($role);
    }
}

if (!function_exists('userHasAnyRole')) {
    /**
     * Vérifie si l'utilisateur authentifié a l'un des rôles donnés en toute sécurité
     *
     * @param array $roles
     * @return bool
     */
    function userHasAnyRole($roles)
    {
        return auth()->check() && auth()->user() && auth()->user()->hasRole($roles);
    }
}

if (!function_exists('isAuthenticated')) {
    /**
     * Vérifie si l'utilisateur est authentifié et que l'objet user existe
     *
     * @return bool
     */
    function isAuthenticated()
    {
        return auth()->check() && auth()->user();
    }
}