# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Tronc commun / specialisation enrollment system (issue #160)
  - 7 configurable settings in Settings > Configuration Bulletin
  - `TroncCommunService` orchestrating TC-to-specialization flow
  - Specialization selection page with AJAX class loading
  - Double inscription per academic year (TC class + specialization class)
  - Payment reporting from TC to specialization enrollment
  - MGA calculation including S1 notes from original TC class
  - Bulletin display of origin class name
  - Matiere commune auto-detection between TC and specializations
  - Planning strict mode restricting subjects by semester
- Filiere model: `is_tronc_commun`, `semestres_tronc_commun` fields
- Inscription model: `inscription_origine_id`, `type_changement` fields
- `ESBTPSpecialisationController` with show/getClasses/store actions
- `TroncCommunSettingsSeeder` added to `setup.php` automated setup

### Changed
- Inscription unique constraint: `(etudiant_id, annee_universitaire_id, status)` replaced by `(etudiant_id, annee_universitaire_id, classe_id)` to allow 2 enrollments per year
- `BulletinService::genererDonneesBulletin()` now handles weighted S1+S2 MGA for specialization enrollments
- Filiere create/edit forms show TC configuration when tronc commun is enabled

### Fixed
- Migration FK dependency: create new unique index before dropping old one (MySQL constraint on `etudiant_id`)
- Specialisation view: `@section('scripts')` replaced by `@push('scripts')` to match layout `@stack`
