<?php

namespace App\Helpers;

/**
 * Helper pour la gestion des rôles et équivalences
 *
 * Gère l'équivalence entre coordinateur et superAdmin
 * Ces deux rôles ont exactement les mêmes permissions dans le LMS
 */
class RoleHelper
{
    /**
     * Rôles considérés comme équivalents au coordinateur
     */
    const COORDINATOR_EQUIVALENT_ROLES = ['coordinateur', 'superAdmin'];

    /**
     * Rôles considérés comme des administrateurs
     */
    const ADMIN_ROLES = ['coordinateur', 'superAdmin'];

    /**
     * Rôles considérés comme des enseignants
     */
    const TEACHER_ROLES = ['enseignant'];

    /**
     * Rôles considérés comme des étudiants
     */
    const STUDENT_ROLES = ['etudiant'];

    /**
     * Rôles autorisés pour l'accès LMS
     */
    const LMS_ALLOWED_ROLES = ['enseignant', 'coordinateur', 'etudiant', 'superAdmin'];

    /**
     * Vérifie si un rôle est équivalent au coordinateur
     *
     * @param string $role Le rôle à vérifier
     * @return bool true si le rôle est équivalent au coordinateur
     */
    public static function isCoordinatorEquivalent(string $role): bool
    {
        return in_array($role, self::COORDINATOR_EQUIVALENT_ROLES);
    }

    /**
     * Vérifie si un utilisateur a l'un des rôles requis
     * Traite automatiquement l'équivalence coordinateur/superAdmin
     *
     * @param string $userRole Le rôle de l'utilisateur
     * @param array $requiredRoles Les rôles requis
     * @return bool true si l'utilisateur a l'un des rôles requis
     */
    public static function hasAnyRole(string $userRole, array $requiredRoles): bool
    {
        // Si l'utilisateur a directement l'un des rôles requis
        if (in_array($userRole, $requiredRoles)) {
            return true;
        }

        // Si les rôles requis incluent coordinateur et que l'utilisateur est superAdmin
        if (in_array('coordinateur', $requiredRoles) && self::isCoordinatorEquivalent($userRole)) {
            return true;
        }

        // Si les rôles requis incluent superAdmin et que l'utilisateur est coordinateur
        if (in_array('superAdmin', $requiredRoles) && self::isCoordinatorEquivalent($userRole)) {
            return true;
        }

        return false;
    }

    /**
     * Normalise un rôle vers son équivalent standardisé
     * Convertit superAdmin vers coordinateur pour uniformiser
     *
     * @param string $role Le rôle à normaliser
     * @return string Le rôle normalisé
     */
    public static function normalizeRole(string $role): string
    {
        if (self::isCoordinatorEquivalent($role)) {
            return 'coordinateur'; // Normaliser vers coordinateur
        }
        return $role;
    }

    /**
     * Vérifie si un rôle est un rôle d'administration
     *
     * @param string $role Le rôle à vérifier
     * @return bool true si c'est un rôle d'administration
     */
    public static function isAdmin(string $role): bool
    {
        return in_array($role, self::ADMIN_ROLES);
    }

    /**
     * Vérifie si un rôle est un rôle d'enseignant
     *
     * @param string $role Le rôle à vérifier
     * @return bool true si c'est un rôle d'enseignant
     */
    public static function isTeacher(string $role): bool
    {
        return in_array($role, self::TEACHER_ROLES);
    }

    /**
     * Vérifie si un rôle est un rôle d'étudiant
     *
     * @param string $role Le rôle à vérifier
     * @return bool true si c'est un rôle d'étudiant
     */
    public static function isStudent(string $role): bool
    {
        return in_array($role, self::STUDENT_ROLES);
    }

    /**
     * Obtient le libellé d'affichage pour un rôle
     *
     * @param string $role Le rôle
     * @return string Le libellé d'affichage
     */
    public static function getRoleDisplayName(string $role): string
    {
        switch ($role) {
            case 'coordinateur':
            case 'superAdmin':
                return 'Coordinateur/Administrateur';
            case 'enseignant':
                return 'Enseignant';
            case 'etudiant':
                return 'Étudiant';
            default:
                return $role;
        }
    }

    /**
     * Obtient les permissions d'un rôle
     *
     * @param string $role Le rôle
     * @return array La liste des permissions
     */
    public static function getRolePermissions(string $role): array
    {
        if (self::isCoordinatorEquivalent($role)) {
            return [
                'view_all_courses',
                'manage_evaluations',
                'view_all_students',
                'manage_schedules',
                'admin_access',
                'generate_reports',
                'manage_users',
                'view_statistics'
            ];
        }

        if (self::isTeacher($role)) {
            return [
                'view_own_courses',
                'manage_own_evaluations',
                'view_course_students',
                'record_attendance',
                'generate_course_reports'
            ];
        }

        if (self::isStudent($role)) {
            return [
                'view_own_courses',
                'notes.view_own',
                'access_chat',
                'view_schedule'
            ];
        }

        return [];
    }

    /**
     * Vérifie si un utilisateur a une permission spécifique
     *
     * @param string $userRole Le rôle de l'utilisateur
     * @param string $permission La permission à vérifier
     * @return bool true si l'utilisateur a la permission
     */
    public static function hasPermission(string $userRole, string $permission): bool
    {
        $permissions = self::getRolePermissions($userRole);
        return in_array($permission, $permissions);
    }

    /**
     * Détermine si deux rôles sont équivalents
     *
     * @param string $role1 Premier rôle
     * @param string $role2 Deuxième rôle
     * @return bool true si les rôles sont équivalents
     */
    public static function areRolesEquivalent(string $role1, string $role2): bool
    {
        // Même rôle
        if ($role1 === $role2) {
            return true;
        }

        // Coordinateur et superAdmin sont équivalents
        if (self::isCoordinatorEquivalent($role1) && self::isCoordinatorEquivalent($role2)) {
            return true;
        }

        return false;
    }
}