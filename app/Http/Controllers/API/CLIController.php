<?php

namespace App\Http\Controllers\API;

/**
 * @deprecated Split into focused sub-controllers in App\Http\Controllers\API\CLI\:
 *   - CLIUserController (users, userCreate, userResetPasswordExpiry, userDelete)
 *   - CLIAcademicController (annee, anneeSet, anneeCreate)
 *   - CLIStudentController (students, studentShow, inscriptions, validateInscription)
 *   - CLIDataController (stats, classes, payments, settings, settingsUpdate)
 *   - CLIMaintenanceController (cacheClear, permissionsFix)
 *
 * This file is kept for reference only. All routes now point to the sub-controllers.
 */
class CLIController extends BaseApiController
{
    //
}
