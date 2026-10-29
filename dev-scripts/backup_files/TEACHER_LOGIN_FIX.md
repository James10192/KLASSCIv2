# Fix for Teacher Login Issue in ESBTP-yAKROv2

## Problem

When attempting to log in as a teacher with the credentials:
- Email: enseignant@test.com
- Password: password123

The following error was encountered:
```
Illuminate\Database\QueryException
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'enseignant_id' in 'where clause' 
(SQL: select * from `esbtp_seance_cours` where (`enseignant_id` = 2 or `enseignant` = Enseignant Test) 
and `esbtp_seance_cours`.`deleted_at` is null)
```

## Cause

The error occurred because the `DashboardController.php` file was trying to query the `esbtp_seance_cours` table using a column named `enseignant_id`, but this column doesn't exist anymore. 

Looking at the migration history, we found that there was a migration (`2025_03_15_140635_update_esbtp_seance_cours_table_for_enseignant_text.php`) that replaced the `enseignant_id` column with a text column called `enseignant`.

## Solution

We updated the `teacherDashboard` method in the `DashboardController.php` file to remove the reference to the non-existent `enseignant_id` column and only use the `enseignant` column.

**Before:**
```php
$classesTaught = ESBTPSeanceCours::where('enseignant_id', $teacherId)
    ->orWhere('enseignant', $user->name)
    ->with(['classe', 'matiere', 'emploiTemps'])
    ->get()
    ->pluck('classe_id')
    ->unique()
    ->count();
```

**After:**
```php
$classesTaught = ESBTPSeanceCours::where('enseignant', $user->name)
    ->with(['classe', 'matiere', 'emploiTemps'])
    ->get()
    ->pluck('classe_id')
    ->unique()
    ->count();
```

## Additional Changes

We also renamed the classes in two pending migrations to avoid naming conflicts:
1. Renamed `CreateEvaluationsTable` to `CreateNewEvaluationsTable` in `2025_05_10_000001_create_evaluations_table.php`
2. Renamed `CreateStudentGradesTable` to `CreateNewStudentGradesTable` in `2025_05_10_000002_create_student_grades_table.php`

These migrations were not running because of class name conflicts with existing migrations.

## Testing

With these changes in place, teachers should now be able to log in successfully without encountering the database error.

To test:
1. Log in with the teacher credentials: enseignant@test.com / password123
2. You should now be able to access the teacher dashboard without errors
3. Verify that the class count and other teacher-specific information is displayed correctly

## Additional Notes

If you encounter any issues with teacher login or dashboard access, please check if there are other references to the `enseignant_id` column in the codebase that might need to be updated. 