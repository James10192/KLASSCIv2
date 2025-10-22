# Legacy → New Schema Mapping (KLASSCI)

This document summarises the structural differences observed between:

- `c2569688c_smart_school.sql` (legacy dump, Sept 2025)
- `c2569688c_esbtp_abidjan.sql` (new dump, Oct 2025)

It focuses on tables present in at least one dump, their column differences, and row deltas. Use it as the starting point to design per-table migration scripts.

## 1. Tables unique to each dump

### Legacy-only

- `esbtp_niveau_matiere`
- `esbtp_unites_enseignement`

These tables have no direct counterpart in the new schema and will need manual review.

### New-only (not present in legacy)

The new schema introduces many additional tables. The ones populated in the new dump are:

| Table | Rows |
| --- | ---: |
| `audits` | 12 319 |
| `esbtp_attendance_settings` | 10 |
| `esbtp_categorie_paiements` | 13 |
| `esbtp_categories_depenses` | 4 |
| `esbtp_departments` | 4 |
| `esbtp_facture_details` | 666 |
| `esbtp_factures` | 319 |
| `esbtp_frais_categories` | 3 |
| `esbtp_frais_configurations` | 81 |
| `esbtp_frais_options` | 3 |
| `esbtp_frais_subscriptions` | 441 |
| `esbtp_inscription_workflow_histories` | 479 |
| `esbtp_matricule_configs` | 3 |
| `esbtp_planifications_academiques` | 18 |
| `esbtp_regles_academiques` | 22 |
| `esbtp_system_settings` | 10 |
| `settings` | 109 |
| `settings_backups` | 8 |

The remaining new tables exist but are empty in the dump.

## 2. Column differences for common tables

Below tables appear in both dumps. Columns listed under **Legacy-only** exist only in the old schema; **New-only** columns were added in the new schema.

