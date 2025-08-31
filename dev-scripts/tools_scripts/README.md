# Smart School Diagnostic Tools

This directory contains copies of the diagnostic and fix tools created to resolve installation issues with the Smart School application.

## Tools Overview

1. **fix_migrations.php** - Automatically fixes duplicate migration classes and renames files
2. **migration_diagnostic.php** - Analyzes migration files and database connection
3. **php_version_check.php** - Verifies PHP version and configuration meets requirements
4. **phpinfo.php** - Displays detailed PHP configuration information
5. **tools_index.php** - Provides a navigation interface for the diagnostic tools
6. **MIGRATION_FIX.md** - Documentation for the migration issue and solution

## How to Use

These files are copies of the tools located in the `public/tools/` directory of the application. To use these tools:

1. Copy the desired tool to the `public/tools/` directory of your Smart School installation
2. Access the tool through your web browser at `http://your-domain/tools/tool-name.php`

## Common Issues Addressed

### 1. Duplicate Migration Classes

The main issue addressed by these tools is the presence of duplicate migration class names in the database migration files. Specifically:

- `CreateTeachersTable` - Found in both:
  - `2025_02_27_200010_create_teachers_table.php`
  - `2025_02_28_000006_create_teachers_table.php`

- `CreateDepartmentsTable` - Found in both:
  - `2025_02_27_200004_create_departments_table.php`
  - `2025_02_28_000007_create_departments_table.php`

The `fix_migrations.php` tool automatically renames these classes and updates the table names to avoid conflicts.

### 2. PHP Version Requirements

The application requires PHP 8.0 or higher. The `php_version_check.php` tool verifies that your PHP version meets this requirement and checks for required extensions.

## Troubleshooting

If you encounter issues with the tools:

1. Make sure your web server has write permissions to the migration files
2. Check that your PHP version is 8.0 or higher
3. Ensure all required PHP extensions are installed
4. Verify that your database credentials are correct

## Additional Resources

For more detailed information about the migration issues and solutions, refer to the `MIGRATION_FIX.md` file in this directory. 