<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CLIMaintenanceController extends BaseApiController
{
    /**
     * POST /api/cli/cache/clear — Clear all caches
     */
    public function cacheClear(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $output = [];

        try {
            Artisan::call('config:clear');
            $output[] = 'config:clear OK';

            Artisan::call('cache:clear');
            $output[] = 'cache:clear OK';

            Artisan::call('view:clear');
            $output[] = 'view:clear OK';

            Artisan::call('permission:cache-reset');
            $output[] = 'permission:cache-reset OK';

            // Also clear settings cache
            Setting::clearCache();
            $output[] = 'settings cache cleared';
        } catch (\Exception $e) {
            Log::error('CLI: cache clear failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', ['completed' => $output], 500);
        }

        return $this->successResponse([
            'commands' => $output,
        ], 'All caches cleared successfully');
    }

    /**
     * POST /api/cli/permissions/fix — Sync all permissions and roles
     */
    public function permissionsFix(Request $request): JsonResponse
    {
        if (!$request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        try {
            // Reset permission cache
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            // All permissions from the canonical list
            $permissions = [
                'view_dashboard', 'access_admin',
                'view_students', 'create_students', 'edit_students', 'delete_students', 'view_own_students',
                'view_inscriptions', 'create_inscriptions', 'edit_inscriptions', 'approve_inscriptions', 'reject_inscriptions',
                'inscriptions.view', 'inscriptions.create', 'inscriptions.edit', 'inscriptions.delete', 'inscriptions.validate',
                'edit inscriptions', 'valider inscriptions', 'annuler inscriptions', 'delete inscriptions',
                'paiements.view', 'paiements.create', 'paiements.edit', 'paiements.delete', 'paiements.validate',
                'frais.view', 'frais.create', 'frais.edit', 'frais.delete', 'frais.configure',
                'security.audit.view', 'security.audit.export', 'comptabilite.audit.view', 'security.users.monitor',
                'generate-attendance-codes',
                'manage-planning', 'view-all-timetables', 'view_timetables', 'create_timetable', 'edit_timetables', 'delete_timetables', 'view_own_timetable',
                'view cycles', 'create cycles', 'edit cycles', 'delete cycles', 'restore cycles', 'force delete cycles',
                'view_classes', 'create_classes', 'edit_classes', 'delete_classes',
                'view_filieres', 'create_filieres', 'edit_filieres',
                'view_niveaux_etudes', 'create_niveaux_etudes', 'edit_niveaux_etudes', 'delete_niveaux_etudes',
                'view_matieres', 'create_matieres', 'edit_matieres', 'delete_matieres',
                'view_notes', 'create_notes', 'edit_notes', 'edit_existing_notes', 'view_own_notes', 'manage_own_notes',
                'view_grades', 'view_own_grades', 'create_grade', 'edit_grades', 'delete_grades',
                'view_evaluations', 'view_own_exams', 'create_evaluations', 'edit_evaluations',
                'view_bulletins', 'generate_bulletins', 'edit_bulletins', 'view_own_bulletin',
                'view_attendances', 'create_attendance', 'create_attendances', 'edit_attendances', 'delete_attendances',
                'view_own_attendances', 'sign_attendance', 'view_own_attendance',
                'view_payments', 'create_payments', 'edit_payments', 'view_comptabilite', 'manage_comptabilite',
                'view_teachers', 'create_teachers', 'edit_teachers', 'view_personnel', 'manage_personnel', 'view_own_profile',
                'view_coordinateurs', 'create_coordinateurs', 'edit_coordinateurs', 'delete_coordinateurs',
                'view_schedules', 'create_schedules', 'edit_schedules', 'view_own_schedule',
                'send_messages', 'receive_messages', 'view_annonces', 'create_annonces', 'edit_annonces',
                'view_reports', 'generate_reports',
                'view_settings', 'edit_settings', 'manage_system',
                'view_planning_general', 'edit_planning_general', 'view_resultats', 'edit_resultats',
                'module.enseignants.access', 'module.notes_evaluations.access', 'module.emploi_temps.access',
                'module.presences.access', 'module.lmd.access', 'module.academique.access',
                'module.etudiants.access', 'module.comptabilite.access', 'module.communication.access',
                'manage-users', 'edit_enseignants', 'edit_bulletins',
                'paywall.configure', 'paywall.manage_subscriptions', 'paywall.extend_subscriptions', 'paywall.view_all_stats',
                'system.technical_access', 'system.emergency_override',
                'comptabilite.access', 'comptabilite.dashboard.view', 'comptabilite.relances.send',
                'comptabilite.reports.export', 'comptabilite.config.manage',
                'comptabilite.paiements.view', 'comptabilite.paiements.validate',
                'comptabilite.frais.view', 'comptabilite.frais.configure',
            ];

            $createdPermissions = 0;
            foreach ($permissions as $permName) {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                $createdPermissions++;
            }

            // Roles
            $roles = [
                'superAdmin', 'admin', 'secretaire', 'coordinateur',
                'enseignant', 'etudiant', 'parent', 'serviceTechnique',
                'teacher', 'comptable', 'caissier',
            ];

            $createdRoles = 0;
            foreach ($roles as $roleName) {
                Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $createdRoles++;
            }

            // Sync superAdmin with all permissions
            $superAdminRole = Role::findByName('superAdmin');
            $superAdminRole->syncPermissions($permissions);

            // Sync admin with all permissions
            $adminRole = Role::findByName('admin');
            $adminRole->syncPermissions($permissions);

            // Reset cache after sync
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

            return $this->successResponse([
                'permissions_synced' => $createdPermissions,
                'roles_synced' => $createdRoles,
                'superadmin_permissions' => count($permissions),
            ], 'Permissions and roles synced successfully');
        } catch (\Exception $e) {
            Log::error('CLI: permissions fix failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Operation failed. Check server logs for details.', [], 500);
        }
    }
}