| Table | Legacy-only columns | New-only columns |
| --- | --- | --- |
| `esbtp_attendances` | – | `call_type` |
| `esbtp_classe_matiere` | – | `deleted_at` |
| `esbtp_classes` | `capacity` | `places_totales`, `places_occupees` |
| `esbtp_enseignant_matiere` | – | `notes`, `heures_prevues`, `heures_effectuees` |
| `esbtp_etudiants` | – | `date_abandon`, `motif_abandon`, `abandon_type` |
| `esbtp_evaluations` | – | `enseignant_id`, `enseignant_externe_nom`, `token_saisie_externe`, `token_expire_at` |
| `esbtp_filieres` | – | `created_by`, `updated_by` |
| `esbtp_inscriptions` | – | `affectation_status`, `workflow_step`, `reliquat_annee_precedente`, `reliquat_source_inscription_id`, `has_reliquat`, `reliquat_notes`, `paiement_validation_id`, `comptabilite_activee`, `classe_alternative_id`, `reinscription_status`, `reinscription_validated_at`, `reinscription_validated_by`, `reinscription_observations` |
| `esbtp_matieres` | `coefficient_default`, `total_heures_default` | – |
| `esbtp_paiements` | – | `categorie_id`, `frais_category_id`, `annee_universitaire_id`, `type_paiement`, `date_echeance`, `numero_transaction`, `reference_externe`, `metadata`, `validateur_id`, `relance_id`, `reliquat_detail_id` |
| `esbtp_seance_cours` | – | `date_seance`, `teacher_id`, `type`, `color`, `homework_description`, `homework_due_date`, `is_recurring`, `recurrence_days`, `priority` |
| `personal_access_tokens` | – | `expires_at` |
| `settings` | – | `type`, `description`, `is_required`, `default_value`, `validation_rules`, `is_active`, `requires_restart`, `category`, `sort_order`, `created_by`, `updated_by` |
| `students` | `user_id`, `student_id`, `parcours_id`, `promotion`, `current_year`, `status`, `registration_date`, `expected_graduation_date`, `actual_graduation_date`, `scholarship_status`, `scholarship_details`, `special_needs`, `international_student`, `country_of_origin`, `visa_status`, `visa_expiry_date`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone`, `previous_institution`, `previous_qualification`, `admission_score`, `notes`, `created_by`, `updated_by` | `name`, `registration_number`, `email`, `phone`, `date_of_birth`, `place_of_birth`, `gender`, `address`, `parent_name`, `parent_phone`, `parent_email`, `emergency_contact`, `emergency_phone`, `medical_info`, `is_active` |
| `teachers` | – | `matricule` |
| `users` | – | `must_change_password`, `password_changed_at`, `first_login_at`, `created_by`, `updated_by` |

All other common tables have identical column names.

## 3. Row count comparison (legacy vs new)

Significant differences in row counts highlight where the datasets diverge. Positive counts indicate data exists; zero means no inserts in that dump.

| Table | Legacy rows | New rows | Notes |
| --- | ---: | ---: | --- |
| `audits` | 0 | 12 319 | New audit log data only in new system |
| `esbtp_annee_universitaires` | 22 | 5 | New dump trimmed to active years |
| `esbtp_bulletins` | 3 | 0 | Legacy data missing in new system |
| `esbtp_classes` | 53 | 84 | New schema has more class entries |
| `esbtp_config_matieres` | 69 | 0 | Legacy configuration absent from new dump |
| `esbtp_etudiant_parent` | 1 712 | 319 | Parent links drastically reduced/renamed |
| `esbtp_etudiants` | 1 590 | 2 785 | New system has additional students |
| `esbtp_evaluations` | 612 | 0 | Legacy evaluations missing |
| `esbtp_filieres` | 13 | 7 | Data consolidated |
| `esbtp_inscriptions` | 1 588 | 2 841 | New system carries more enrolments |
| `esbtp_matiere_filiere` | 114 | 18 | Mapping simplified |
| `esbtp_matieres` | 84 | 20 | Legacy list far larger |
| `esbtp_notes` | 22 485 | 0 | Evaluation notes absent in new dump |
| `esbtp_parents` | 1 714 | 321 | Parent profile model revamped |
| `esbtp_paiements` | 0 | 375 | Payment module brand new |
| `model_has_roles` | 1 589 | 322 | Role assignments recast |
| `permissions` | 112 | 234 | Permission set expanded |
| `roles` | 5 | 10 | Additional roles introduced |
| `settings` | 0 | 109 | New configuration registry |
| `users` | 1 599 | 324 | More granular user model in legacy; new system likely separates staff/students differently |

(For a complete list see the comparison script output: 46 tables show row deltas.)

## 4. Implications for migration

1. **Direct table copy possible** when column sets match and row counts confirm differences only due to new data (e.g. `esbtp_annee_universitaires` can be merged by ID/date ranges).
2. **Column remapping required** where new tables add mandatory fields (`esbtp_paiements`, `esbtp_inscriptions`, `users`). Default values or joins to new supporting tables will be necessary.
3. **Legacy-only tables** (`esbtp_niveau_matiere`, `esbtp_unites_enseignement`) either map to new structures or become obsolete; examine business requirements.
4. **New-only tables** must be populated from new sources or derived data (financial modules, workflow history, audit trail, etc.).
5. **Row count gaps** highlight functional areas where the new system expects data that the legacy dump does not cover (e.g. payments). Decide whether to migrate legacy equivalents or start fresh.

## 5. Next steps

1. Confirm mapping rules for critical domains (students, classes, inscriptions, evaluations, notes).
2. For each table with column differences, design an ETL step that injects default values or derives new fields.
3. Generate reconciliation reports comparing key metrics (number of students per class, payments per year) to validate migrated data.
4. Iterate on a migration script (SQL or Python) that pulls from the legacy dump, transforms data, and loads into the new schema while respecting referential integrity.

